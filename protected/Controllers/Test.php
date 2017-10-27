<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\ContentFilters\HeaderFilter;
use App\Components\Reports\PivotReport;
use App\Components\TableFilterNew;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\Geo_View;
use App\ViewModels\GeoDev;
use App\ViewModels\GeoDev_View;
use App\ViewModels\GeoDevStat;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use T4\Core\Std;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        $rep = new PivotReport('test', GeoDev_View::class);
        $rep->setPivotColumn('platformTitle', 'citext');
        $rep->setRowNamesColumn('office', 'citext');
        $rep->setRowNamesColumn('office', 'citext');
        $rep->setPivotFilter(['appType' => 'phone']);
        $rep->setValueColumn('appliance_id', 'citext', 'sum');
        $rep->setExtraColumn(['region' => 'citext', 'test' => 'type']);
        $rep->save();
        var_dump($rep);
        var_dump($rep->pivotColumnNames);
        $rep->save();
        die;

//        var_dump(GeoDev_View::reportConfig());
        GeoDev_View::saveReportConf();
        var_dump(GeoDev_View::pivotColumnNames());
        die;

        GeoDev_View::setRowNamesColumn('office', 'citext');
        GeoDev_View::setPivotColumn('platformTitle', 'citext');
        GeoDev_View::setValueColumn('platformItem_id', 'citext');
        GeoDev_View::setValueCountMethod('count');
        $var = GeoDev_View::reportColumns();
        var_dump($var);

        die;

    }
}