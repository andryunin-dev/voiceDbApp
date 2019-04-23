<?php

namespace App\Controllers;

use App\Components\IpTools;
use App\ConsolidationTablesModels\ConsolidationTable_1;
use App\MappingModels\LotusLocation;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\DataPort;
use App\Models\Network;
use App\Models\Network2;
use App\Models\Vrf;
use App\ViewModels\ApiView_Geo;
use App\ViewModels\DevGeo_View;
use T4\Core\Std;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\MappedLocations_View;
use T4\Core\Collection;
use T4\Dbal\Query;
use T4\Mvc\Controller;
use T4\Mvc\Route;

class Test extends Controller
{
    public function actionNetTest()
    {
        $vrf = Vrf::instanceGlobalVrf();
        var_dump(DataPort::findByIpVrf('10.102.66.52', $vrf));
        var_dump(DataPort::findAllByIpVrf('10.102.66.52', $vrf));
        var_dump(DataPort::countByIpVrf('10.102.66.52', $vrf));
        die;
    }
    public function actionTestIp()
    {
        try {
            $vrf = Vrf::findByPK(1);
    
            $net = Network::findByAddressVrf('10.1.1.0/24', $vrf);
            $net->delete();
            $net = (new Network())->fill(['address' => '10.1.1.0/24', 'comment' => 'test' ]);
            $net->vrf = $vrf;
            $net->save();
//            $net->address = '10.1.3.0/24';
//            $net->delete();
            $children = $net->children;
            var_dump($children);
            die;
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }

//        $vrf = Vrf::findByPK(1);
//        $net->fill([
//            'address' => '10.1.0.0/16',
//            'comment' => '',
//            'vrf' => $vrf
//        ]);
//        $net->save();
    }
    
    public function actionJson()
    {
        
        $jstring = '
        {  
            "Person":[  
               "name",
               "age"
            ],
            "Device":{  
               "Platform":[  
                  "sn",
                  "inv"
               ],
               "Software":[  
                  "ver"
               ]
        }
}
        ';
        echo $jstring;
        var_dump(json_decode($jstring));
        die;
    }
    
    public function actionTestApi()
    {
        $query = (new Query())
            ->select(['location_id', 'office'])
            ->from(ApiView_Geo::getTableName())
//            ->where(join(' AND ', $condition))
            ->group('location_id, office')
            ->order('"office"');
        $res = ApiView_Geo::findAllByQuery($query)->toArrayRecursive();
        $this->data->res = $res;
    }
    
