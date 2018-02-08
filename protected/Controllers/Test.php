<?php

namespace App\Controllers;




use App\Components\ContentFilter;
use App\Components\Paginator;
use App\Components\Reports\PivotReport;
use App\Components\Sorter;
use App\Components\Sql\SqlFilter;
use App\Components\Tables\PivotTable;
use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\RecordItem;
use App\Components\Tables\Table;
use App\Components\Tables\TableConfig;
use App\Models\Appliance;
use App\Models\DPortType;
use App\Models\LotusLocation;
use App\Models\Office;
use App\ViewModels\DevGeo_View;
use App\ViewModels\DevGeoPeople_1;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\GeoDev_View;
use App\ViewModels\GeoDevStat;
use App\ViewModels\LotusDbData;
use T4\Core\Collection;
use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Core\Url;
use T4\Dbal\Connection;
use T4\Http\Request;
use T4\Mvc\Controller;
use T4\Orm\Model;

class Test extends Controller
{
    public function actionDefault()
    {
        $array = [
            'active' => [
                't1' => 'v1',
                't2' => 'v2',
                't3' => 'v3',
            ],
            'passive' => [
                't1' => 'vp1',
                't2' => 'vp2',
                't3' => 'vp3',
            ]
        ];
        $array2 = [
            'passive' => [
                'tp1' => 'vp1',
                'tp2' => 'vp2',
                'tp3' => 'vp3',
            ]
        ];

        array_walk($array['active'], function (&$item, $key) use (&$array) {
            $item .= '/' . $array['passive'][$key];
        });

        die;
    }
}