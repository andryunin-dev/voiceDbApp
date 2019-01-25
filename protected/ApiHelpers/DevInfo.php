<?php

namespace App\ApiHelpers;

use App\Models\Appliance;
use App\Models\ApplianceType;
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
 * @property Std $geoLocation
 *  int $office_id
 *  int $city_id
 *  int $region_id
 *  string $office_comment
 * @property Std $devInfo
 *  int $dev_id
 *  int $location_id
 *  int $platform_id
 *  int $platform_item_id
 *  int $software_id
 *  int $software_item_id
 *  int $vendor_id
 *  int $dev_type_id
 *  string $dev_comment
 *  string $software_comment
 *  string $dev_last_update
 *  bool $dev_in_use
 *  string $platform_sn
 *  string $platform_sn_alt
 *  bool $is_hw
 *  string software_ver
 *  Std dev_details
 *  {
 *      site {row, rack, unit, floor, rackSide},
 *      hostname
 *  }
 *  Std software_details {}
 * @property Std[] $modules
 * @property Std[] $ports
 * @property Std $errors
 */
class DevInfo extends Std
{
    public $errors;
    
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
    }
    public function saveDev()
    {
        // Appliance->details
        if ($this->rawData->devInfo->dev_details instanceof Std || is_null($this->rawData->devInfo->dev_details)) {
            $this->devDetails = $this->rawData->devInfo->dev_details;
        }
    }
}