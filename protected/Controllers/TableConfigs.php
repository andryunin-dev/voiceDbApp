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
use App\ViewModels\HdsAgentsPhonesStatView;
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



    public function actionPhoneStatsByCallsWithBodyFooter()
    {
        $tableName = 'devGeoStatisticByNotUsedWithBodyFooter';
        $tableNameBF = $tableName . 'BF';
        $ajaxHandlersURL = '/report/PhoneStatsByNotUsedReportHandler.json';
        $className = DevGeo_ViewMat::class;
        $maxAge = 73;

        $columns = ['region', 'city', 'office', 'lotus_employees', 'phoneAmount', 'HWActive', 'HWNotActive', 'notHWActive', 'lotusId', 'office_id', 'd0Hw_nonCallingDevAmount', 'm0Hw_nonCallingDevAmount', 'm1Hw_nonCallingDevAmount', 'm2Hw_nonCallingDevAmount', 'd0An_nonCallingDevAmount', 'm0An_nonCallingDevAmount', 'm1An_nonCallingDevAmount', 'm2An_nonCallingDevAmount', 'appType_id'];

        $extraColumns = ['d0Hw_nonCallingDevAmount', 'm0Hw_nonCallingDevAmount', 'm1Hw_nonCallingDevAmount', 'm2Hw_nonCallingDevAmount', 'd0An_nonCallingDevAmount', 'm0An_nonCallingDevAmount', 'm1An_nonCallingDevAmount', 'm2An_nonCallingDevAmount'];

        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count'],
            'HWActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']],
            'HWNotActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']],
            'notHWActive' => ['name' => 'appType', 'method' => 'count', 'selectBy' => ['lotusId']]
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px', 'visible' => false],
            'office_id' => ['id' => 'officeId','name' => 'office-id', 'width' => '50px', 'visible' => false],
            'appType_id' => ['id' => 'appTypeId','name' => 'appType-id', 'width' => '50px', 'visible' => false],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 24, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 24, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>29, 'sortable' => true, 'filterable' => true],
            'lotus_employees' => ['id' => 'people-v','name' => 'Сотрудников', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active-v','name' => 'HW Phones<br>(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'HWNotActive' => ['id' => 'hw-not-active-v','name' => 'HW Phones<br>(не актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'notHWActive' => ['id' => 'not-hw-active-v','name' => 'virtual & analog<br>Phones(актив.)', 'width' => '60px', 'classes' => ['class_1', 'class_2']],

            'd0Hw_nonCallingDevAmount' => ['id' => 'd0-amount-OfNonCallingHwDev-v','name' => 'Phones HW<br>not used<br>ДЕНЬ тек.', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'm0Hw_nonCallingDevAmount' => ['id' => 'm0-amount-OfNonCallingHwDev-v','name' => 'Phones HW<br>not used<br>МЕСЯЦ тек.', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'm1Hw_nonCallingDevAmount' => ['id' => 'm1-amount-OfNonCallingHwDev-v','name' => 'Phones HW<br>not used<br>1 МЕС. назад', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'm2Hw_nonCallingDevAmount' => ['id' => 'm2-amount-OfNonCallingHwDev-v','name' => 'Phones HW<br>not used<br>2 МЕС. назад', 'width' => '60px', 'classes' => ['class_1', 'class_2']],

            'd0An_nonCallingDevAmount' => ['id' => 'd0-amount-OfNonCallingAnalogDev-v','name' => 'Phones AN<br>not used<br>ДЕНЬ тек.', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'm0An_nonCallingDevAmount' => ['id' => 'm0-amount-OfNonCallingAnalogDev-v','name' => 'Phones AN<br>not used<br>МЕСЯЦ тек.', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'm1An_nonCallingDevAmount' => ['id' => 'm1-amount-OfNonCallingAnalogDev-v','name' => 'Phones AN<br>not used<br>1 МЕС. назад', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
            'm2An_nonCallingDevAmount' => ['id' => 'm2-amount-OfNonCallingAnalogDev-v','name' => 'Phones AN<br>not used<br>2 МЕС. назад', 'width' => '60px', 'classes' => ['class_1', 'class_2']],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'office' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $tablePreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone']);
        $tab = (new PivotTableConfig($tableName, $className));
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
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped", "links-style"])
            ->rowsOnPageList([10,50,100,200,500,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        echo '===============body footer table config===============';
        /*=============body footer table================*/

        $columns = ['textField', 'appType', 'employees', 'phoneAmount', 'HWActive', 'HWNotActive', 'notHWActive', 'd0Hw_total_nonCallingDevAmount', 'm0Hw_total_nonCallingDevAmount', 'm1Hw_total_nonCallingDevAmount', 'm2Hw_total_nonCallingDevAmount', 'd0An_total_nonCallingDevAmount', 'm0An_total_nonCallingDevAmount', 'm1An_total_nonCallingDevAmount', 'm2An_total_nonCallingDevAmount'];

        $extraColumns = ['textField', 'employees', 'd0Hw_total_nonCallingDevAmount', 'm0Hw_total_nonCallingDevAmount', 'm1Hw_total_nonCallingDevAmount', 'm2Hw_total_nonCallingDevAmount', 'd0An_total_nonCallingDevAmount', 'm0An_total_nonCallingDevAmount', 'm1An_total_nonCallingDevAmount', 'm2An_total_nonCallingDevAmount'];
        $confColumns = [
            'textField' => ['id' => 'txt_field','name' => 'ИТОГО:', 'width' => 31, 'visible' => true],
            'appType' => ['id' => 'app_type','name' => 'appType', 'width' => 10, 'visible' => false],
            'employees' => ['id' => 'people-v','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'HWActive' => ['id' => 'hw-active','name' => 'HW Phones', 'width' => '60px'],
            'HWNotActive' => ['id' => 'hw-not-active','name' => 'HW not active Phones', 'width' => '60px'],
            'notHWActive' => ['id' => 'not-hw-active-v','name' => 'not HW Phones', 'width' => '60px'],

            'd0Hw_total_nonCallingDevAmount' => ['id' => 'd0-amount-OfNonCallingHwDev-v', 'width' => '60px'],
            'm0Hw_total_nonCallingDevAmount' => ['id' => 'm0-amount-OfNonCallingHwDev-v', 'width' => '60px'],
            'm1Hw_total_nonCallingDevAmount' => ['id' => 'm1-amount-OfNonCallingHwDev-v', 'width' => '60px'],
            'm2Hw_total_nonCallingDevAmount' => ['id' => 'm2-amount-OfNonCallingHwDev-v', 'width' => '60px'],

            'd0An_total_nonCallingDevAmount' => ['id' => 'd0-amount-OfNonCallingAnalogDev-v', 'width' => '60px'],
            'm0An_total_nonCallingDevAmount' => ['id' => 'm0-amount-OfNonCallingAnalogDev-v', 'width' => '60px'],
            'm1An_total_nonCallingDevAmount' => ['id' => 'm1-amount-OfNonCallingAnalogDev-v', 'width' => '60px'],
            'm2An_total_nonCallingDevAmount' => ['id' => 'm2-amount-OfNonCallingAnalogDev-v', 'width' => '60px'],
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

        /*=======make config================*/
        $tab = (new PivotTableConfig($tableNameBF, $className));
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
            ->cssSetBodyTableClasses(["table", "bg-success", "table-bordered", "body-footer"])
            ->rowsOnPageList([10,50,100,200,'все'])
            ->tablePreFilter($tablePreFilter)
            ->save();
        var_dump($tab);
        die;
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



    public function actionAgentsPhonesStatsByModelsWithBodyFooter()
    {
        $tableName = 'devGeoPivotStatisticByAgentsPhonesWithBodyFooter';
        $tableNameBF = $tableName . 'BF';
        $ajaxHandlersURL = '/report/AgentsPhonesStatsReportHandler.json';
        $className = HdsAgentsPhonesStatView::class;
        $pivotWidthItems = '50px';

        $columns = ['regionTitle', 'cityTitle', 'officeTitle', 'employees', 'hwPhonesActive', 'plPrefix', 'plPlatform', 'officeId'];
        $pivots = [
            'plPrefix' => ['name' => 'prefix'],
            'plPlatform' => ['name' => 'platformTitle']
        ];
        $extraColumns = [];
        $confColumns = [
            'regionTitle' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'cityTitle' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'officeTitle' => ['id' => 'office','name' => 'Офис', 'width' =>25, 'sortable' => true, 'filterable' => true],
            'employees' => ['id' => 'people-v','name' => 'Сотрудников', 'width' => '50px'],
            'hwPhonesActive' => ['id' => 'hw-phone-active-v','name' => 'HW Phones<br>(актив.)', 'width' => '50px', 'classes' => ['class_1', 'class_2']],
            'plPrefix' => ['id' => 'pl-prefix-v','name' => 'stat-by-prefix'],
            'plPlatform' => ['id' => 'pl-platform-v','name' => 'stat-by-platform'],
            'officeId' => ['id' => 'office-id','name' => 'OfficeId', 'visible' => false],
        ];
        $sortTemplates = [
            'region' => ['regionTitle' => '', 'cityTitle' => '', 'officeTitle' => ''],
            'city' => ['cityTitle' => '', 'officeTitle' => ''],
        ];
        $pivotItemsSelectBy = ['officeId'];

        $tab = (new PivotTableConfig($tableName, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
        }

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
            ->pivotItemsSelectBy('plPrefix', $pivotItemsSelectBy)
            ->pivotItemsSelectBy('plPlatform', $pivotItemsSelectBy)
            ->pivotSortBy('plPrefix', ['prefix'], 'asc')
            ->pivotSortBy('plPlatform', ['platformTitle'], 'asc')
            ->pivotWidthItems('plPrefix', $pivotWidthItems)
            ->pivotWidthItems('plPlatform', $pivotWidthItems)
            ->cssSetHeaderTableClasses(['bg-primary', 'table-bordered', 'table-header-rotated'])
            ->cssSetBodyTableClasses(["table", "cell-bordered", "cust-table-striped"])
            ->rowsOnPageList([10,50,100,200,500,'все'])
            ->save();
        var_dump($tab);

        echo '===============body footer table config===============';
        /*=============body footer table================*/

        $columns = ['textField', 'employees', 'hwPhonesActive', 'plPrefix', 'plPlatform', 'officeId', 'applianceType'];
        $pivots = [
            'plPrefix' => ['name' => 'prefix'],
            'plPlatform' => ['name' => 'platformTitle']
        ];

        $extraColumns = ['textField', 'employees'];

        $confColumns = [
            'textField' => ['id' => 'txt_field','name' => 'ИТОГО:', 'width' => 45, 'visible' => true],
            'employees' => ['id' => 'people','name' => 'Сотр.', 'width' => '50px'],
            'hwPhonesActive' => ['id' => 'hw-phone-active','name' => 'HW Phones<br>(актив.)', 'width' => '50px'],
            'plPrefix' => ['id' => 'pl-prefix','name' => 'stat-by-prefix', 'width' => 65],
            'plPlatform' => ['id' => 'pl-platform','name' => 'stat-by-platform', 'width' => 65],
            'officeId' => ['id' => 'office-id', 'visible' => false],
            'applianceType' => ['id' => 'appliance-type', 'visible' => false],
        ];
        $footerPivotItemsSelectBy = ['applianceType'];

        $sortTemplates = [
            'default' => [],
        ];

        /*=======make config================*/
        $tab = (new PivotTableConfig($tableNameBF, $className));
        foreach ($pivots as $alias => $col) {
            $tab->definePivotColumn($col['name'], $alias);
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
            ->pivotItemsSelectBy('plPrefix', $footerPivotItemsSelectBy)
            ->pivotItemsSelectBy('plPlatform', $footerPivotItemsSelectBy)
            ->pivotSortBy('plPrefix', ['prefix'], 'asc')
            ->pivotSortBy('plPlatform', ['platformTitle'], 'asc')
            ->pivotWidthItems('plPrefix', $pivotWidthItems)
            ->pivotWidthItems('plPlatform', $pivotWidthItems)
            ->cssSetBodyTableClasses(["table", "bg-success", "table-bordered", "body-footer"])
            ->rowsOnPageList([10,50,100,200,'все'])
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
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('publisherIp', 'eq', [
                '10.30.30.70',
                '10.30.30.21',
                '10.101.19.100',
                '10.101.15.10'
            ]);
        $pivotPreFilter = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('publisherIp', 'eq', [
                '10.30.30.70',
                '10.30.30.21',
                '10.101.19.100',
                '10.101.15.10'
            ]);
        $pivotPreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('appAge', 'lt', [$maxAge])
            ->addFilter('publisherIp', 'eq', [
                '10.30.30.70',
                '10.30.30.21',
                '10.101.19.100',
                '10.101.15.10'
            ]);
        $pivotPreFilterActiveHW = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->addFilter('appAge', 'lt', [$maxAge])
            ->addFilter('isHW', 'eq', ['true'])
            ->addFilter('publisherIp', 'eq', [
                '10.30.30.70',
                '10.30.30.21',
                '10.101.19.100',
                '10.101.15.10'
            ]);

        $HWPhonePreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', ['true'])
            ->addFilter('appAge', 'lt', [$maxAge])
            ->addFilter('publisherIp', 'eq', [
                '10.30.30.70',
                '10.30.30.21',
                '10.101.19.100',
                '10.101.15.10'
            ]);
        $notHWPhonePreFilterActive = (new SqlFilter($className))
            ->setFilter('appType', 'eq', ['phone'])
            ->setFilter('isHW', 'eq', ['false'])
            ->addFilter('appAge', 'lt', [$maxAge])
            ->addFilter('publisherIp', 'eq', [
                '10.30.30.70',
                '10.30.30.21',
                '10.101.19.100',
                '10.101.15.10'
            ]);

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