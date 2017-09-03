<?php

namespace App\Controllers;

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

    }
    public function actionDevicesTable()
    {
        if (isset($_GET['header'])) {
            $data['columns'] = $_GET['header']['columns'];
            $data['user'] = $this->data->user;
            $this->data->header->html = $this->view->render('DevicesTableHeader.html', $data);
        }
        $this->data->header->result = $_GET;
    }

}