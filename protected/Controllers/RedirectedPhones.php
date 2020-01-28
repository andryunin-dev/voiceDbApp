<?php
namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\Cucm;
use App\Components\Cucm\Models\RedirectedPhone;
use App\Components\Paginator;
use App\Components\Sorter;
use App\Models\Appliance;
use App\Models\ApplianceType;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class RedirectedPhones extends Controller
{
    public function actionDefault()
    {
        $this->data->exportUrl = '/redirectedPhones/fromCucm';
    }

    public function actionRedirectedPhoneTable()
    {
        $request = (new Request());
        $baseUrl = $request->referer;
        $basePhoneUrl = $request->url->protocol.'://'.$request->url->host.'/basePhone';
        $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
        foreach ($request as $key => $value ) {
            switch ($key) {
                case 'header':
                    $data['columns'] = $value->columns->toArrayRecursive();
                    $data['user'] = $this->data->user;
                    $this->data->header->html = $this->view->render('RedirectedPhoneTableHeader.html', $data);
                    break;
                case 'body':
                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, RedirectedPhone::class, RedirectedPhone::$columnMap) :
                        new ContentFilter();
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, RedirectedPhone::class, RedirectedPhone::$columnMap) :
                        new ContentFilter();
                    $globalFilter = isset($value->globalFilter) ?
                        new ContentFilter($value->globalFilter, RedirectedPhone::class, RedirectedPhone::$columnMap, 'g', 'OR', 'OR') :
                        new ContentFilter();

                    $sorter = isset($value->sorting->sortBy) ?
                        new Sorter(RedirectedPhone::sortOrder($value->sorting->sortBy), '', RedirectedPhone::class, RedirectedPhone::$columnMap) :
                        new Sorter(RedirectedPhone::sortOrder('default'), '', RedirectedPhone::class, RedirectedPhone::$columnMap);
                    $paginator = isset($value->pager) ?
                        new Paginator($value->pager) :
                        new Paginator();
                    $joinedFilter = ContentFilter::joinFilters($tableFilter, $hrefFilter);

                    $query = $joinedFilter->countQuery(RedirectedPhone::class, $globalFilter);

                    $paginator->records = RedirectedPhone::countAllByQuery($query);
                    $paginator->update();
                    $query = $joinedFilter->selectQuery(RedirectedPhone::class, $sorter, $paginator, $globalFilter);
                    $twigData = new Std();
                    $twigData->devices = RedirectedPhone::findAllByQuery($query);
                    $twigData->baseUrl = $baseUrl;
                    $twigData->basePhoneUrl = $basePhoneUrl;

                    $this->data->body->html = $this->view->render('RedirectedPhoneTableBody.html', $twigData);
                    $this->data->body->hrefFilter = $hrefFilter;
                    $this->data->body->tableFilter = $tableFilter;
                    $this->data->body->pager = $paginator;
                    $info[] = 'Записей: ' . $paginator->records;
                    $this->data->body->info = $info;
                    break;
                case 'headerFilter':
                    if (isset($value->filter)) {
                        $filterScr[$value->filter->column] = [$value->filter->statement => $value->filter->value];
                    } else {
                        $filterScr = [];
                    }
                    $newTabFilter = new ContentFilter($filterScr, RedirectedPhone::class, RedirectedPhone::$columnMap);

                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, RedirectedPhone::class, RedirectedPhone::$columnMap) :
                        new ContentFilter();
                    $tableFilter->mergeWith($newTabFilter);
                    //удалить statement 'eq' для поля column если есть statement 'like'
                    // (чтобы можно было выбирать кажды раз из полного набора значений колонки)
                    // без этого удаления выбрав, например "Астрахань", фильтр ничего другого выбрать уже не даст
                    if (isset($tableFilter->{$value->filter->column}->like) && isset($tableFilter->{$value->filter->column}->eq)) {
                        $tableFilter->removeStatement($value->filter->column, 'eq');
                    }
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, RedirectedPhone::class, RedirectedPhone::$columnMap) :
                        new ContentFilter();
                    $joinedFilter = (new ContentFilter())->mergeWith($tableFilter)->mergeWith($hrefFilter);
                    $sorter = new Sorter($value->filter->column, '', RedirectedPhone::class, RedirectedPhone::$columnMap);
                    $query = (new Query())
                        ->distinct()
                        ->select($sorter->sortBy)
                        ->from(RedirectedPhone::getTableName())
                        ->order($sorter->sortBy)
                        ->where($joinedFilter->whereStatement->where)
                        ->params($joinedFilter->whereStatement->params);
                    if (! empty($value->filter->limit) && is_numeric($value->filter->limit)) {
                        $query->limit((intval($value->filter->limit)));
                    }
                    $this->data->result = RedirectedPhone::findAllDistictColumnValues($query);
                    unset($this->data->user);
                    break;
                default:
                    break;
            }
        }
    }

    public function actionFromCucm()
    {
        $request = new Request();
        $this->data->baseUrl = $request->referer;
        $this->data->cucmsUrl = 'http://' . $request->host . '/export/cucmPublishersIp';
    }

    /**
     * Return Redirected Phones with given call forwarding number from Cucm:
     * if exists then return Array of RedirectedPhone,
     * else return []
     * @throws \Exception
     */
    public function actionFromCucmWithCallForwardingNumber()
    {
        ob_start();
        $result = [];
        $GETrequest = (new Request())->get;
        if (0 < $GETrequest->count() && !empty($GETrequest->cucmIp)) {
            try {
                $phones =
                    (empty($GETrequest->callForwardingNumber))
                        ? (new Cucm($GETrequest->cucmIp))->redirectedPhones()
                        : (new Cucm($GETrequest->cucmIp))->redirectedPhonesWithCallForwardingNumber($GETrequest->callForwardingNumber)
                ;
                $result = array_map(
                    function ($phone) {
                        return $phone->toArray();
                    },
                    $phones
                );
            } catch (\Throwable $e) {
                $result = ['error' => 'Runtime error'];
            }
        }
        ob_end_clean();
        echo json_encode($result);
        die;
    }
}
