<?php

namespace App\Controllers;

use App\ConsolidationTablesModels\ConsolidationTable_1;
use App\MappingModels\LotusLocation;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\ViewModels\DevGeo_View;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\MappedLocations_View;
use T4\Core\Collection;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Test extends Controller
{

    public function actionConsolidationSource()
    {
        $MAX_AGE = 400;
        $WORKING = 'в работе';
        $WAS_WORKING = 'был в работе';
        $SHOULD_BE_RETURNED = 'к возврату';
        $WRITTEN_OFF = 'списан';
        $INV_AND_SN = 'Инв. и S/N';
        $INV_WO_SN = 'Инв без S/N';
        $SN_WO_INV = 'S/N без Инв';
        $WO_SN_WO_INV = '??без инв и сер';
        $COMPARED = 'совпадает';
        $NOT_COMPARED = 'не совпадает';
        $ON_BALANCE = 'на балансе';
        
        $locationMap = $this->createLocationMapArray();
        $params = [':list_number' => 1];
        $sql = 'SELECT * FROM view.consolidation_excel_table_src t1 WHERE t1."listNumber_1c" = :list_number OR t1."listNumber_voice" = :list_number ORDER BY "invNumber",  "lotusId_voice"';
        $res = ConsolidationTable_1::getDbConnection()->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($res as $key => $item ) {
            $item['molTabNumber_1c'] = (is_numeric($item['molTabNumber_1c']) && ($item['molTabNumber_1c'] < 0)) ? '' : $item['molTabNumber_1c'];
            $res[$key]['molTabNumber_1c'] = $item['molTabNumber_1c'];
            $writtenOff = empty($item['molTabNumber_1c']) && empty($item['roomCode_1c']);
            $registeredInVoice = !empty($item['dev_id']);
            $active = $registeredInVoice && $item['dev_age'] < $MAX_AGE;
            $sn = empty($item['invNumber']) ? $item['serialNumber'] : $item['serialNumber_1c'];
            $res[$key]['res_sn'] = $sn;
//          if device active, get location data via lotusId_voice
//          if device isn't active, get location data via lotusId_1c
            $lotusId = (!empty($item['dev_age']) && $item['dev_age'] < $MAX_AGE) ? $item['lotusId_voice'] : $item['lotusId_1c'];
//            filling result of comparison lotus IDs
            if (empty($item['lotusId_voice']) || empty($item['lotusId_1c'])) {
                $res[$key]['compareLotusId'] = null;
            } else {
                $res[$key]['compareLotusId'] = ($item['lotusId_voice'] == $item['lotusId_1c']) ? $COMPARED : $NOT_COMPARED;
            }
//            filling location info
            if (array_key_exists($lotusId,$locationMap)) {
                $res[$key]['regCenter'] = $locationMap[$lotusId]['regCenter'];
                $res[$key]['region'] = $locationMap[$lotusId]['region'];
                $res[$key]['city'] = $locationMap[$lotusId]['city'];
                $res[$key]['office'] = $locationMap[$lotusId]['office'];
            } else {
                $res[$key]['regCenter'] = null;
                $res[$key]['region'] = null;
                $res[$key]['city'] = null;
                $res[$key]['office'] = null;
            }
//            filling соответствие Инв. номера и SN
            if (!$item['invNumber']) {
                if ($registeredInVoice) {
                    $res[$key]['status'] = $active ? $WORKING : $WAS_WORKING;
                }
                $res[$key]['status'] = null;
            } else {
                if ($writtenOff) {
//                    is written-off
//                    but is registered in voice DB
                    $res[$key]['status'] = empty($item['dev_id']) ? null : ($active ? $WORKING : null);
                } else {
                    //                    isn't written-off
                    if (empty($item['serialNumber_1c']) || empty($item['dev_id']) || $item['dev_age'] >= $MAX_AGE) {
                        $res[$key]['status'] = $SHOULD_BE_RETURNED;
                    } else {
                        $res[$key]['status'] = $WORKING;
                    }
                }
            }
//            filling "списан" and "соотв. Инв. и SN"
            if (empty($item['invNumber'])) {
                $res[$key]['writtenOff'] = null;
                $res[$key]['invNum_SN'] = empty($item['serialNumber_1c']) ? $WO_SN_WO_INV : $SN_WO_INV;
            } else {
                $res[$key]['writtenOff'] = $writtenOff ? $WRITTEN_OFF : $ON_BALANCE;
                $res[$key]['invNum_SN'] = empty($item['serialNumber_1c']) ? $INV_WO_SN : $INV_AND_SN;
            }
            
        }
        
//        var_dump($res);die;
        $this->data->res = $res;
//        var_dump($res);die;
    }
    
    protected function createLocationMapArray()
    {
        $viewData = MappedLocations_View::findAll();
        $mappingArray = [];
        foreach ($viewData as $item) {
            $mappingArray[$item->lotus_id]['regCenter'] = $item->regCenter;
            $mappingArray[$item->lotus_id]['region'] = $item->region;
            $mappingArray[$item->lotus_id]['city'] = $item->city;
            $mappingArray[$item->lotus_id]['office'] = $item->office;
            $mappingArray[$item->lotus_id]['address'] = $item->address;
            $mappingArray[$item->lotus_id]['comment'] = $item->comment;
        }
        return $mappingArray;
    }
    
    public function actionLotusLocation()
    {
        $res = LotusLocation::findAll();
        var_dump($res->toArray());die;
    }
    public function actionGetPhone()
    {

        $name = '';
        $cmd = 'php '.ROOT_PATH.DS.'protected'.DS.'t4.php cucmsPhones'.DS.'getPhoneByName --name='. $name;
        exec($cmd, $result);

        var_dump($result);

        die;
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