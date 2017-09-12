<?php

namespace App\Controllers;


use App\Components\ContentFilter;
use App\Components\Paginator;
use App\Components\Sorter;
use App\Components\TableFilter;
use App\ViewModels\DevModulePortGeo;
use T4\Core\Std;
use T4\Core\Url;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        $hrefFilter = [
            'href' => null,
            'reg' => [
                'eq' => 'Hreg_1',
                'like' => 'Hreg_2'
            ],
            'city' => [
                'eq' => 'Hcity 1, Hcity2, Hcity 3',
                'like' => 'Hcity like'
            ],
            'soft' => [
                'eq' => 123,
                'like' => 'Hsoft like'
            ]
        ];
        $tabFilter = [
            'reg' => [
                'eq' => 'Treg_1',
                'like' => 'Treg_2'
            ],
            'city' => [
                'eq' => 'Tcity 1, Tcity2, Tcity 3',
                'like' => 'Tcity like'
            ],
            'soft' => [
                'eq' => 123,
                'like' => 'Tsoft like'
            ]
        ];
        $sort = [
            'col1',
            'col2',
            'col3',
        ];
        var_dump(new Paginator());die;
    }
}