<?php

namespace App\Controllers;


use App\Components\Cookies;
use App\Components\Links;
use App\Components\UrlExt;
use App\Models\LotusLocation;
use App\ViewModels\DevModulePortGeo;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class Ajax extends Controller
{
    use DebugTrait;

    public function actionDefault()
    {
        var_dump($_GET);
    }

    public function actionPlugin()
    {

    }

    public function actionDevices()
    {

    }

    public function actionDevicesData()
    {
        $columnMap = [
            'reg' => 'region_id',
            'loc' => 'office_id',
            'city' => 'city_id',
            'pl' => 'platform_id',
            'type' => '"appType_id"',
            'cl' => 'cluster_id',
            'ven' => '"platformVendor_id"',
            'soft' => 'software_id',
            'softVer' => '"softwareVersion"'
        ];

        $http = new Request();
        $this->data->url = new UrlExt('/device/info');
        $params = new Std();
        $maxAge = 73;

        $params->tableId = $http->get->tableId;
        $params->resultAsArray = false;
        $params->currentPage = $http->get->page ?? 1;
        $params->rowsOnPage = $http->get->rowsOnPage ?? -1;
        $params->order = $http->get->order ?? 'default';
        $params->filters = $http->get->filters ?? new Std();
        $params->search = $http->get->search ?? new Std();
        $params->url = $http->get->url ?? '';
        $params->extUrl = $http->get->extUrl ?? '';
        if (! empty($params->url)) {
            $url = new UrlExt($params->url);
            $params->currentPage = 1;
            foreach ($url->query as $key => $val) {
                if (array_key_exists($key, $columnMap)) {
                    if (is_numeric($val)) {
                        $params->search->{$columnMap[$key]} = $val;
                    } else {
                        $params->search->{$columnMap[$key]} = '\'' . $val . '\'';
                    }
                } elseif ($key == 'noActiveAge' || $key == 'activeAge') {
                    $params->search->$key = $val;
                }
            }
        } elseif (! empty($params->extUrl)) {
            $url = new UrlExt($params->extUrl);
            $params->currentPage = 1;
            foreach ($url->query as $key => $val) {
                if (array_key_exists($key, $columnMap)) {
                    if (is_numeric($val)) {
                        $params->search->{$columnMap[$key]} = $val;
                    } else {
                        $params->search->{$columnMap[$key]} = '\'' . $val . '\'';
                    }
                } elseif ($key == 'noActiveAge' || $key == 'activeAge') {
                    $params->search->$key = $val;
                }
            }
        }

//        $params = GeoDevModulePort_View::findAllByParams($params);
//        $params = GeoDevModulePort_View::findAllLotusIdByParams($params);
        $params = DevModulePortGeo::findAllByParams($params);
        $params = DevModulePortGeo::findAllLotusIdByParams($params);

        if (! empty($params->tableId)) {
            //пишем pagesCount, recordsCount, rowsOnPage,  в cookies
            Cookies::setCookie($params->tableId . '_currentPage', $params->currentPage);
            Cookies::setCookie($params->tableId . '_pagesCount', $params->pagesCount);
            Cookies::setCookie($params->tableId . '_recordsCount', $params->recordsCount);
            Cookies::setCookie($params->tableId . '_rows', $params->rowsOnPage, time() + 30 * 24 * 3600);
        }
        $this->data->geoDevs = $params->data;
        $peopleInOffices = [];
        foreach ($params->locations as $location) {
            if (! key_exists($location->lotusId, $peopleInOffices)) {
                $peopleInOffices[$location->lotusId] = LotusLocation::employeesByLotusId($location->lotusId);
            }
        }
        $this->data->info->peopleCount = array_sum($peopleInOffices);
        $this->data->info->recordsCount = $params->recordsCount;
        $this->data->maxAge = $maxAge;
        $this->data->renderResult = $this->view->render('DevicesDataBody.html', $this->data);
        $this->data->geoDevs = '';
        $this->data->params = $params;
    }


    public function actionOffices()
    {
        $this->data->links = Links::instance();

    }


    public function actionHeaderData()
    {
        $header = (new Std())
            ->fill([
                'region' => [
                    'width' => 25,
                    'name' => 'Регион',
                    'class' => 'firstColumn'
                ],
                'city' => [
                    'width' => 25,
                    'name' => 'Город',
                ],
                'office' => [
                    'width' => 25,
                    'name' => 'Офис',
                ],

            ]);
        $this->data->columns = $header;
    }

    public function actionGetTemplate()
    {

    }
}