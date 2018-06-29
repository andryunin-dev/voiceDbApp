<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\Paginator;
use App\Components\Sorter;
use App\Models\ApplianceType;
use App\Models\LotusLocation;
use App\ViewModels\DevModulePortGeo;
use function foo\func;
use T4\Core\Session;
use T4\Core\Std;
use T4\Core\Url;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class Device extends Controller
{
    use DebugTrait;

    public function actionInfo()
    {
        $this->data->exportUrl = '/export/hardInvExcel';
        $this->data->activeLink->devicesNew = true;
    }

    public function actionDevicesTable()
    {
        $maxAge = 73;

        $url = new Url('/device/info');
//        $request = (new Request());
//        if (0 == $request->get->count()) {
//            $request = $request->post;
//        } else {
//            $request = $request->get;
//        }
        $request = (new Request());
        $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
        foreach ($request as $key => $value ) {
            switch ($key) {
                case 'header':
                    $data['columns'] = $value->columns->toArrayRecursive();
                    $data['user'] = $this->data->user;
                    $this->data->header->html = $this->view->render('DevicesTableHeader.html', $data);
                    break;
                case 'body':
                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, DevModulePortGeo::class, DevModulePortGeo::$columnMap) :
                        new ContentFilter();
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, DevModulePortGeo::class, DevModulePortGeo::$columnMap) :
                        new ContentFilter();
                    $globalFilter = isset($value->globalFilter) ?
                        new ContentFilter($value->globalFilter, DevModulePortGeo::class, DevModulePortGeo::$columnMap, 'g', 'OR', 'OR') :
                        new ContentFilter();

                    $sorter = isset($value->sorting->sortBy) ?
                        new Sorter(DevModulePortGeo::sortOrder($value->sorting->sortBy), '', DevModulePortGeo::class, DevModulePortGeo::$columnMap) :
                        new Sorter(DevModulePortGeo::sortOrder('default'), '', DevModulePortGeo::class, DevModulePortGeo::$columnMap);
                    $paginator = isset($value->pager) ?
                        new Paginator($value->pager) :
                        new Paginator();
                    $joinedFilter = ContentFilter::joinFilters($tableFilter, $hrefFilter);

                    $query = $joinedFilter->countQuery(DevModulePortGeo::class, $globalFilter);


                    $paginator->records = DevModulePortGeo::countAllByQuery($query);
                    $paginator->update();
                    $query = $joinedFilter->selectQuery(DevModulePortGeo::class, $sorter, $paginator, $globalFilter);
                    $twigData = new Std();
                    $twigData->devices = DevModulePortGeo::findAllByQuery($query);
                    $twigData->appTypeMap = DevModulePortGeo::$applianceTypeMap;
                    $twigData->user = $this->data->user;
                    $twigData->url = $url;
                    $twigData->maxAge = $maxAge;
                    $lotusIdList = DevModulePortGeo::officeIdListByQuery($query, 'lotusId');
                    $peoples = LotusLocation::countPeoples($lotusIdList);
                    $twigData->phoneType = ApplianceType::PHONE;

                    $this->data->body->html = $this->view->render('DevicesTableBody.html', $twigData);
                    $this->data->body->hrefFilter = $hrefFilter;
                    $this->data->body->tableFilter = $tableFilter;
                    $this->data->body->pager = $paginator;
                    $info[] = 'Записей: ' . $paginator->records;
                    $info[] = 'Сотрудников: ' . $peoples;
                    $this->data->body->info = $info;
                    break;
                case 'headerFilter':
                    if (isset($value->filter)) {
                        $filterScr[$value->filter->column] = [$value->filter->statement => $value->filter->value];
                    } else {
                        $filterScr = [];
                    }
                    $newTabFilter = new ContentFilter($filterScr, DevModulePortGeo::class, DevModulePortGeo::$columnMap);

                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, DevModulePortGeo::class, DevModulePortGeo::$columnMap) :
                        new ContentFilter();
                    $tableFilter->mergeWith($newTabFilter);
                    //удалить statement 'eq' для поля column если есть statement 'like'
                    // (чтобы можно было выбирать кажды раз из полного набора значений колонки)
                    // без этого удаления выбрав, например "Астрахань", фильтр ничего другого выбрать уже не даст
                    if (isset($tableFilter->{$value->filter->column}->like) && isset($tableFilter->{$value->filter->column}->eq)) {
                        $tableFilter->removeStatement($value->filter->column, 'eq');
                    }
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, DevModulePortGeo::class, DevModulePortGeo::$columnMap) :
                        new ContentFilter();
                    $joinedFilter = (new ContentFilter())->mergeWith($tableFilter)->mergeWith($hrefFilter);
                    $sorter = new Sorter($value->filter->column, '', DevModulePortGeo::class, DevModulePortGeo::$columnMap);
                    $query = (new Query())
                        ->distinct()
                        ->select($sorter->sortBy)
                        ->from(DevModulePortGeo::getTableName())
                        ->order($sorter->sortBy)
                        ->where($joinedFilter->whereStatement->where)
                        ->params($joinedFilter->whereStatement->params);
                    if (! empty($value->filter->limit) && is_numeric($value->filter->limit)) {
                        $query->limit((intval($value->filter->limit)));
                    }
                    $this->data->result = DevModulePortGeo::findAllDistictColumnValues($query);
                    unset($this->data->user);
                    break;
                default:
                    break;
            }
        }
    }

}