<?php

namespace App\ApiHelpers;

use App\Components\IpTools;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Office;
use App\Models\Platform;
use App\Models\Software;
use App\Models\Vendor;
use App\Models\Vrf;
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
    const DEFAULT_PORT_TYPE = '';
    
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
        try {
            Appliance::getDbConnection()->beginTransaction();
            // Appliance->details
            if ($this->rawData->devInfo->dev_details instanceof Std || is_null($this->rawData->devInfo->dev_details)) {
                $this->devDetails = $this->rawData->devInfo->dev_details;
            } else {
                $this->devDetails = null;
            }
            //office comment
            $this->office->comment = $this->rawData->geoLocation->office_comment;
            $this->office->save();
            $this->currentAppliance->inUse = $this->rawData->devInfo->dev_in_use;
            ($this->currentAppliance->platform)
                ->fill([
                    'platform' => $this->platform,
                    'serialNumber' => $this->rawData->devInfo->platform_item_sn,
                    'serialNumberAlt' => $this->rawData->devInfo->platform_item_sn_alt,
                ])
                ->save();
            ($this->currentAppliance->software)
                ->fill([
                    'software' => $this->software,
                    'version' => $this->rawData->devInfo->software_item_ver
                ])
                ->save();
            ($this->currentAppliance)
                ->fill([
                    'location' => $this->office,
                    'vendor' => $this->vendor,
                    'type' => $this->applianceType,
                    'details' => $this->devDetails,
                    'comment' => $this->rawData->d
                ])
                ->save();
            //save modules
            foreach ($this->rawData->modules as $updatedModule) {
                if ($updatedModule->newModule === true) {
                    $moduleItem = new ModuleItem();
                    if (false === $module = Module::findByPK($updatedModule->module_id)) {
                        $this->errors[] = 'Save data error for new module item';
                        continue;
                    }
                } else {
                    if (
                        false === ($module = Module::findByPK($updatedModule->module_id)) ||
                        false === ($moduleItem = ModuleItem::findByPK($updatedModule->module_item_id))
                    ) {
                        $this->errors[] = 'Save data error for module item ' . $updatedModule->module_item_id;
                        continue;
                    }
                }
        
                if ($updatedModule->deleted === true) {
                    $moduleItem->delete();
                    continue;
                }
                $moduleItem
                    ->fill([
                        'appliance' => $this->currentAppliance,
                        'module' => $module,
                        'details' => $updatedModule->module_item_details,
                        'serialNumber' => $updatedModule->module_item_sn,
                        'location' => $this->office,
                        'comment' => $updatedModule->module_item_comment,
                        'inUse' => $updatedModule->module_item_in_use,
                        'noFound' => $updatedModule->module_item_not_found
                    ])
                    ->save();
            }
            //save ports
            $defaultPortType = DPortType::findByColumn('type', self::DEFAULT_PORT_TYPE);
            foreach ($this->rawData->ports as $updatedPort) {
                $vrf = is_null($updatedPort->port_vrf_id) ? null : Vrf::findByPK($updatedPort->port_vrf_id);
                if ($vrf === false) {
                    $this->errors[] = 'Can\'t find VRF with id: ' . $updatedPort->port_vrf_id;
                    continue;
                }
                $portType = DPortType::findByPK($updatedPort->port_type_id);
                $ip = new IpTools($updatedPort->port_ip, $updatedPort->port_mask_len);
                if ($updatedPort->newPort === true) {
                    if ($updatedPort->deleted === true) {
                        continue;
                    }
                    if (!($vrf instanceof Vrf)) {
                        $this->errors[] = 'VRF error for new port';
                    }
                    $existedPort = DataPort::findByIpVrf($updatedPort->port_ip, $vrf);
                    if ($existedPort instanceof DataPort) {
                        $this->errors[] = 'Port with IP ' . $updatedPort->port_ip . 'and VRF ' . $vrf->name . ' already exists';
                        continue;
                    }
                    $portType = $portType === false ? $defaultPortType : $portType;
                    $newPort = (new DataPort())->fill([
                        'ipAddress' => $updatedPort->port_ip,
                        'masklen' => $ip->masklen,
                        'appliance' => $this->currentAppliance,
                        'vrf' => $vrf,
                        'portType' => $portType,
                        'isManagement' => $updatedPort->port_is_mng,
                        'macAddress' => $updatedPort->port_mac,
                        'comment' => $updatedPort->port_comment,
                        'details' => $updatedPort->port_details,
                    ]);
                    $newPort->save();
                } else {
                    $currentPort = DataPort::findByPK($updatedPort->port_id);
                    if (! $ip->is_valid) {
                        $this->errors[] = 'Invalid IP address: $updatedPort->port_ip' .  ' or mask: ' . $updatedPort->port_mask_len;
                        continue;
                    }
                    $currentPort->fill([
                        'ipAddress' => $ip->address,
                        'masklen' => $ip->masklen,
                        'appliance' => $this->currentAppliance,
                        'vrf' => $vrf,
                        'portType' => $portType,
                        'isManagement' => $updatedPort->port_is_mng,
                        'macAddress' => $updatedPort->port_mac,
                        'comment' => $updatedPort->port_comment,
                        'details' => $updatedPort->port_details,
                    ]);
                    $currentPort->save();
                }
            }
            if ($this->errors->count() === 0) {
                Appliance::getDbConnection()->commitTransaction();
            } else {
                Appliance::getDbConnection()->rollbackTransaction();
            }
        } catch (\Exception $e) {
            $this->errors[] = 'Unexpected error (code: ' . $e->getCode() . ') save data: ' . $e->getMessage();
            Appliance::getDbConnection()->rollbackTransaction();
        }
    }
}
