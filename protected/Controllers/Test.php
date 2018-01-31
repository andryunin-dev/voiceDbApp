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
        $tbConf = Table::getTableConfig('devGeoPivotStatisticWithBodyFooter');
        $tb = Table::getTable($tbConf);
        $res = $tb->getRecords(null,null,null,true);

//        $res = array_reduce($res, function ($carry, $item) {
//            $appDetails = json_decode($item['appDetails'], true);
//            $cucmName = isset($appDetails['reportName']) ? $appDetails['reportName'] : null;
//            $carry[$item['managementIp']] = $cucmName;
//            return $carry;
//        });

        var_dump($res);
        die;
    }
}