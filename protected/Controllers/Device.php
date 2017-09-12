<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\Paginator;
use App\Components\Sorter;
use App\ViewModels\DevModulePortGeo;
use function foo\func;
use T4\Core\Session;
use T4\Core\Std;
use T4\Core\Url;
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
    public function actionInfo2()
    {
        $this->data->exportUrl = '/export/hardInvExcel';
        $this->data->activeLink->devicesNew = true;
        $this->data->getParams = $_GET;
    }

    public function actionDevicesTable()
    {
        $request = (new Request())->get;
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
                    $sorter = isset($value->sorting->sortBy) ?
                        new Sorter(DevModulePortGeo::sortOrder($value->sorting->sortBy)) :
                        new Sorter(DevModulePortGeo::sortOrder('default'));
                    $paginator = isset($value->pager) ?
                        new Paginator($value->pager) :
                        new Paginator();
                    $joinedFilter = ContentFilter::joinFilters($tableFilter, $hrefFilter);
                    $query = $joinedFilter->countQuery(DevModulePortGeo::class);
                    $paginator->records = DevModulePortGeo::countAllByQuery($query);
                    $paginator->update();
                    $query = $joinedFilter->selectQuery(DevModulePortGeo::class, $sorter, $paginator);
                    $devices = DevModulePortGeo::findAllByQuery($query);
//                    $queryData->columns = $value->columns;
//                    $queryData->tableFilter = isset($value->tableFilter) ? $value->tableFilter : new Std();
//                    $queryData->hrefFilter = isset($value->hrefFilter) ? $value->hrefFilter : new Std();
//                    $queryData->sorting = isset($value->sorting) ? $value->sorting : new Std();
//                    $queryData->pager = isset($value->pager) ? new Paginator($value->pager) : new Paginator();
//                    $queryData->user = $this->data->user;
//
//                    $queryData->hrefFilter = DevModulePortGeo::buildHrefFilter($queryData->hrefFilter);
//                    $queryData->tableFilter = DevModulePortGeo::buildTableFilter($queryData->tableFilter);
//                    $queryData->joinedFilter = DevModulePortGeo::joinFilters($queryData->tableFilter, $queryData->hrefFilter);
//                    $queryData->joinedFilter = DevModulePortGeo::buildQueries($queryData->joinedFilter, $queryData->sorting, $queryData->pager);
//
//                    $queryData->pager->records = DevModulePortGeo::countAllByQuery($queryData->countQuery, $queryData->countQueryParam);
//                    $queryData->pager = DevModulePortGeo::updatePager($queryData->pager);
//                    $queryData->selectResult = DevModulePortGeo::findAllByQuery($queryData->selectQUery,$queryData->selectQueryParam);
//                    $queryData->appTypeMap = DevModulePortGeo::$applianceTypeMap;
//                    $this->data->body->html = $this->view->render('DevicesTableBody.html', $queryData);
//                    $this->data->body->hrefFilter = $queryData->hrefFilter;
//                    $this->data->body->tableFilter = $queryData->tableFilter;
//                    $this->data->body->pager = $queryData->pager;

//                    $data['columns'] = $value->columns;
//                    $data['tableFilter'] =
//                    $data['hrefFilter'] =
//                    $data[''] =
//                    $data[''] =
//                    $data[''] =
//                    $data['url'] = new Url($value->href);
//                    $data['sorting'] = DevModulePortGeo::buildSorting($data['sorting']);
//
//                    $data['hrefFilter'] =
//                    $data['tableFilter'] = DevModulePortGeo::buildTableFilter($data['tableFilter']);
//                    $data['filter'] = DevModulePortGeo::joinFilters($data['tableFilter'], $data['hrefFilter']);
//
//                    $data['filter']->query = DevModulePortGeo::buildQuery($data['filter'], $data['sorting'], $data['pager']);
//
//                    $data['pager']->records = DevModulePortGeo::countAllByQuery(($data['filter'])->query, $data['filter']->queryParams);
//                    $data['pager'] = DevModulePortGeo::updatePager($data['pager']);
//                    $data['filter']->query
//                        ->offset(($data['pager'])->rowsOnPage * (($data['pager'])->page - 1))
//                        ->limit(($data['pager'])->rowsOnPage);
//
//                    $data['devices'] = DevModulePortGeo::findAllByQuery($data['filter']->query,$data['filter']->queryParams);
//                    $data['appTypeMap'] = DevModulePortGeo::$applianceTypeMap;
//                    $this->data->body->html = $this->view->render('DevicesTableBody.html', $data);
//                    $this->data->body->hrefFilter = $data['hrefFilter'];
//                    $this->data->body->tableFilter = $data['tableFilter'];
//                    $this->data->body->pager = $data['pager'];
                    break;
                default:
                    break;
            }
        }
    }

}