    public function actionConsolidationSource()
    {
        $MAX_AGE = 73;
        $WORKING = 'В работе';
        $WAS_WORKING = 'Был в работе';
        $SHOULD_BE_RETURNED = 'К возврату';
        $WRITTEN_OFF = 'Списан';
        $INV_AND_SN = 'Инв и S/N';
        $INV_WO_SN = 'Инв без S/N';
        $SN_WO_INV = 'S/N без Инв';
        $WO_SN_WO_INV = ''; //??без инв и сер
        $COMPARED = 'совпадает';
        $NOT_COMPARED = 'не совпадает';
        $ON_BALANCE = 'На балансе';
        
        $locationMap = $this->createLocationMapArray();
        $params = [':list_number' => 1];
        $sql = 'SELECT * FROM view.consolidation_excel_table_src t1 WHERE t1."listNumber_1c" = :list_number OR t1."listNumber_voice" = :list_number ORDER BY "invNumber",  "lotusId_voice"';
        $res = ConsolidationTable_1::getDbConnection()->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($res as $key => $item) {
            $item['molTabNumber_1c'] = (is_numeric($item['molTabNumber_1c']) && ($item['molTabNumber_1c'] < 0)) ? '' : $item['molTabNumber_1c'];
            $res[$key]['molTabNumber_1c'] = $item['molTabNumber_1c'];
            $writtenOff = empty($item['molTabNumber_1c']) && empty($item['roomCode_1c']);
            $registeredInVoice = !empty($item['dev_id']);
            $emptyAge = empty($item['dev_age']);
            $active = $registeredInVoice && !$emptyAge && $item['dev_age'] < $MAX_AGE;
            $sn = empty($item['invNumber']) ? $item['serialNumber'] : $item['serialNumber_1c'];
            $res[$key]['res_sn'] = $sn;


//          //if device active, get location data via lotusId_voice
//          //if device isn't active, get location data via lotusId_1c
//            //$lotusId = (!empty($item['dev_age']) && $item['dev_age'] < $MAX_AGE) ? $item['lotusId_voice'] : $item['lotusId_1c'];
//            choosing of lotusId for location info
            if (empty($item['invNumber'])) {
                $lotusId = $item['lotusId_voice'];
            } else {
                if ($registeredInVoice) { // dev is registered in voice
                    $lotusId = $active ? $item['lotusId_voice'] : $item['lotusId_1c'];
                } else { // dev isn't registered in voice
                    $lotusId = $item['lotusId_1c'];
                }
            }
//          == field "Lotus_id по Реальному адресу"
//            set as lotusId_voice only if dev is active or dev doesn't have inv number
            $item['lotusId_voice'] = $active || empty($item['invNumber']) ? $item['lotusId_voice'] : null;
            $res[$key]['lotusId_voice'] = $item['lotusId_voice'];

//          ==field "1C адрес и Реальный"
            if (empty($item['lotusId_voice']) || empty($item['lotusId_1c'])) {
                $res[$key]['compareLotusId'] = null;
            } else {
                $res[$key]['compareLotusId'] = ($item['lotusId_voice'] == $item['lotusId_1c']) ? $COMPARED : $NOT_COMPARED;
            }
//          ==Fields of location info
            if (array_key_exists($lotusId, $locationMap)) {
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
//                    $res[$key]['status'] = $active ? $WORKING : ($emptyAge ? '' : $WAS_WORKING);
                    $res[$key]['status'] = $active ? $WORKING : $WAS_WORKING;
                } else {
                    $res[$key]['status'] = null;
                }
            } else {
                if ($writtenOff) {
//                    is written-off
//                    but is registered in voice DB
                    $res[$key]['status'] = empty($item['dev_id']) ? null : ($active ? $WORKING : null);
                } else {
                    //                    isn't written-off
                    if (empty($item['serialNumber_1c']) || !$registeredInVoice || $emptyAge || $item['dev_age'] >= $MAX_AGE) {
                        $res[$key]['status'] = $SHOULD_BE_RETURNED;
                    } else {
                        $res[$key]['status'] = $WORKING;
                    }
                }
            }
//            filling "списан" and "соотв. Инв. и SN"
            if (empty($item['invNumber'])) {
                $res[$key]['writtenOff'] = null;
                $res[$key]['invNum_SN'] = empty($item['serialNumber']) ? $WO_SN_WO_INV : $SN_WO_INV;
            } else {
                $res[$key]['writtenOff'] = $writtenOff ? $WRITTEN_OFF : $ON_BALANCE;
                $res[$key]['invNum_SN'] = empty($item['serialNumber_1c']) ? $INV_WO_SN : $INV_AND_SN;
            }
            
        }

//        var_dump($res);die;
        $this->data->res = $res;
//        var_dump($res);die;
    }
    
    public function actionPhonesConsolidationSource()
    {
        ini_set('memory_limit', '256M');
        $MAX_AGE = 73;
        $WORKING = 'В работе';
        $WAS_WORKING = 'Был в работе';
        $SHOULD_BE_RETURNED = 'К возврату';
        $WRITTEN_OFF = 'Списан';
        $INV_AND_SN = 'Инв и S/N';
        $INV_WO_SN = 'Инв без S/N';
        $SN_WO_INV = 'S/N без Инв';
        $WO_SN_WO_INV = ''; //??без инв и сер
        $COMPARED = 'совпадает';
        $NOT_COMPARED = 'не совпадает';
        $ON_BALANCE = 'На балансе';
        
        $locationMap = $this->createLocationMapArray();
        $params = [':list_number' => 2];
        $sql = 'SELECT * FROM view.consolidation_excel_table_src t1 WHERE t1."listNumber_1c" = :list_number OR t1."listNumber_voice" = :list_number ORDER BY "invNumber",  "lotusId_voice"';
        $res = ConsolidationTable_1::getDbConnection()->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($res as $key => $item) {
            $item['molTabNumber_1c'] = (is_numeric($item['molTabNumber_1c']) && ($item['molTabNumber_1c'] < 0)) ? '' : $item['molTabNumber_1c'];
            $res[$key]['molTabNumber_1c'] = $item['molTabNumber_1c'];
            $writtenOff = empty($item['molTabNumber_1c']) && empty($item['roomCode_1c']);
            $registeredInVoice = !empty($item['dev_id']);
            $emptyAge = empty($item['dev_age']);
            $active = $registeredInVoice && !$emptyAge && $item['dev_age'] < $MAX_AGE;
            $sn = empty($item['invNumber']) ? $item['serialNumber'] : $item['serialNumber_1c'];
            $res[$key]['res_sn'] = $sn;
            if ($item['molTabNumber_1c'] > 0 && !is_null($item['invUserTabNumber'])) {
                $res[$key]['molIsUser'] = $item['molTabNumber_1c'] === $item['invUserTabNumber'] ? 1 : 0;
            } else {
                $res[$key]['molIsUser'] = null;
            }
//          //if device active, get location data via lotusId_voice
//          //if device isn't active, get location data via lotusId_1c
//            //$lotusId = (!empty($item['dev_age']) && $item['dev_age'] < $MAX_AGE) ? $item['lotusId_voice'] : $item['lotusId_1c'];
//            choosing of lotusId for location info
            if (empty($item['invNumber'])) {
                $lotusId = $item['lotusId_voice'];
            } else {
                if ($registeredInVoice) { // dev is registered in voice
                    $lotusId = $active ? $item['lotusId_voice'] : $item['lotusId_1c'];
                } else { // dev isn't registered in voice
                    $lotusId = $item['lotusId_1c'];
                }
            }
//          == field "Lotus_id по Реальному адресу"
//            set as lotusId_voice only if dev is active or dev doesn't have inv number
            $item['lotusId_voice'] = $active || empty($item['invNumber']) ? $item['lotusId_voice'] : null;
            $res[$key]['lotusId_voice'] = $item['lotusId_voice'];

//          ==field "1C адрес и Реальный"
            if (empty($item['lotusId_voice']) || empty($item['lotusId_1c'])) {
                $res[$key]['compareLotusId'] = null;
            } else {
                $res[$key]['compareLotusId'] = ($item['lotusId_voice'] == $item['lotusId_1c']) ? $COMPARED : $NOT_COMPARED;
            }
//          ==Fields of location info
            if (array_key_exists($lotusId, $locationMap)) {
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
//                    $res[$key]['status'] = $active ? $WORKING : ($emptyAge ? '' : $WAS_WORKING);
                    $res[$key]['status'] = $active ? $WORKING : $WAS_WORKING;
                } else {
                    $res[$key]['status'] = null;
                }
            } else {
                if ($writtenOff) {
//                    is written-off
//                    but is registered in voice DB
                    $res[$key]['status'] = empty($item['dev_id']) ? null : ($active ? $WORKING : null);
                } else {
                    //                    isn't written-off
                    if (empty($item['serialNumber_1c']) || !$registeredInVoice || $emptyAge || $item['dev_age'] >= $MAX_AGE) {
                        $res[$key]['status'] = $SHOULD_BE_RETURNED;
                    } else {
                        $res[$key]['status'] = $WORKING;
                    }
                }
            }
//            filling "списан" and "соотв. Инв. и SN"
            if (empty($item['invNumber'])) {
                $res[$key]['writtenOff'] = null;
                $res[$key]['invNum_SN'] = empty($item['serialNumber']) ? $WO_SN_WO_INV : $SN_WO_INV;
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
        var_dump($res->toArray());
        die;
    }
    
    public function actionGetPhone()
    {
        
        $name = '';
        $cmd = 'php ' . ROOT_PATH . DS . 'protected' . DS . 't4.php cucmsPhones' . DS . 'getPhoneByName --name=' . $name;
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