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
use App\ViewModels\DevGeo_View;
use App\ViewModels\DevGeoPeople_1;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\GeoDev_View;
use App\ViewModels\GeoDevStat;
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
        $selectByColumns = ['one'];
        $mainTableNameAlias = 't1';

        $linkWithMainTable = array_map(function ($item) use ($mainTableNameAlias) {
            return $mainTableNameAlias . '.' . $item;
        }, $selectByColumns);

        var_dump($linkWithMainTable);
        die;
    }
}