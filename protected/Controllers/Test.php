<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\ContentFilters\HeaderFilter;
use App\Components\TableFilterNew;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\Geo_View;
use App\ViewModels\GeoDevStat;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use T4\Core\Std;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        $var = GeoDevStat::findAll()->slice(0,3);
        var_dump($var);
//        foreach ($var as $office) {
//            foreach ($office->devStatistics as $type) {
//                var_dump($type->appType);
//            }
//        }
        die;

    }
}