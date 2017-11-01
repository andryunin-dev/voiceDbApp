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
//        $rep->delete();
//        $rep = new PivotReport2('test', GeoDev_View::class);
//        var_dump($rep);
//        var_dump($rep->setReportColumns(['region', 'office', 'platformTitle']));
//        var_dump($rep->setPivotColumn('platformTitle'));
//        var_dump($rep->setPivotFilter(['appType' => 'phone']));
//        var_dump($rep->setReportColumnsFilter([], false));
//        $rep->save();
//        var_dump($rep->pivotColumnValues);
        var_dump($rep->buildSelectQuery());
//        var_dump($rep->reportConfig);
//        var_dump($rep->reportColumns);
//        var_dump($rep->pivotColumn);
//        var_dump($rep->pivotColumnValues);
        die;

    }
}