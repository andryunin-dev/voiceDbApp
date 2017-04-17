<?php

namespace App\Controllers;

use App\Components\CiscoParser;
use App\Components\DataProcessor;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Network;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;
use T4\Core\Exception;
use T4\Core\MultiException;

class RServer extends Controller
{
    public function actionDefault()
    {
        try {
            $rawdata = file_get_contents('php://input');
            var_dump($rawdata);

            $fileName = function () {
                $dir = 'C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\';

                $mt = microtime();
                $mt = explode(' ', $mt);
                $rawmc = explode('.', $mt[0]);

                $datetime = date('YmdGis', $mt[1]);
                $mc = $rawmc[1];

                return $dir . 'item_' . $datetime . $mc . '.json';
            };

            $file = fopen($fileName(), 'w+');
            fwrite($file,$rawdata);
            fclose($file);

        } catch (MultiException $e) {
            $this->data->errors = $e;
        } catch (Exception $e) {
            $this->data->errors = (new MultiException())->add($e);
        }

        die();
    }

    public function actionShow()
    {
        $rawdata = (new Std())->fill(json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_201704139214578446000.json')));
        var_dump($rawdata);

        var_dump(json_last_error_msg());

        die();
    }

    public function actionShowSoftware()
    {
        $rawdata = (new Std())->fill(json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_2017041011131081254400.json')));

        $software = CiscoParser::getSoftware($rawdata->software);

        var_dump($software);
        die();
    }


    /*
     * Тестовый экшен для Data Procesing
     */
    public function actionDP()
    {
        $rawData = (new Std())->fill(json_decode(file_get_contents('php://input')));

        $srcData = new Std();
//        $srcData->ip = '192.168.12.1/24';
        $srcData->ip = $rawData->ip;
        $srcData->LotusId = $rawData->LotusId;
        $srcData->platformVendor = $rawData->platformVendor;
        $srcData->platformTitle = $rawData->platformTitle;
        $srcData->platformSerial = $rawData->platformSerial;
        $srcData->applianceSoft = $rawData->applianceSoft;
        $srcData->softwareVersion = $rawData->softwareVersion;
        $srcData->applianceType = $rawData->applianceType;
        $srcData->applianceModules = $rawData->applianceModules;
        $srcData->hostname = $rawData->hostname;

//        var_dump($srcData);
//        die;

//        $srcData = (new Std())->fill(json_decode('{
//            "ip" : "192.168.10.1/24",
//            "officeLotusId" : "2",
//            "platformVendor" : "vend 433455",
//            "platformTitle" : "platform233233dgf111",
//            "platformSerial" : "w*Kjh4444-111",
//            "applianceSoft" : "sofftt 9993",
//            "softwareVersion" : "3kjHK45Illh",
//            "applianceType" : "Role3",
//            "hostname" : "hostname"
//        }'));

        // В БД ОТСУТСТВУЕТ устройство с заданым IP адресом
        echo 'Create new Appliance';

        try {
            $office = Office::findByLotusId($srcData->LotusId);
            if (empty($office)) {
                throw new Exception('Неверный Lotus ID');
                die;
            }

            $vendor = Vendor::getByTitle($srcData->platformVendor);
            $platform = Platform::getByVendor($vendor, $srcData->platformTitle);
            $platformItem = PlatformItem::getByPlatform($platform, $srcData->platformSerial);
            $software = Software::getByVendor($vendor, $srcData->applianceSoft);
            $softwareItem = SoftwareItem::getBySoftware($software, $srcData->softwareVersion);
            $applianceType = ApplianceType::getByType($srcData->applianceType);

//            var_dump(DPortType::findByPK(2));
//            die;
//            var_dump($srcData->ip);
//            die;

//            $p = (new DataPort())
//                ->fill([
//                    'appliance' => Appliance::findByPK(40),
//                    'ipAddress' => $srcData->ip,
//                    'portType' => DPortType::findByPK(2),
//                ]);
//            var_dump($p);
//            die;


//            DataPort::getDbConnection()->beginTransaction();
//            $p = (new DataPort())
//                ->fill([
//                    'appliance' => Appliance::findByPK(40),
//                    'ipAddress' => $srcData->ip,
//                    'portType' => DPortType::findByPK(2),
//                ])
////            var_dump($p);
////            die;
//                ->save();
//            DataPort::getDbConnection()->commitTransaction();
//
//            die;

            Appliance::getDbConnection()->beginTransaction();
            $appliance = (new Appliance())
                ->fill([
                    'location' => $office,
                    'vendor' => $vendor,
                    'platform' => $platformItem,
                    'software' => $softwareItem,
                    'type' => $applianceType,
                    'details' => [
                        'hostname' => $srcData->hostname
                    ]
                ])
                ->save();
            Appliance::getDbConnection()->commitTransaction();


            ModuleItem::getDbConnection()->beginTransaction();
            if (!empty($srcData->applianceModules)) {
                foreach ($srcData->applianceModules as $module) {

                    $moduleTitle = Module::getByVendorAndTitle($vendor, $module->product_number, $module->description);
                    (new ModuleItem())
                        ->fill([
                            'appliance' => $appliance,
                            'module' => $moduleTitle,
                            'serialNumber' => $module->serial,
                            'details' => [
                                'slot' => $module->slot
                            ],
                        ])
                        ->save();
                }
            }
            ModuleItem::getDbConnection()->commitTransaction();

            DataPort::getDbConnection()->beginTransaction();
            (new DataPort())
                ->fill([
                    'appliance' => $appliance,
                    'ipAddress' => $srcData->ip,
                    'portType' => DPortType::findByPK(5),
                ])
                ->save();
            DataPort::getDbConnection()->commitTransaction();



        } catch (MultiException $e) {
            Appliance::getDbConnection()->rollbackTransaction();
        } catch (Exception $e) {
            Appliance::getDbConnection()->rollbackTransaction();
        }

        die;


// Начало логики обработки
//        $dataPort = DataPort::findByIp($srcData->ip);

//        var_dump($dataPort);
//        die;

//        // В БД ЕСТЬ устройство с заданым IP адресом
//        if (!empty($dataPort)) {
//
//            $appliance = (DataPort::findByIp($srcData->ip))->appliance;
//
//            // Это ТЕКУЩЕЕ устройство связанное с IP адресом в БД
//            if ($srcData->platformSerial == $appliance->platform->serialNumber) {
//                echo 'Current Appliance';
//                // Обновим данные устройства в БД
//
//                var_dump($appliance);
//
//                die();
//            }
//
//            // Это ДРУГОЕ устройство, не связанное с этим IP адресом, но существующее в БД
//            echo 'Another Appliance';
//            // Удалить IP адрес у Current Appliance (определить его по текущему IP адресу)
//            // Изменить IP адрес для Another Appliance
//            // Изменить Office по IP адресу для Another Appliance
//
//            die();
//        }
    }

}
