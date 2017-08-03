<?php

namespace App\Controllers;


use App\Components\Cookies;
use App\Components\Links;
use App\Components\UrlExt;
use App\ViewModels\GeoDevModulePort_View;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class Ajax extends Controller
{
    use DebugTrait;

    public function actionDefault()
    {

    }

    public function actionPlugin()
    {

    }

    public function actionDevices()
    {

    }

    /**
     * get параметры передаваемые от клиента:
     * order - порядок сортировки (см. protected static $sortOrders в GeoDevModulePort_View)
     * filters->appTypes = типы девайсов которые попадут в выборку (берем из devTypesTrait)
     */
    public function actionDevicesData()
    {
        $http = new Request();
        $info = new Std();

        $info->currentPage = $http->get->page ?? 1;
        $info->order = GeoDevModulePort_View::sortOrder($http->get->order);
        //делаем фильтр по типам устройств (если задан параметр filters->appTypes)
        $info->filters->appTypes = $http->get->filters->appTypes ?? 'all';
        $info->filters->appTypes = GeoDevModulePort_View::appTypeFilter($http->get->filters->appTypes);
        $where[] = '"appType_id" IN (' . implode(',', $info->filters->appTypes) . ')';
        //получаем количество записей и кол-во страниц с учетом фильтров
        $queryRecordsCount = (new Query())
            ->select()
            ->from(GeoDevModulePort_View::getTableName())
            ->where(implode(' AND ', $where));
        $info->recordsCount = GeoDevModulePort_View::countAllByQuery($queryRecordsCount);

        //если rowsOnPage не задан или rowsOnPage = -1 (выводим все на одной странице)
        $info->rowsOnPage = (empty($http->get->rowsOnPage) || $http->get->rowsOnPage < 0) ?
            $info->recordsCount :
            $http->get->rowsOnPage;
        $info->pagesCount = ceil($info->recordsCount / $info->rowsOnPage);

        //если в параметрах запроса номер страницы больше максимального, то устанавливаем его в макс.
        $info->currentPage = $info->currentPage <= $info->pagesCount ? $info->currentPage : $info->pagesCount;
        $info->offset = ($info->currentPage - 1) * $info->rowsOnPage;
        $info->columns = empty($http->get->columnList) ? GeoDevModulePort_View::findColumns() : GeoDevModulePort_View::findColumns($http->get->columnList);

        //создаем запрос данных
        $queryData = (new Query())
            ->select($info->columns)
            ->from(GeoDevModulePort_View::getTableName())
            ->where(implode(' AND ', $where))
            ->order($info->order)
            ->limit($info->rowsOnPage)
            ->offset($info->offset);
        $this->data->data = GeoDevModulePort_View::findAllByQuery($queryData)->toArrayRecursive();
        $this->data->info = $info;
    }

    /**
     * GET параметры передаваемые с запросом:
     * http->get->tableId
     * http->get->page
     * http->get->rowsOnPage
     * http->get->order
     * http->get->filters->appTypes
     */
    public function actionDevicesDataHtml()
    {
        $columnMap = [
            'reg' => 'region_id',
            'loc' => 'location_id',
            'pl' => 'platform_id',
            'type' => '"appType_id"'
        ];

        $http = new Request();
        $this->data->url = new UrlExt($http->url->toArrayRecursive());
        $params = new Std();
        $maxAge = 73;

        $params->tableId = $http->get->tableId;
        $params->resultAsArray = false;
        $params->currentPage = $http->get->page ?? 1;
        $params->rowsOnPage = $http->get->rowsOnPage ?? -1;
        $params->order = $http->get->order ?? 'default';
        $params->filters->appTypes = $http->get->filters->appTypes ?? '';
        $params->search = $http->get->search ?? new Std();
        $params->url = $http->get->url ?? '';
        if (! empty($params->url)) {
            $url = new UrlExt($params->url);
            $params->currentPage = 1;
            foreach ($url->query as $key => $val) {
                if (array_key_exists($key, $columnMap)) {
                    $params->search->{$columnMap[$key]} = $val;
                }
            }
        }

        $params = GeoDevModulePort_View::findAllByParams($params);

        if (! empty($params->tableId)) {
            //пишем pagesCount, recordsCount, rowsOnPage,  в cookies
            Cookies::setCookie($params->tableId . '_currentPage', $params->currentPage);
            Cookies::setCookie($params->tableId . '_pagesCount', $params->pagesCount);
            Cookies::setCookie($params->tableId . '_recordsCount', $params->recordsCount);
            Cookies::setCookie($params->tableId . '_rows', $params->rowsOnPage, time() + 30 * 24 * 3600);
        }
        $this->data->geoDevs = $params->data;
        $this->data->maxAge = $maxAge;

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