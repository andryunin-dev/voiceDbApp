<?php

namespace App\ApiHelpers;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Office;
use App\Models\Platform;
use App\Models\Software;
use App\Models\Vendor;
use T4\Core\Exception;
use T4\Core\Std;

/**
 * Class DevInfo
 * @package App\ApiHelpers
 *
 * @property Std $rawData
 *  ->geoLocation
 *      ->office_id
 *      ->city_id
 *      ->region_id
 *      office_comment
 *  ->devInfo
 *      ->dev_id
 *      ->location_id
 *      ->platform_id
 *      ->platform_item_id
 *      ->software_id
 *      ->software_item_id
 *      ->vendor_id
 *      ->dev_type_id
 *      ->dev_comment
 *      ->software_comment
 *      ->dev_last_update
 *      ->dev_in_use
 *      ->platform_sn
 *      ->platform_sn_alt
 *      ->is_hw
 *      ->software_ver
 *      ->dev_details
 *          ->site
 *              ->row
 *              ->rack
 *              ->unit
 *              ->floor
 *              ->rackSide
 *          ->hostname
 *      ->software_details
 * @property Std $errors
 * @property Appliance $currentAppliance
 * @property Vendor $vendor
 * @property ApplianceType $applianceType
 * @property Platform $platform
 * @property Software $software
 * @property Office $office
 * @property Std devDetails
 */
class DevInfo extends Std
{
    
    public function __construct($data = null)
    {
        $this->errors = new Std();
        if (empty($data)) {
            parent::__construct();
            return;
        }
        $this->rawData = $data;
        $this->checkLocation($data);
        $this->checkDevInfo($data);
        parent::__construct();
        
    }
    
    protected function checkLocation($data)
    {
        if (!isset($data->geoLocation)) {
            $this->errors[] = 'LocationInfo is not found';
            return;
        }
        if (false === $this->office = Office::findByPK($data->geoLocation->office_id)) {
            $errors[] = 'Office is not found';
        }
    }
    protected function checkDevInfo($data)
    {
        if (!isset($data->devInfo)) {
            $this->errors[] = 'devInfo is not found';
            return;
        }
        if (!empty($data->devInfo->dev_id)) {
            $this->currentAppliance = Appliance::findByPK($data->devInfo->dev_id);
        }
        if (false === $this->vendor = Vendor::findByPK($data->devInfo->vendor_id)) {
            $errors[] = 'Vendor is not found';
        }
        if (false === $this->applianceType = ApplianceType::findByPK($data->devInfo->dev_type_id)) {
            $errors[] = 'Appliance type is not found';
        }
        if (false === $this->platform = Platform::findByPK($data->devInfo->platform_id)) {
            $errors[] = 'Platform is not found';
        }
        if (false === $this->software = Software::findByPK($data->devInfo->software_id)) {
            $errors[] = 'Software is not found';
        }
        if (false === $this->office = Office::findByPK($data->devInfo->location_id)) {
            $errors[] = 'Office is not found';
        }
    }
    public function saveDev()
    {
        // Appliance->details
        if ($this->rawData->devInfo->dev_details instanceof Std || is_null($this->rawData->devInfo->dev_details)) {
            $this->devDetails = $this->rawData->devInfo->dev_details;
        }
        //office comment
        $this->office->comment = $this->rawData->geoLocation->office_comment;
        $this->office->save();
        $this->currentAppliance->inUse = $this->rawData->devInfo->dev_in_use;
        ($this->currentAppliance->platform)
            ->fill([
                'platform' => $this->platform,
                'serialNumber' => $this->rawData->devInfo->platform_sn,
                'serialNumberAlt' => $this->rawData->devInfo->platform_sn_alt,
            ])
            ->save();
        ($this->currentAppliance->software)
            ->fill([
                'software' => $this->software,
                'version' => $this->rawData->devInfo->software_ver
            ])
            ->save();
        ($this->currentAppliance)
            ->fill([
                'location' => $this->office,
                'vendor' => $this->vendor,
                'type' => $this->applianceType,
            ])
            ->save();
        //save modules
        foreach ($this->rawData->modules as $upModule) {
            if($upModule->newModule === true) {
                $moduleItem = new ModuleItem();
                if (false === $module = Module::findByPK($upModule->module_id)) {
                    $this->errors[] = 'Save data error for new module item';
                    continue;
                }
            } else {
                if (
                    false === ($module = Module::findByPK($upModule->module_id)) ||
                        false === ($moduleItem = ModuleItem::findByPK($upModule->module_item_id))
                ) {
                    $this->errors[] = 'Save data error for module item ' . $upModule->module_item_id;
                    continue;
                }
            }
            
            if ($upModule->deleted === true) {
                $moduleItem->delete();
                continue;
            }
            $moduleItem
                ->fill([
                    'appliance' => $this->currentAppliance,
                    'module' => $module,
                    'details' => $upModule->module_item_details,
                    'serialNumber' => $upModule->module_item_sn,
                    'location' => $this->office,
                    'comment' => $upModule->module_item_comment,
                    'inUse' => $upModule->module_item_in_use,
                    'noFound' => $upModule->module_item_not_found
                ])
                ->save();
        }

    }
}