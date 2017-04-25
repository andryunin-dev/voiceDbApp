<?php

namespace App\Controllers;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Mvc\Controller;

class Rsapl extends Controller
{
    public function actionDefault()
    {


        try {
            Appliance::getDbConnection()->beginTransaction();

            // Getting Datasets in JSON format from php://input
            $jsonData = json_decode(file_get_contents('php://input'));
            if (null == $jsonData) {
                throw new Exception('DATASET: Empty an input dataset or Not a valid JSON');
            }

            $errors = new MultiException();

            // Determine the validity of the input data format
            $srcData = (new Std())->fill($jsonData);

            if (!isset($srcData->LotusId)) {
                $errors->add(new Exception('DATASET: No field LotusId'));
            }
            if (empty($srcData->LotusId) || !is_numeric($srcData->LotusId)) {
                $errors->add(new Exception('DATASET: LotusId is not valid'));
            }
            if (!isset($srcData->platformVendor)) {
                $errors->add(new Exception('DATASET: No field platformVendor'));
            }
            if (!isset($srcData->platformTitle)) {
                $errors->add(new Exception('DATASET: No field platformTitle'));
            }
            if (!isset($srcData->platformSerial)) {
                $errors->add(new Exception('DATASET: No field platformSerial'));
            }
            if (!isset($srcData->applianceType)) {
                $errors->add(new Exception('DATASET: No field applianceType'));
            }
            if (!isset($srcData->applianceModules)) {
                $errors->add(new Exception('DATASET: No field applianceModules'));
            }
            if (!empty($srcData->applianceModules)) {
                foreach ($srcData->applianceModules as $moduleDataset) {

                    if (!isset($moduleDataset->product_number)) {
                        $errors->add(new Exception('DATASET: No field applianceModule->product_number'));
                    }
                    if (!isset($moduleDataset->serial)) {
                        $errors->add(new Exception('DATASET: No field applianceModule->serial'));
                    }
                    if (!isset($moduleDataset->description)) {
                        $errors->add(new Exception('DATASET: No field applianceModule->description'));
                    }
                }
            }
            if (!isset($srcData->applianceSoft)) {
                $errors->add(new Exception('DATASET: No field applianceSoft'));
            }
            if (!isset($srcData->softwareVersion)) {
                $errors->add(new Exception('DATASET: No field softwareVersion'));
            }
            if (!isset($srcData->hostname)) {
                $errors->add(new Exception('DATASET: No field hostname'));
            }
            if (!isset($srcData->ip)) {
                $errors->add(new Exception('DATASET: No field ip'));
            }

            // Если DataSet не валидный, то заканчиваем работу
            if (0 < $errors->count()) {
                throw $errors;
            }

            // Determine "Location"
            $office = Office::findByLotusId($srcData->LotusId);
            if (false == $office) {
                throw new Exception('ERROR: Location not found');
            }

            // Determine "Vendor"
            $vendor = Vendor::findByTitle($srcData->platformVendor);
            if (false == $vendor) {
                $vendor = (new Vendor())
                    ->fill([
                        'title' => $srcData->platformVendor
                    ])
                    ->save();
            }

            // Determine "Platform"
            $requestPlatformTitle = $srcData->platformTitle;
            $platform = $vendor->platforms->filter(
                function ($platform) use ($requestPlatformTitle) {
                    return $requestPlatformTitle == $platform->title;
                }
            )->first();
            if (false == $platform) {
                $platform = (new Platform())
                    ->fill([
                        'vendor' => $vendor,
                        'title' => $srcData->platformTitle
                    ])
                    ->save();
            }

            // Determine "PlatformItem"
            $requestPlatformSerial = $srcData->platformSerial;
            $platformItem = $platform->platformItems->filter(
                function ($platformItem) use ($requestPlatformSerial) {
                    return $requestPlatformSerial == $platformItem->serialNumber;
                }
            )->first();
            if (false == $platformItem) {
                $platformItem = (new PlatformItem())
                    ->fill([
                        'platform' => $platform,
                        'serialNumber' => $srcData->platformSerial
                    ])
                    ->save();
            }

            // Determine "Software"
            $requestApplianceSoft = $srcData->applianceSoft;
            $software = $vendor->software->filter(
                function ($software) use ($requestApplianceSoft) {
                    return $requestApplianceSoft == $software->title;
                }
            )->first();
            if (false == $software) {
                $software = (new Software())
                    ->fill([
                        'vendor' => $vendor,
                        'title' => $srcData->applianceSoft
                    ])
                    ->save();
            }

            // Determine "SoftwareItem"
            $requestSoftwareVersion = $srcData->softwareVersion;
            $softwareItem = $software->softwareItems->filter(
                function ($softwareItem) use ($requestSoftwareVersion) {
                    return $requestSoftwareVersion == $softwareItem->version;
                }
            )->first();
            if (false == $softwareItem) {
                $softwareItem = (new SoftwareItem())
                    ->fill([
                        'software' => $software,
                        'version' => $srcData->softwareVersion
                    ])
                    ->save();
            }

            // Determine "Appliance Type"
            $applianceType = ApplianceType::findByType($srcData->applianceType);
            if (false == $applianceType) {
                $applianceType = (new ApplianceType())
                    ->fill([
                        'type' => $srcData->applianceType
                    ])
                    ->save();
            }

            // Determine "Appliance"
            $appliance = (false !== $platformItem->appliance) ? $platformItem->appliance : new Appliance();
            $appliance->fill([
                'location' => $office,
                'type' => $applianceType,
                'vendor' => $vendor,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'details' => [
                    'hostname' => $srcData->hostname,
                ]
            ])->save();
            if (false == $appliance) {
                throw new Exception('ERROR: The appliance is not created');
            }

            // Determine the USED "Modules"
            $usedModules = new Collection();
            foreach ($srcData->applianceModules as $moduleDataset) {

                // Determine "Module"
                $module = Module::findByVendorAndTitle($vendor, $moduleDataset->product_number);
                if (false == $module) {
                    $module = (new Module())
                        ->fill([
                            'vendor' => $vendor,
                            'title' => $moduleDataset->product_number,
                            'description' => $moduleDataset->description,
                        ])
                        ->save();
                }

                // Determine "ModuleItem"
                $moduleItemSerial = $moduleDataset->serial;
                $moduleItem = $module->moduleItems->filter(
                    function ($moduleItem) use ($moduleItemSerial) {
                        return $moduleItemSerial == $moduleItem->serialNumber;
                    }
                )->first();
                if (false == $moduleItem) {
                    $moduleItem = (new ModuleItem())
                        ->fill([
                            'module' => $module,
                            'serialNumber' => $moduleItemSerial,
                            'appliance' => $appliance,
                            'location' => $appliance->location,
                        ])
                        ->save();
                }

                $usedModules->add($moduleItem);
            }

//            var_dump($usedModules);

            // Determine the UNUSED "Modules"
            foreach ($appliance->modules as $dbModule) {
                if (!$usedModules->existsElement(['serialNumber' => $dbModule->serialNumber])) {
                    $dbModule->fill([
                        'appliance' => 2,
                    ])->save();

                    var_dump($dbModule);
                }
            }



            Appliance::getDbConnection()->commitTransaction();
        } catch (MultiException $errors) {
            Appliance::getDbConnection()->rollbackTransaction();

            foreach ($errors as $error) {
                var_dump($error->getMessage());
            }
        } catch (Exception $e) {
            Appliance::getDbConnection()->rollbackTransaction();

            var_dump($e->getMessage());
        }


        die;
    }
}