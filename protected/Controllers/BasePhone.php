<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\Paginator;
use App\Components\Sorter;
use App\ViewModels\BasePhoneView;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class BasePhone extends Controller
{
    public function actionDefault()
    {
        $this->data->exportUrl = '/export/hardInvExcel';
    }

    public function actionBasePhoneTable()
    {
        $request = (new Request());
        $baseUrl = $request->referer;
        $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
        foreach ($request as $key => $value ) {
            switch ($key) {
                case 'header':
                    $data['columns'] = $value->columns->toArrayRecursive();
                    $data['user'] = $this->data->user;
                    $this->data->header->html = $this->view->render('BasePhoneTableHeader.html', $data);
                    break;
                case 'body':
                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, BasePhoneView::class, BasePhoneView::$columnMap) :
                        new ContentFilter();
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, BasePhoneView::class, BasePhoneView::$columnMap) :
                        new ContentFilter();
                    $globalFilter = isset($value->globalFilter) ?
                        new ContentFilter($value->globalFilter, BasePhoneView::class, BasePhoneView::$columnMap, 'g', 'OR', 'OR') :
                        new ContentFilter();

                    $sorter = isset($value->sorting->sortBy) ?
                        new Sorter(BasePhoneView::sortOrder($value->sorting->sortBy), '', BasePhoneView::class, BasePhoneView::$columnMap) :
                        new Sorter(BasePhoneView::sortOrder('default'), '', BasePhoneView::class, BasePhoneView::$columnMap);
                    $paginator = isset($value->pager) ?
                        new Paginator($value->pager) :
                        new Paginator();
                    $joinedFilter = ContentFilter::joinFilters($tableFilter, $hrefFilter);

                    $query = $joinedFilter->countQuery(BasePhoneView::class, $globalFilter);

                    $paginator->records = BasePhoneView::countAllByQuery($query);
                    $paginator->update();
                    $query = $joinedFilter->selectQuery(BasePhoneView::class, $sorter, $paginator, $globalFilter);
                    $twigData = new Std();
                    $twigData->devices = BasePhoneView::findAllByQuery($query);
                    $twigData->baseUrl = $baseUrl;

                    $this->data->body->html = $this->view->render('BasePhoneTableBody.html', $twigData);
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
                    $newTabFilter = new ContentFilter($filterScr, BasePhoneView::class, BasePhoneView::$columnMap);

                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, BasePhoneView::class, BasePhoneView::$columnMap) :
                        new ContentFilter();
                    $tableFilter->mergeWith($newTabFilter);
                    //удалить statement 'eq' для поля column если есть statement 'like'
                    // (чтобы можно было выбирать кажды раз из полного набора значений колонки)
                    // без этого удаления выбрав, например "Астрахань", фильтр ничего другого выбрать уже не даст
                    if (isset($tableFilter->{$value->filter->column}->like) && isset($tableFilter->{$value->filter->column}->eq)) {
                        $tableFilter->removeStatement($value->filter->column, 'eq');
                    }
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, BasePhoneView::class, BasePhoneView::$columnMap) :
                        new ContentFilter();
                    $joinedFilter = (new ContentFilter())->mergeWith($tableFilter)->mergeWith($hrefFilter);
                    $sorter = new Sorter($value->filter->column, '', BasePhoneView::class, BasePhoneView::$columnMap);
                    $query = (new Query())
                        ->distinct()
                        ->select($sorter->sortBy)
                        ->from(BasePhoneView::getTableName())
                        ->order($sorter->sortBy)
                        ->where($joinedFilter->whereStatement->where)
                        ->params($joinedFilter->whereStatement->params);
                    if (! empty($value->filter->limit) && is_numeric($value->filter->limit)) {
                        $query->limit((intval($value->filter->limit)));
                    }
                    $this->data->result = BasePhoneView::findAllDistictColumnValues($query);
                    unset($this->data->user);
                    break;
                default:
                    break;
            }
        }
    }
}
