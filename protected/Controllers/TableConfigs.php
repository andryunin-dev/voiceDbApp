<?php

namespace App\Controllers;

use App\Components\Sql\SqlFilter;
use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\TableConfig;
use App\Models\LotusLocation;
use App\ViewModels\DevGeo_View;
use App\ViewModels\DevGeo_ViewMat;
use App\ViewModels\DevPhoneInfoGeo;
use App\ViewModels\DevPhoneInfoGeoMat;
use T4\Core\Std;
use T4\Mvc\Controller;

class TableConfigs extends Controller
{
    public function actionLotusIdEmployeesOnDevGeo()
    {
        $tableName = 'devGeoEmployeesLotusIdDistinct';
        $className = DevGeo_View::class;

        $columns = ['lotusId', 'lotus_lotusId', 'lotus_employees'];

        $confColumns = [
            'lotusId' => ['visible' => false],
            'lotus_lotusId' => ['visible' => false],
            'lotus_employees' => ['visible' => false],
        ];
        $sortTemplates = [
            'default' => ['lotusId' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $tab = (new PivotTableConfig($tableName, $className));
        //calculated columns
        //=====================
        $tab->columns($columns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('default');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        $tab
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);die;

    }
    public function actionPhoneStatsByModelsWithBodyFooter()
    {
        $tableName = 'devGeoPivotStatisticWithBodyFooter';
        $tableNameBF = $tableName . 'BF';
        $ajaxHandlersURL = '/report/PhoneStatsReportHandler.json';
        $className = DevGeo_ViewMat::class;
        $pivotWidthItems = '67px';
        $maxAge = 73;

        $columns = ['region', 'city', 'office', 'lotus_employees', 'phoneAmount', 'HWActive', 'HWNotActive', 'notHWActive', 'plTitle', 'plTitleActive', 'lotusId'];
        $pivots = [
            'plTitle' => ['name' => 'platformTitle'],
            'plTitleActive' => ['name' => 'platformTitle']
        ];
        $extraColumns = [];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count'],
            'HWActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']],
            'HWNotActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']],
            'notHWActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']]
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'lotus_employees' => ['id' => 'people-v','name' => 'Сотрудников', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active-v','name' => 'HW Phones<br>(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'HWNotActive' => ['id' => 'hw-not-active-v','name' => 'HW Phones<br>(не актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'notHWActive' => ['id' => 'not-hw-active-v','name' => 'virtual & analog<br>Phones(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
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
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('appAge', 'lt', [$maxAge]);
        $pivotItemsSelectBy = ['lotusId'];
        $tab = (new PivotTableConfig($tableName, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        //calculated columns
        foreach ($countedColumns as $alias => $col) {
            $col['selectBy'] = isset($col['selectBy']) ? $col['selectBy'] : null;
            $tab->calculatedColumn($alias, $col['name'], $col['method'], $col['selectBy']);
        }
        $HWActivePhonePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', ['true'])
            ->addFilter('appAge', 'lt', [$maxAge]);
        $tab->calculatedColumnPreFilter('HWActive', $HWActivePhonePreFilter);
        $HWNotActivePhonePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', ['true'])
            ->addFilter('appAge', 'ge', [$maxAge]);
        $tab->calculatedColumnPreFilter('HWNotActive', $HWNotActivePhonePreFilter);
        $notHWPhonePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', ['false'])
            ->addFilter('appAge', 'lt', [$maxAge]);
        $tab->calculatedColumnPreFilter('notHWActive', $notHWPhonePreFilter);

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

            ->pivotSortBy('plTitle', ['platformTitle'], 'asc')
            ->pivotWidthItems('plTitle', $pivotWidthItems)
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,500,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        echo '===============body footer table config===============';
        /*=============body footer table================*/

        $columns = ['textField', 'appType', 'employees', 'phoneAmount', 'HWActive', 'HWNotActive', 'notHWActive', 'plTitle', 'plTitleActive'];
        $pivots = [
            'plTitle' => ['name' => 'platformTitle'],
            'plTitleActive' => ['name' => 'platformTitle']
        ];
        $pivotItemsSelectBy = ['appType'];
        $extraColumns = ['textField', 'employees'];
        $confColumns = [
            'textField' => ['id' => 'txt_field','name' => 'ИТОГО:', 'width' => 35, 'visible' => true],
            'appType' => ['id' => 'app_type','name' => 'appType', 'width' => 10, 'visible' => false],
            'employees' => ['id' => 'people-v','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active','name' => 'HW Phones', 'width' => '60px'],
            'HWNotActive' => ['id' => 'hw-not-active','name' => 'HW not active Phones', 'width' => '60px'],
            'notHWActive' => ['id' => 'not-hw-active-v','name' => 'not HW Phones', 'width' => '60px'],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
            'plTitleActive' => ['id' => 'pl_active','visible' => false],
        ];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count'],
            'HWActive' => ['name' => 'appType', 'method' => 'count'],
            'HWNotActive' => ['name' => 'appType', 'method' => 'count'],
            'notHWActive' => ['name' => 'appType', 'method' => 'count']
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
        $tab->calculatedColumnPreFilter('HWActive', $HWActivePhonePreFilter);
        $tab->calculatedColumnPreFilter('HWNotActive', $HWNotActivePhonePreFilter);
        $tab->calculatedColumnPreFilter('notHWActive', $notHWPhonePreFilter);

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

            ->pivotSortBy('plTitle', ['platformTitle'], 'asc')
            ->pivotWidthItems('plTitle', $pivotWidthItems)
            ->cssSetBodyTableClasses(["table", "bg-success", "table-bordered", "body-footer"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        die;
    }
    public function actionPhoneStatsByClustersWithBodyFooter()
    {
        $tableName = 'devGeoPivotStatisticByClustersWithBodyFooter';
        $tableNameBF = $tableName . 'BF';
        $ajaxHandlersURL = '/report/PhoneStatsByClustersReportHandler.json';
        $className = DevPhoneInfoGeoMat::class;
        $pivotWidthItems = '67px';
        $maxAge = 73;

        $columns = ['region', 'city', 'office', 'lotus_employees', 'phoneAmount', 'HWActive', 'notHWActive', 'byPublishIp', 'byPublishIpActive', 'byPublishIpActiveHW'];
        $pivots = [
            'byPublishIp' => ['name' => 'publisherIp'],
            'byPublishIpActive' => ['name' => 'publisherIp'],
            'byPublishIpActiveHW' => ['name' => 'publisherIp'],
        ];
        $extraColumns = [];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count'],
            'HWActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']],
            'notHWActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']]
        ];
        $confColumns = [
            //'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'lotus_employees' => ['id' => 'people-v','name' => 'Сотрудников', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active-v','name' => 'HW Phones<br>(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'notHWActive' => ['id' => 'not-hw-active-v','name' => 'virtual & analog<br>Phones(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'byPublishIp' => ['id' => 'pub','name' => 'Оборудование', 'width' => 65],
            'byPublishIpActive' => ['id' => 'pub-active','name' => 'Оборудование','visible' => false],
            'byPublishIpActiveHW' => ['id' => 'pub-active-hw','name' => 'Оборудование','visible' => false],
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
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('appAge', 'lt', [$maxAge]);
        $pivotPreFilterActiveHW = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('appAge', 'lt', [$maxAge])
            ->addFilter('isHW', 'eq', ['true']);

        $HWPhonePreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', ['true'])
            ->addFilter('appAge', 'lt', [$maxAge]);
        $notHWPhonePreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', ['false'])
            ->addFilter('appAge', 'lt', [$maxAge]);

        $pivotItemsSelectBy = ['lotusId'];

        $tab = (new PivotTableConfig($tableName, $className));

        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        //calculated columns
        foreach ($countedColumns as $alias => $col) {
            $col['selectBy'] = isset($col['selectBy']) ? $col['selectBy'] : null;
            $tab->calculatedColumn($alias, $col['name'], $col['method'], $col['selectBy']);
        }
        $tab->calculatedColumnPreFilter('HWActive', $HWPhonePreFilterActive);
        $tab->calculatedColumnPreFilter('notHWActive', $notHWPhonePreFilterActive);

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

            ->pivotItemsSelectBy('byPublishIp', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIp', $pivotPreFilter)

            ->pivotItemsSelectBy('byPublishIpActive', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIpActive', $pivotPreFilterActive)

            ->pivotItemsSelectBy('byPublishIpActiveHW', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIpActiveHW', $pivotPreFilterActiveHW)

            ->pivotSortBy('byPublishIp', ['publisherIp'], 'desc')
            ->pivotWidthItems('byPublishIp', $pivotWidthItems)
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,300,500,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        echo '===============body footer table config===============';
        /*=============body footer table================*/

        $columns = ['textField', 'appType', 'employees', 'phoneAmount', 'HWActive', 'notHWActive', 'byPublishIp', 'byPublishIpActive', 'byPublishIpActiveHW'];

        $pivotItemsSelectBy = ['appType'];
        $extraColumns = ['textField', 'employees'];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count'],
            'HWActive' => ['name' => 'appType', 'method' => 'count'],
            'notHWActive' => ['name' => 'appType', 'method' => 'count']
        ];
        $confColumns = [
            'textField' => ['id' => 'txt_field','name' => 'ИТОГО:', 'width' => 35, 'visible' => true],
            'appType' => ['id' => 'app_type','name' => 'appType', 'width' => 10, 'visible' => false],
            'employees' => ['id' => 'people-v','name' => 'Сотрудников', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active-v','name' => 'HW Phones<br>(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'notHWActive' => ['id' => 'not-hw-active-v','name' => 'virtual & analog<br>Phones(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'byPublishIp' => ['id' => 'pub','name' => 'Оборудование', 'width' => 65],
            'byPublishIpActive' => ['id' => 'pub','name' => 'Оборудование','visible' => false],
            'byPublishIpActiveHW' => ['id' => 'pub-active-hw','name' => 'Оборудование','visible' => false]
        ];
        $sortTemplates = [
            'default' => [],
        ];
        /*======preFilters=============*/
//        $tablePreFilter = (new SqlFilter($className))
//            ->setFilter('appType', 'eq', ['phone']);
//        $pivotPreFilter = (new SqlFilter($className))
//            ->setFilter('appType', 'eq', ['phone']);
//        $pivotPreFilterActive = (new SqlFilter($className))
//            ->setFilter('appType', 'eq', ['phone'])
//            ->addFilter('appAge', 'lt', [$maxAge]);

        /*=======make config================*/
        $tab = (new PivotTableConfig($tableNameBF, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }
        //calculated columns
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab->calculatedColumnPreFilter('HWActive', $HWPhonePreFilterActive);
        $tab->calculatedColumnPreFilter('notHWActive', $notHWPhonePreFilterActive);

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

            ->pivotItemsSelectBy('byPublishIp', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIp', $pivotPreFilter)

            ->pivotItemsSelectBy('byPublishIpActive', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIpActive', $pivotPreFilterActive)

            ->pivotItemsSelectBy('byPublishIpActiveHW', $pivotItemsSelectBy)
            ->pivotPreFilter('byPublishIpActiveHW', $pivotPreFilterActiveHW)

            ->pivotSortBy('byPublishIp', ['publisherIp'], 'desc')
            ->pivotWidthItems('byPublishIp', $pivotWidthItems)
            ->cssSetBodyTableClasses(["table", "bg-success", "table-bordered", "body-footer"])
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
    public function actionLotusLocationTotal()
    {
        $tableName = 'lotusLocationTotal';
        $columns = ['employees'];
        $extraColumns = [];
        $countedColumns = [
            'employees' => ['name' => 'employees', 'method' => 'sum'],
        ];
        $confColumns = [
            'employees' => ['id' => 'employees','name' => 'Сотр.', 'width' => '60px'],
        ];
        $sortTemplates = [
            'default' => ['employees' => ''],
        ];
        $tablePreFilter = (new SqlFilter(LotusLocation::class));
        $tab = (new PivotTableConfig($tableName, LotusLocation::class));
        $tab->columns($columns, $extraColumns)
            ->sortOrderSets($sortTemplates)
            ->sortBy('default');
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, new Std($conf));
        }
        //calculated columns
        foreach ($countedColumns as $alias => $col) {
            $tab->calculatedColumn($alias, $col['name'], $col['method']);
        }
        $tab
            ->connection('lotusData')
            ->dataUrl('/test/devicesPivotTable.json')
            ->tableWidth(100)
//            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
//            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
//            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();

        var_dump($tab);
        die;
    }

    public function actionCucmPublishers()
    {
        $tableName = 'devGeoCUCMPublishers';
        $ajaxHandlersURL = '/report/getCucmPublishers.json';
        $className = DevGeo_View::class;

        $columns = ['managementIp', 'appDetails'];
        $extraColumns = [];
        $confColumns = [
            'managementIp' => ['visible' => false],
            'appDetails' => ['visible' => false],
        ];
        $sortTemplates = [
            'default' => ['managementIp' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['cmp']);
        $tab = (new PivotTableConfig($tableName, $className));
        //=====================
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
            ->rowsOnPageList([10,50,100,200,300,500,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);

    }

}