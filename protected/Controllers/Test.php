<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\ContentFilters\HeaderFilter;
use App\Components\TableFilterNew;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\GeoPeople_View;
use T4\Core\Std;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        var_dump($test = GeoPeople_View::findByColumn('lotusId', 10));
        var_dump($test->officeComment);
        die;

    }
}