<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\Paginator;
use App\Components\Sorter;
use App\ViewModels\Geo_View;
use App\ViewModels\GeoDevStat;
use T4\Core\Std;
use T4\Core\Url;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class Locations extends Controller
{
    use DebugTrait;

    public function actionDefault()
    {
        $this->data->activeLink->offices = true;
    }
    public function actionOfficesTable()
    {
        $url = new Url('/device/info');
        $request = (new Request())->get;
        foreach ($request as $key => $value ) {
            switch ($key) {
                case 'header':
                    $data['columns'] = $value->columns->toArrayRecursive();
                    $data['user'] = $this->data->user;
                    $this->data->header->html = $this->view->render('LocTableHeader.html', $data);
                    break;
                case 'body':
                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, GeoDevStat::class, GeoDevStat::$columnMap) :
                        new ContentFilter();
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, GeoDevStat::class, GeoDevStat::$columnMap) :
                        new ContentFilter();
                    $globalFilter = isset($value->globalFilter) ?
                        new ContentFilter($value->globalFilter, GeoDevStat::class, GeoDevStat::$columnMap, 'g', 'OR', 'OR') :
                        new ContentFilter();

                    $sorter = isset($value->sorting->sortBy) ?
                        new Sorter(GeoDevStat::sortOrder($value->sorting->sortBy), '', GeoDevStat::class, GeoDevStat::$columnMap) :
                        new Sorter(GeoDevStat::sortOrder('default'), '', GeoDevStat::class, GeoDevStat::$columnMap);
                    $paginator = isset($value->pager) ?
                        new Paginator($value->pager) :
                        new Paginator();
                    $joinedFilter = ContentFilter::joinFilters($tableFilter, $hrefFilter);

                    $query = $joinedFilter->countQuery(GeoDevStat::class, $globalFilter);


                    $paginator->records = GeoDevStat::countAllByQuery($query);
                    $paginator->update();
                    $query = $joinedFilter->selectQuery(GeoDevStat::class, $sorter, $paginator, $globalFilter);
                    $twigData = new Std();
                    $twigData->offices = GeoDevStat::findAllByQuery($query);
                    $twigData->user = $this->data->user;
                    $twigData->url = $url;

                    $this->data->body->html = $this->view->render('LocTableBody.html', $twigData);
                    $this->data->body->hrefFilter = $hrefFilter;
                    $this->data->body->tableFilter = $tableFilter;
                    $this->data->body->pager = $paginator;
                    $info[] = 'Записей: ' . $paginator->records;
//                    $info[] = 'Сотрудников: ' . $peoples;
                    $this->data->body->info = $info;
                    break;
                case 'headerFilter':
                    if (isset($value->filter)) {
                        $filterScr[$value->filter->column] = [$value->filter->statement => $value->filter->value];
                    } else {
                        $filterScr = [];
                    }
                    $newTabFilter = new ContentFilter($filterScr, GeoDevStat::class, GeoDevStat::$columnMap);

                    $tableFilter = isset($value->tableFilter) ?
                        new ContentFilter($value->tableFilter, GeoDevStat::class, GeoDevStat::$columnMap) :
                        new ContentFilter();
                    $tableFilter->mergeWith($newTabFilter);
                    //удалить statement 'eq' для поля column если есть statement 'like'
                    // (чтобы можно было выбирать кажды раз из полного набора значений колонки)
                    // без этого удаления выбрав, например "Астрахань", фильтр ничего другого выбрать уже не даст
                    if (isset($tableFilter->{$value->filter->column}->like) && isset($tableFilter->{$value->filter->column}->eq)) {
                        $tableFilter->removeStatement($value->filter->column, 'eq');
                    }
                    $hrefFilter = isset($value->hrefFilter) ?
                        new ContentFilter($value->hrefFilter, GeoDevStat::class, GeoDevStat::$columnMap) :
                        new ContentFilter();
                    $joinedFilter = (new ContentFilter())->mergeWith($tableFilter)->mergeWith($hrefFilter);
                    $sorter = new Sorter($value->filter->column, '', GeoDevStat::class, GeoDevStat::$columnMap);
                    $query = (new Query())
                        ->distinct()
                        ->select($sorter->sortBy)
                        ->from(GeoDevStat::getTableName())
                        ->order($sorter->sortBy)
                        ->where($joinedFilter->whereStatement->where)
                        ->params($joinedFilter->whereStatement->params);
                    if (! empty($value->filter->limit) && is_numeric($value->filter->limit)) {
                        $query->limit((intval($value->filter->limit)));
                    }
                    $this->data->result = GeoDevStat::findAllDistictColumnValues($query);
                    unset($this->data->user);
                    break;
                default:
                    break;
            }
        }
    }

}