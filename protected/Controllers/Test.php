<?php

namespace App\Controllers;




use App\Components\Reports\PivotReport;
use App\ViewModels\GeoDev_View;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        $rep = new PivotReport('test', GeoDev_View::class);
        $rep->setPivotColumn('platformTitle', 'citext');
        $rep->setRowNamesColumn('office', 'citext');
        $rep->setRowNamesColumn('office', 'citext');
        $rep->setPivotFilter(['appType_id' => 3]);
        $rep->setValueColumn('appliance_id', 'citext', 'sum');
        $rep->setExtraColumns(['region' => 'citext', 'test' => 'type']);
        $rep->save();
        var_dump($rep->buildSourceSql());die;
        var_dump($rep->extraColumnsNames);
        var_dump($rep->pivotColumnValues);
        $rep->save();
        die;
    }
}