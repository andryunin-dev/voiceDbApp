<?php

namespace App\Controllers;




use App\Components\Reports\PivotReport;
use App\Components\Sql\SqlFilter;
use App\Components\Tables\TableConfig;
use App\ViewModels\GeoDev_View;
use T4\Core\Config;
use T4\Core\Std;
use T4\Mvc\Controller;

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
        $tab = new TableConfig('table_1', GeoDev_View::class);
        $tab->columns(['region', 'city', 'office']);
        $sortTemplates = [
            'region' => ['region' => '', 'city' => ''],
            'city' => ['city' => '', 'office' => ''],
        ];
        $tab->sortOrderSets($sortTemplates);
        $tab->sortBy('city');
        $confColumns = [
            'region' => ['width' => 10, 'sortable' => true],
            'city' => ['width' => 20, 'sortable' => true],
            'office' => ['width' => 30, 'sortable' => true],
        ];
        foreach ($confColumns as $col => $conf)
        {
            $tab->columnConfig($col, $conf);
        }

    }

    public function actionTest()
    {

        $obj = new Std();

    }
}