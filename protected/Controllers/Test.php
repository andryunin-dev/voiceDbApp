<?php

namespace App\Controllers;

use App\Components\ContentFilter;
use App\Components\ContentFilters\HeaderFilter;
use App\Components\TableFilterNew;
use App\ViewModels\DevModulePortGeo;
use T4\Core\Std;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
    $test = new Std();
    $prop1 = 'reg';
    $prop2 = 'eq';
    $val = ['a'];
    $test
        ->fill([
            $prop1 => (new Std())
        ]);
    $test->$prop1
        ->fill([
            $prop2 => $val
        ]);
    var_dump($test);
        die;
    }
}