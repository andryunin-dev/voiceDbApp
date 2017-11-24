<?php

namespace App\Controllers;




use App\Components\Reports\PivotReport;
use App\Components\Sql\SqlFilter;
use App\Components\Tables\Table;
use App\Components\Tables\TableConfig;
use App\Models\Appliance;
use App\Models\DPortType;
use App\ViewModels\DevModulePortGeo;
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
        $columns = ['region', 'city', 'office', 'hostname_dn', 'appType', 'platformTitle'];
        $confColumns = [
            'region' => ['title' => 'Регион', 'width' => 10, 'sortable' => true],
            'city' => ['title' => 'Город', 'width' => 10, 'sortable' => true],
            'office' => ['title' => 'Оффисе', 'width' => 20, 'sortable' => true],
            'hostname_dn' => ['title' => 'hostname', 'width' => 20],
            'appType' => ['title' => 'Тип', 'width' => '70px'],
            'platformTitle' => ['title' => 'Оборудование', 'width' => 20],
        ];
        $sortTemplates = [
            'region' => ['region' => '', 'city' => '', 'appSortOrder' => 'desc'],
            'city' => ['city' => '', 'office' => '', 'appSortOrder' => 'desc'],
        ];
        $preFilter = (new SqlFilter(DevModulePortGeo::class))
            ->setFilter('appType', 'eq', ['phone']);
        //ToDO ability to add extra columns that haven't in class
        $tab = (new TableConfig('deviceInfo', DevModulePortGeo::class))
            ->dataUrl('voice.rs.ru/dataUrl')
            ->tableWidth(100)
            ->columns($columns)
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

    public function actionTest()
    {

    }
}