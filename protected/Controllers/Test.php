<?php

namespace App\Controllers;




use App\Components\Reports\PivotReport2;
use App\ViewModels\GeoDev_View;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        $rep = new PivotReport2('test', GeoDev_View::class);
        $rep->delete();
        $rep = new PivotReport2('test', GeoDev_View::class);
        $rep->setReportColumns(['region', 'office', 'platformTitle']);
        $rep->setPivotColumn('platformTitle');
        $rep->setPivotFilter(['appType' => 'phone']);
        $rep->setReportColumnsFilter([], true);
        $rep->save();
        var_dump($rep->pivotColumnValues);
        die;

    }
}