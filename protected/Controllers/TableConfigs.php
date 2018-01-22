<?php

namespace App\Controllers;

use App\Components\Sql\SqlFilter;
use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\TableConfig;
use App\Models\LotusLocation;
use App\ViewModels\DevGeo_View;
use App\ViewModels\DevPhoneInfoGeo;
use T4\Core\Std;
use T4\Mvc\Controller;

class TableConfigs extends Controller
{
    public function actionPhoneStatsByModels()
    {
        $tableName = 'devGeoPivotStatistic';
        $ajaxHandlersURL = '/report/PhoneStatsReportHandler.json';
        $className = DevGeo_View::class;
        $maxAge = 73;

        $columns = ['region', 'city', 'office', 'people', 'phoneAmount', 'plTitle', 'plTitleActive', 'lotusId'];
        $pivots = [
            'plTitle' => ['name' => 'platformTitle'],
            'plTitleActive' => ['name' => 'platformTitle']
        ];
        $extraColumns = ['people'];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count']
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'people' => ['id' => 'people','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
            'plTitleActive' => ['id' => 'pl_active', 'visible' => false],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'office' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilterActive->addFilter('appAge', 'lt', [$maxAge]);
        $pivotItemsSelectBy = ['lotusId'];
        $tab = (new PivotTableConfig($tableName, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('region');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        $tab
            ->dataUrl($ajaxHandlersURL)
            ->tableWidth(100)
            ->pivotItemsSelectBy('plTitle', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitle', $preFilter)
            ->pivotItemsSelectBy('plTitleActive', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitleActive', $preFilterActive)
            ->pivotSortBy('plTitle', ['platformTitle'], 'desc')
            ->pivotWidthItems('plTitle', '65px')
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();

        var_dump($tab);
        die;
    }
    public function actionPhoneStatsTest()
    {
        $tableName = 'devGeoStatisticTest';
        $ajaxHandlersURL = '/report/PhoneStatsReportHandler.json';
        $className = DevGeo_View::class;
        $maxAge = 73;

        $columns = ['region', 'city', 'office', 'phoneAmount', 'lotusId'];
        $pivots = [];
        $extraColumns = [];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count']
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'office' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilterActive->addFilter('appAge', 'lt', [$maxAge]);
        $pivotItemsSelectBy = [];
        $tab = (new PivotTableConfig($tableName, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('region');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        $tab
            ->dataUrl($ajaxHandlersURL)
            ->tableWidth(100)
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();

        var_dump($tab);
        die;
    }
    public function actionPhoneStatsByModelsWithLowerRow()
    {
        $tableName = 'devGeoPivotStatisticWithLower';
        $tableNameBF = $tableName . 'BF';
        $ajaxHandlersURL = '/report/PhoneStatsReportHandler.json';
        $className = DevGeo_View::class;
        $maxAge = 73;

        $columns = ['region', 'city', 'office', 'people', 'phoneAmount', 'plTitle', 'plTitleActive', 'lotusId'];
        $pivots = [
            'plTitle' => ['name' => 'platformTitle'],
            'plTitleActive' => ['name' => 'platformTitle']
        ];
        $extraColumns = ['people'];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count']
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'people' => ['id' => 'people','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
            'plTitleActive' => ['id' => 'pl_active','visible' => false],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'office' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive->addFilter('appAge', 'lt', [$maxAge]);
        $pivotItemsSelectBy = ['lotusId'];
        $tab = (new PivotTableConfig($tableName, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('region');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }

        $tab
            ->dataUrl($ajaxHandlersURL)
            ->tableWidth(100)

            ->bodyFooterTableName($tableNameBF)

            ->pivotItemsSelectBy('plTitle', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitle', $pivotPreFilter)

            ->pivotItemsSelectBy('plTitleActive', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitleActive', $pivotPreFilterActive)

            ->pivotSortBy('plTitle', ['platformTitle'], 'desc')
            ->pivotWidthItems('plTitle', '65px')
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        echo '===============body footer table config===============';
        /*=============body footer table================*/

        $columns = ['textField', 'appType', 'people', 'phoneAmount', 'plTitle', 'plTitleActive'];
        $pivots = [
            'plTitle' => ['name' => 'platformTitle'],
            'plTitleActive' => ['name' => 'platformTitle']
        ];
        $pivotItemsSelectBy = ['appType'];
        $extraColumns = ['textField', 'people'];
        $confColumns = [
            'textField' => ['id' => 'txt_field','name' => 'txtField', 'width' => 35, 'visible' => true],
            'appType' => ['id' => 'app_type','name' => 'appType', 'width' => 10, 'visible' => false],
            'people' => ['id' => 'people','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
            'plTitleActive' => ['id' => 'pl_active','visible' => false],
        ];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count']
        ];
        $sortTemplates = [
            'default' => [],
        ];
        /*======preFilters=============*/
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive->addFilter('appAge', 'lt', [$maxAge]);

        /*=======make config================*/
        $tab = (new PivotTableConfig($tableNameBF, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('default');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }

        $tab
            ->dataUrl($ajaxHandlersURL)
            ->tableWidth(100)

            ->pivotItemsSelectBy('plTitle', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitle', $pivotPreFilter)

            ->pivotItemsSelectBy('plTitleActive', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitleActive', $pivotPreFilterActive)

            ->pivotSortBy('plTitle', ['platformTitle'], 'desc')
            ->pivotWidthItems('plTitle', '65px')
            ->cssSetBodyTableClasses(["table", "bg-success", "table-bordered", "body-footer"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        die;
    }
    public function actionPhoneStatsByModelsWithLowerRow2()
    {
        $tableName = 'devGeoPivotStatisticWithLower2';
        $tableNameBF = $tableName . 'BF';
        $ajaxHandlersURL = '/report/PhoneStatsReportHandler.json';
        $className = DevGeo_View::class;
        $maxAge = 73;

        $columns = ['region', 'city', 'office', 'people', 'phoneAmount', 'HWActive', 'plTitle', 'plTitleActive', 'lotusId'];
        $pivots = [
            'plTitle' => ['name' => 'platformTitle'],
            'plTitleActive' => ['name' => 'platformTitle']
        ];
        $extraColumns = ['people'];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count'],
            'HWActive' => ['name' => 'appType', 'method' => 'count']
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'people' => ['id' => 'people','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active','name' => 'HW Phones', 'width' => '60px'],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
            'plTitleActive' => ['id' => 'pl_active','visible' => false],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'office' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive->addFilter('appAge', 'lt', [$maxAge]);
        $pivotItemsSelectBy = ['lotusId'];
        $tab = (new PivotTableConfig($tableName, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        //calculated columns
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $HWPhonePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', [true])
            ->addFilter('appAge', 'lt', [73]);
        $tab->calculatedColumnPreFilter('HWActive', $HWPhonePreFilter);

        //=====================
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('region');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }

        $tab
            ->dataUrl($ajaxHandlersURL)
            ->tableWidth(100)

            ->bodyFooterTableName($tableNameBF)

            ->pivotItemsSelectBy('plTitle', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitle', $pivotPreFilter)

            ->pivotItemsSelectBy('plTitleActive', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitleActive', $pivotPreFilterActive)

            ->pivotSortBy('plTitle', ['platformTitle'], 'desc')
            ->pivotWidthItems('plTitle', '65px')
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        echo '===============body footer table config===============';
        /*=============body footer table================*/

        $columns = ['textField', 'appType', 'people', 'phoneAmount', 'HWActive', 'plTitle', 'plTitleActive'];
        $pivots = [
            'plTitle' => ['name' => 'platformTitle'],
            'plTitleActive' => ['name' => 'platformTitle']
        ];
        $pivotItemsSelectBy = ['appType'];
        $extraColumns = ['textField', 'people'];
        $confColumns = [
            'textField' => ['id' => 'txt_field','name' => 'txtField', 'width' => 35, 'visible' => true],
            'appType' => ['id' => 'app_type','name' => 'appType', 'width' => 10, 'visible' => false],
            'people' => ['id' => 'people','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active','name' => 'HW Phones', 'width' => '60px'],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
            'plTitleActive' => ['id' => 'pl_active','visible' => false],
        ];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count'],
            'HWActive' => ['name' => 'appType', 'method' => 'count']
        ];
        $sortTemplates = [
            'default' => [],
        ];
        /*======preFilters=============*/
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $pivotPreFilterActive->addFilter('appAge', 'lt', [$maxAge]);

        /*=======make config================*/
        $tab = (new PivotTableConfig($tableNameBF, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab->calculatedColumnPreFilter('HWActive', $HWPhonePreFilter);
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('default');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }

        $tab
            ->dataUrl($ajaxHandlersURL)
            ->tableWidth(100)

            ->pivotItemsSelectBy('plTitle', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitle', $pivotPreFilter)

            ->pivotItemsSelectBy('plTitleActive', $pivotItemsSelectBy)
            ->pivotPreFilter('plTitleActive', $pivotPreFilterActive)

            ->pivotSortBy('plTitle', ['platformTitle'], 'desc')
            ->pivotWidthItems('plTitle', '65px')
            ->cssSetBodyTableClasses(["table", "bg-success", "table-bordered", "body-footer"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        die;
    }
    public function actionPhoneStatsByClusters()
    {
        $tableName = 'devGeoPivotStatisticByClusters';
        $ajaxHandlersURL = '/report/PhoneStatsByClustersReportHandler.json';
        $className = DevPhoneInfoGeo::class;
        $maxAge = 73;
        $pivotWidthItems = '65px';

        $columns = ['region', 'city', 'office', 'people', 'phoneAmount', 'byPublishIp', 'byPublishIpActive', 'lotusId'];
        $pivots = [
            'byPublishIp' => ['name' => 'publisherIp'],
            'byPublishIpActive' => ['name' => 'publisherIp']];
        $extraColumns = ['people'];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count']
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'people' => ['id' => 'people','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'byPublishIp' => ['id' => 'pub','name' => 'Оборудование', 'width' => 65],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'office' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $preFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('appAge', 'lt', [$maxAge]);
        $pivotItemsSelectBy = ['lotusId'];
        $tab = (new PivotTableConfig($tableName, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('region');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        $tab
            ->dataUrl($ajaxHandlersURL)
            ->tableWidth(100)
            ->pivotItemsSelectBy('byPublishIp', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIp', $preFilter)
            ->pivotItemsSelectBy('byPublishIpActive', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIpActive', $preFilterActive)
            ->pivotSortBy('byPublishIp', ['publisherIp'], 'desc')
            ->pivotWidthItems('byPublishIp', $pivotWidthItems)
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();

        var_dump($tab);
        die;
    }

    public function actionLotusLocation()
    {
        $tableName = 'lotusLocation';
        $columns = ['lotus_id', 'title', 'reg_center', 'region', 'city', 'address', 'employees'];
        $extraColumns = [];
        $confColumns = [
            'lotus_id' => ['id' => 'lotus-id','name' => 'Lotus ID', 'width' => 12, 'sortable' => true, 'filterable' => true],
            'title' => ['id' => 'title','name' => 'Офис', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'reg_center' => ['id' => 'regc','name' => 'Рег.ц', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'address' => ['id' => 'addr','name' => 'Адрес', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'employees' => ['id' => 'employees','name' => 'Сотр.', 'width' => '60px'],
        ];
        $sortTemplates = [
            'regCenter' => ['reg_center' => '', 'region' => '', 'city' => '', 'title' => ''],
            'region' => ['region' => '', 'city' => '', 'title' => ''],
            'city' => ['city' => '', 'title' => ''],
        ];
        $tablePreFilter = (new SqlFilter(LotusLocation::class));
        $tab = (new TableConfig($tableName, LotusLocation::class));
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('regCenter');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        $tab
            ->connection('lotusData')
            ->dataUrl('/test/devicesPivotTable.json')
            ->tableWidth(100)
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();

        var_dump($tab);
        die;
    }

}