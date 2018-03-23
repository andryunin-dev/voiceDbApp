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
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;
use T4\Orm\Model;

class Test extends Controller
{
    public function actionDefault()
    {
        $test = new Collection();
        $test->append('123');
        var_dump($test);die;
    }
    public function actionDeleteVeryOldAnalogPhones()
    {
        $query = (new Query())
            ->select(['appliance_id', 'appAge', 'platformSerial'])
            ->from(DevGeo_View::getTableName())
            ->where('"appType" = :appType AND ("platformTitle" = :platform_title_1 OR "platformTitle" = :platform_title_2) AND "appAge" > 300')
            ->params([
                ':appType' => 'phone',
                ':platform_title_1' => 'Analog Phone',
                ':platform_title_2' => 'VGC Phone',
            ]);

        $res = DevGeo_View::findAllByQuery($query);
        $counter = 0;
        foreach ($res as $dev) {
            $item = Appliance::findByPK($dev->appliance_id);
            if ($item instanceof Appliance) {
                $item->delete();

                echo ++$counter . ' - ' . $item->platform->platform->title . ' - has been deleted' . "\n";
            }
        }
    }
}