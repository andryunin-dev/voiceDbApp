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
        $this->data->phone = '123456789';
//        $column = 'string';
//        $column = preg_split("/\s+/", $column, -1, PREG_SPLIT_NO_EMPTY);
//
//        var_dump($column);

    }
}