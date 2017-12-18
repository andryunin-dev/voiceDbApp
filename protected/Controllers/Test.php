<?php

namespace App\Controllers;




use App\Components\ContentFilter;
use App\Components\Paginator;
use App\Components\Reports\PivotReport;
use App\Components\Sorter;
use App\Components\Sql\SqlFilter;
use App\Components\Tables\PivotTable;
use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\Table;
use App\Components\Tables\TableConfig;
use App\Models\Appliance;
use App\Models\DPortType;
use App\Models\LotusLocation;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\GeoDev_View;
use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Core\Url;
use T4\Http\Request;
use T4\Mvc\Controller;
use T4\Orm\Model;

class Test extends Controller
{
    public function actionDefault()
    {
        $rep = new PivotReport('test', GeoDev_View::class);

        $rep->delete();
        $rep = new PivotReport('test2', GeoDev_View::class);
        var_dump($rep);
        var_dump($rep->setReportColumns(['region', 'office', 'platformTitle', 'officeAddress']));
        var_dump($rep->setPivotColumn('platformTitle', [], 'asc'));
        var_dump($rep->setPivotFilter(['appType' => 'phone']));
        var_dump($rep->setReportColumnsFilter([], false));
        $rep->save();

        var_dump($rep->className);
        var_dump($rep->tableName);
        var_dump($rep->reportConfig);
        var_dump($rep->reportColumnsConfig);
        var_dump($rep->pivotColumnConfig);
        var_dump($rep->reportColumns);
        var_dump($rep->pivotColumn);
        var_dump($rep->pivotColumnValues);
        $sql = $rep->buildSelectQuery();
        var_dump($sql);
//        var_dump($rep->reportConfig);
//        var_dump($rep->reportColumns);
//        var_dump($rep->pivotColumn);
//        var_dump($rep->pivotColumnValues);
        die;

    }
    public function actionConfigTable()
    {
        $columns = ['region', 'city', 'office', 'hostname_dn', 'appType', 'platformTitle', 'softwareAndVersion', 'moduleInfo', 'portInfo', 'action'];
        $confColumns = [
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Оффисе', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'hostname_dn' => ['id' => 'hostname_dn','name' => 'hostname', 'width' => 15, 'sortable' => true, 'filterable' => true],
            'appType' => ['id' => 'app-type','name' => 'Тип', 'width' => '70px', 'sortable' => true, 'filterable' => true],
            'platformTitle' => ['id' => 'appliance','name' => 'Оборудование', 'width' => 20, 'sortable' => true, 'filterable' => true],
            'softwareAndVersion' => ['id' => 'soft','name' => 'ПО', 'width' => 15, 'sortable' => true, 'filterable' => true],
            'moduleInfo' => ['id' => 'module','name' => 'Модуль', 'width' => 10, 'sortable' => true, 'filterable' => false],
            'portInfo' => ['id' => 'dport','name' => 'Интерфейсы', 'width' => 15, 'sortable' => true, 'filterable' => false],
            'action' => ['id' => 'action','name' => 'Действия', 'width' => '105px'],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'appSortOrder' => 'desc'],
            'city' => ['city' => '', 'office' => '', 'appSortOrder' => 'desc'],
        ];
        $preFilter = (new SqlFilter(DevModulePortGeo::class))
            ->setFilter('appType', 'eq', ['phone']);
        //ToDO ability to add extra columns that haven't in class
        $tab = (new TableConfig('deviceInfo', DevModulePortGeo::class))
            ->dataUrl('/test/devicesTable.json')
            ->tableWidth(100)
            ->columns($columns, ['action'])
            ->sortOrderSets($sortTemplates)
            ->sortBy('region');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        $tab->cssSetHeaderTableClasses(['bg-primary', 'table-bordered'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"]);
        $tab->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($preFilter)
            ->save();

        var_dump($tab);
    }

    public function actionBuildTable()
    {
        $tb = new Table(new TableConfig('deviceInfo'));
        $tb->rowsOnPage(40);
        $res = $tb->buildTableConfig();
        $this->data = $res;
    }


    public function actionBuildPivotTable()
    {
        $tableName = 'deviceInfoPivot';
        $tb = new PivotTable(new PivotTableConfig($tableName));
        $tb->rowsOnPage(40);
//        $res = $tb->findPivotItems('plTitle');
        $res = $tb->buildTableConfig();
//        $res = $tb->buildJsonTableConfig();
        var_dump($res);die;
    }


    public function actionTest()
    {
        $conn = Model::getDbConnection();

    }

    public function actionDevicesTable()
    {
        $maxAge = 73;
        $url = new Url('/device/info');
        $request = (new Request());
        if (0 == $request->get->count()) {
            $request = $request->post;
        }
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
    /*===================================*/
    public function actionConfigPivotTable()
    {
        $tableName = 'deviceInfoPivot';
        $pivots = ['plTitle' => 'platformTitle'];
        $columns = ['region', 'city', 'office', 'plTitle', 'action'];
        $confColumns = [
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
            'action' => ['id' => 'action','name' => 'Действия', 'width' => '105px'],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $preFilter = (new SqlFilter(DevModulePortGeo::class))
            ->setFilter('appType', 'eq', ['phone']);
        $tab = (new PivotTableConfig($tableName, DevModulePortGeo::class));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col, $alias);
        }
        $tab->columns($columns, ['action'])
            ->sortOrderSets($sortTemplates)
            ->sortBy('region');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        $tab
            ->dataUrl('/test/devicesPivotTable.json')
            ->tableWidth(100)
            ->pivotPreFilter('plTitle', $preFilter)
            ->pivotSortBy('plTitle', ['platformTitle'], 'desc')
            ->pivotWidthItems('plTitle', '40px')
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($preFilter)
            ->save();

        var_dump($tab);
    }



    public function actionDevices()
    {

    }
    /*====Обработка Ajax запроса конфигурации таблицы ====*/
    public function actionTableSettings()
    {
        if (isset($_GET['tableName'])) {
            try {
                $tableName = $_GET['tableName'];
                $tb = new PivotTable(new PivotTableConfig($tableName));
                $tb->rowsOnPage(50);
                $config = $tb->buildTableConfig();
                $config->tableName = $_GET['tableName'];
                return $this->data->config = $config;
            } catch (Exception $e) {
                return $this->data->config = new Std();
            }
        }
    }

    /*обработка запроса header и body таблицы*/

    public function actionDevicesPivotTable()
    {
        $maxAge = 73;
        $url = new Url('/device/info');
        $request = (new Request());
        $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
        foreach ($request as $key => $value ) {
            switch ($key) {
                case 'header':
                    $data['columns'] = $value->columns->toArrayRecursive();
                    $data['user'] = $this->data->user;
                    $this->data->header->html = $this->view->render('DevicesPivotTableHeader.html', $data);
                    break;
                case 'body':
                    $request = $request->body;
                    $tb = new PivotTable(new PivotTableConfig($request->tableName));
                    $sqlStatement = $tb->selectStatement();
                    $tableFilter = isset($value->tableFilter);
            }
        }
    }
}