<?php

namespace App\Controllers;

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
//    protected function filterFromGet($name, $value)
//    {
//
//    }
//    protected function joinFilter(Std $filter)
//    {
//        $res = [];
//        $params = [];
//        foreach ($filter as $key => $filterItem) {
//            switch (true) {
//                case isset($filterItem->eq):
//                    if (is_string($filterItem->eq)) {
//                        $eqArray = preg_split("/\s*,\s*/", $filterItem->eq, -1, PREG_SPLIT_NO_EMPTY);
//                        $filterItem->eq = $eqArray;
//                    } elseif (is_int($filterItem->eq)) {
//                        $eqArray = [];
//                        $eqArray[] = $filterItem->eq;
//                        $filterItem->eq = $eqArray;
//                    }
//                    $subRes = [];
//                    foreach ($filterItem->eq as $index => $value) {
//                        $subRes[] = $this->quoteName($key) . ' = ' . ':' . $key . $index;
//                        $params[':' . $key . $index] = $value;
//                    }
//                    if (count($subRes) == 1) {
//                        $res[] = $subRes[0];
//                    } elseif (count($subRes) > 1) {
//                        $res[] = '(' . implode(' OR ', $subRes) . ')';
//                    }
//                    break;
//                case isset($key->like):
//                    $res[] = $this->quoteName($key) . ' LIKE ' . ':' . $key;
//                    $params[':' . $key] = $filterItem->like;
//                    break;
//            }
//        }
//        $filter->whereClause = implode(' AND ', $res);
//        $filter->queryParams = $params;
//        return $filter;
//    }
//    protected function quoteName($data)
//    {
//        return '"' . $data . '"';
//    }
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
                    $data['columns'] = $value->columns;
                    $data['filter'] = isset($value->filter) ? $value->filter : new Std();
                    $data['sorting'] = isset($value->sorting) ? $value->sorting : new Std();
                    $data['pager'] = isset($value->pager) ? $value->pager : new Std();
                    $data['user'] = $this->data->user;

                    $data['filter'] = DevModulePortGeo::buildFilter($data['filter'], new Url($value->href));
                    $data['sorting'] = DevModulePortGeo::buildSorting($data['sorting']);
                    $query = DevModulePortGeo::buildQuery($data['filter'], $data['sorting'], $data['pager']);
                    $res = DevModulePortGeo::findAllByQuery($query,$data['filter']->queryParams);
                    $this->data->body->html = $this->view->render('DevicesTableBody.html', $data);
                    $this->data->body->pager = $data['pager'];
                    break;
                default:
                    break;
            }
        }
    }

}