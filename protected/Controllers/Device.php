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
                    $data['url'] = new Url($value->href);
                    $data['filter'] = DevModulePortGeo::buildFilter($data['filter'], new Url($value->href));
                    $data['sorting'] = DevModulePortGeo::buildSorting($data['sorting']);
                    $data['filter']->query = DevModulePortGeo::buildQuery($data['filter'], $data['sorting'], $data['pager']);

                    $data['pager']->records = DevModulePortGeo::countAllByQuery(($data['filter'])->query,$data['filter']->queryParams);
                    $data['pager'] = DevModulePortGeo::updatePager($data['pager']);
                    $data['filter']->query
                        ->offset(($data['pager'])->rowsOnPage * (($data['pager'])->page - 1))
                        ->limit(($data['pager'])->rowsOnPage);
                    $data['devices'] = DevModulePortGeo::findAllByQuery($data['filter']->query,$data['filter']->queryParams);
                    $data['appTypeMap'] = DevModulePortGeo::$applianceTypeMap;
                    $this->data->body->html = $this->view->render('DevicesTableBody.html', $data);
                    $this->data->body->pager = $data['pager'];
                    break;
                default:
                    break;
            }
        }
    }

}