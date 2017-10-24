<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\ContentFilters\HeaderFilter;
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
        GeoDev_View::setRowNamesColumn('office', 'citext');
        GeoDev_View::setPivotColumn('platformTitle', 'citext');
        GeoDev_View::setValueColumn('platformItem_id', 'citext');
        GeoDev_View::setValueCountMethod('count');
        $var = GeoDev_View::reportColumns();
        var_dump($var);

        die;

    }
}