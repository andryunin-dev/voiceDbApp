<?php

namespace App\Controllers;

use App\Components\Sql\SqlFilter;
use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\TableConfig;
use App\Models\LotusLocation;
use App\ViewModels\DevGeo_View;
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
            'plTitle' => ['name' => 'platformTitle', 'display' => true],
            'plTitleActive' => ['name' => 'platformTitle', 'display' => false]];
        $extraColumns = ['people'];
        $countedColumns = [
            'phoneAmount' => ['name' => 'appliance_id', 'method' => 'count']
        ];
        $confColumns = [
            'lotusId' => ['id' => 'lot_id','name' => 'ID', 'width' => '50px'],
            'region' => ['id' => 'region','name' => 'Регион', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'city' => ['id' => 'city','name' => 'Город', 'width' => 10, 'sortable' => true, 'filterable' => true],
            'office' => ['id' => 'office','name' => 'Офис', 'width' =>15, 'sortable' => true, 'filterable' => true],
            'people' => ['id' => 'people','name' => 'Сотр.', 'width' => '60px'],
            'phoneAmount' => ['id' => 'phone-count','name' => 'кол-во тел.', 'width' => '60px'],
            'plTitle' => ['id' => 'pl','name' => 'Оборудование', 'width' => 65],
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
            $tab->definePivotColumn($col['name'], $alias, $col['display']);
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