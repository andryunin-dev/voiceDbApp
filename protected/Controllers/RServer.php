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
use T4\Core\Collection;
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
        $rawdata = (new Std())->fill(json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_2017041712054414798300.json')));
        var_dump($rawdata);

        var_dump(json_last_error_msg());

        die();
    }

    public function actionTest()
    {
        $appliance = new Appliance();

        var_dump($appliance);

        die;
    }


    /*
     * Тестовый экшен для Data Procesing
     */
    public function actionDP()
    {

        $rawData = (new Std())->fill(json_decode(file_get_contents('php://input')));

        $srcData = new Std();
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

        $query = (new Query())
            ->select()
            ->from(PlatformItem::getTableName())
            ->where('"serialNumber" = :serialNumber')
            ->params([':serialNumber' => $srcData->platformSerial]);

        $appliance = (PlatformItem::findByQuery($query))->appliance;

        /*
         * Известное устройство - обновляем его данные в БД
         */
        if (false != $appliance) {
            echo 'Current Appliance   ';

            // Добавляем новые модули
            if (!empty($srcData->applianceModules)) {
                try {
                    ModuleItem::getDbConnection()->beginTransaction();
                    foreach ($srcData->applianceModules as $newModule) {

                        if (
                            !empty($newModule->serial) &&
                            !empty($newModule->product_number) &&
                            !$appliance->modules->existsElement(['serialNumber' => $newModule->serial])
                        ) {

                            $module = Module::getByVendorAndTitle($appliance->vendor, $newModule->product_number, $newModule->description);

                            // TODO: Добавить проверку на существование ModuleItem в БД - если ЕСТЬ, то меняем ему Appliance

                            (new ModuleItem())
                                ->fill([
                                    'appliance' => $appliance,
                                    'module' => $module,
                                    'serialNumber' => $newModule->serial,
                                ])
                                ->save();
                        }
                    }

                    // TODO: Удаление из БД удаленных модулей

                    ModuleItem::getDbConnection()->commitTransaction();

                } catch (MultiException $e) {
                    ModuleItem::getDbConnection()->rollbackTransaction();
                } catch (Exception $e) {
                    ModuleItem::getDbConnection()->rollbackTransaction();
                }
            }

            // Обновляем software
            if (
                !empty($srcData->applianceSoft) &&
                !empty($srcData->softwareVersion) &&
                $appliance->software->version != $srcData->softwareVersion
            ) {
                try {
                    Appliance::getDbConnection()->beginTransaction();
                    $software = Software::getByVendor($appliance->vendor, $srcData->applianceSoft);
                    $appliance->software = SoftwareItem::getBySoftware($software, $srcData->softwareVersion);

                    $appliance->fill([
                        'software' => $appliance->software,
                    ])
                        ->save();
                    Appliance::getDbConnection()->commitTransaction();

                } catch (MultiException $e) {
                    Appliance::getDbConnection()->rollbackTransaction();
                } catch (Exception $e) {
                    Appliance::getDbConnection()->rollbackTransaction();
                }
            }

            // Обновляем месторасположения
            if (
                false != ($office = Office::findByLotusId($srcData->LotusId)) &&
                $appliance->location->lotusId != $srcData->LotusId
            ) {
                try {
                    Appliance::getDbConnection()->beginTransaction();
                    $appliance->fill([
                        'location' => $office,
                    ])
                        ->save();
                    Appliance::getDbConnection()->commitTransaction();

                } catch (MultiException $e) {
                    Appliance::getDbConnection()->rollbackTransaction();
                } catch (Exception $e) {
                    Appliance::getDbConnection()->rollbackTransaction();
                }
            }

            // Обновляем HOSTNAME
            if (
                !empty($srcData->hostname) &&
                $appliance->details->hostname != $srcData->hostname
            ) {
                try {
                    Appliance::getDbConnection()->beginTransaction();
                    $appliance->fill([
                        'details' => [
                            'hostname' => $srcData->hostname
                        ]
                    ])
                        ->save();
                    Appliance::getDbConnection()->commitTransaction();

                } catch (MultiException $e) {
                    Appliance::getDbConnection()->rollbackTransaction();
                } catch (Exception $e) {
                    Appliance::getDbConnection()->rollbackTransaction();
                }
            }

            // TODO: Change or Add Data Port

            die();
        }


        /*
         * Неизвестное устройство - создадим и сохраним его в БД
         */
        if (false == $appliance) {
            echo 'Create new Appliance    ';

            // Создать Appliance
            try {
                Appliance::getDbConnection()->beginTransaction();
                $office = Office::findByLotusId($srcData->LotusId);
                $vendor = Vendor::getByTitle($srcData->platformVendor);
                $platform = Platform::getByVendor($vendor, $srcData->platformTitle);
                $platformItem = PlatformItem::getByPlatform($platform, $srcData->platformSerial);
                $software = Software::getByVendor($vendor, $srcData->applianceSoft);
                $softwareItem = SoftwareItem::getBySoftware($software, $srcData->softwareVersion);
                $applianceType = ApplianceType::getByType($srcData->applianceType);

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

            } catch (MultiException $e) {
                Appliance::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                Appliance::getDbConnection()->rollbackTransaction();
            }

            // Добавить к Appliance Data Port
            try {
                // TODO: Сделать получение $portType со значением по умолчанию "Eth"
                $portTypeID = 5;
                $portType = DPortType::findByPK($portTypeID);

                DataPort::getDbConnection()->beginTransaction();
                (new DataPort())
                    ->fill([
                        'appliance' => $appliance,
                        'ipAddress' => $srcData->ip,
                        'portType' => $portType,
                    ])
                    ->save();
                DataPort::getDbConnection()->commitTransaction();

            } catch (MultiException $e) {
                DataPort::getDbConnection()->rollbackTransaction();
            } catch (Exception $e) {
                DataPort::getDbConnection()->rollbackTransaction();
            }

            // Добавить новые модули к Appliance
            if (!empty($srcData->applianceModules)) {
                try {
                    ModuleItem::getDbConnection()->beginTransaction();
                    foreach ($srcData->applianceModules as $newModule) {

                        if (
                            !empty($newModule->serial) &&
                            !empty($newModule->product_number)
                        ) {

                            $module = Module::getByVendorAndTitle($appliance->vendor, $newModule->product_number, $newModule->description);

                            (new ModuleItem())
                                ->fill([
                                    'appliance' => $appliance,
                                    'module' => $module,
                                    'serialNumber' => $newModule->serial,
                                ])
                                ->save();
                        }
                    }

                    ModuleItem::getDbConnection()->commitTransaction();

                } catch (MultiException $e) {
                    ModuleItem::getDbConnection()->rollbackTransaction();
                } catch (Exception $e) {
                    ModuleItem::getDbConnection()->rollbackTransaction();
                }
            }

            die;
        }
    }
}

