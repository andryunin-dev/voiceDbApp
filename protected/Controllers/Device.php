<?php

namespace App\Controllers;

use function foo\func;
use T4\Core\Session;
use T4\Core\Std;
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
    protected function filterFromGet($name, $value)
    {

    }
    protected function joinFilter(Std $filter)
    {
        $res = [];
        foreach ($filter as $key => $filterItem) {
            switch (true) {
                case isset($filterItem->eq):
                    if (is_string($filterItem->eq)) {
                        $filterItem->eq = preg_split("/\s*,\s*/", $filterItem->eq, -1, PREG_SPLIT_NO_EMPTY);
                    }
                    $subRes = [];
                    foreach ($filterItem->eq as $index => $value) {
                        $subRes[] = $this->quoteName($key) . '=' . ':' . $key . $index;
                        $params[':' . $key . $index] = $value;
                    }
                    $res[] = implode(' OR ', $subRes);
                    break;
                case isset($key->like):
                    $res[] = $this->quoteName($key) . ' LIKE ' . ':' . $key;
                    $params[':' . $key] = $filterItem->like;
                    break;
            }
        }
        $res = array_map(function ($item) { return '(' . $item . ')';}, $res);
        $filter->whereClause = implode(' AND ', $res);
        $filter->queryParams = $params;
        return $filter;
    }
    protected function quoteName($data)
    {
        return '"' . $data . '"';
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
                    $data['columns'] = $value->columns;
                    $data['filter'] = isset($value->filter) ? $value->filter : new Std();
                    $data['pager'] = isset($value->pager) ? $value->pager : new Std();
                    $data['user'] = $this->data->user;
                    $this->joinFilter($data['filter']);
                    $this->data->body->html = $this->view->render('DevicesTableBody.html', $data);
                    $this->data->body->pager = $data['pager'];
                    break;
                default:
                    $this->filterFromGet($key, $value);
                    break;
            }
        }
    }

}