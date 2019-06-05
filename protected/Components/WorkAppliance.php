<?php
namespace App\Components;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Cluster;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use App\Models\Vrf;

class WorkAppliance
{
    private $appliance;
    private $data;
    private $logger;
    private $cluster;

    /**
     * WorkAppliance constructor.
     * @param \stdClass $data
     * @param Cluster|null $cluster
     * @throws \Exception
     */
    public function __construct(\stdClass $data, Cluster $cluster = null)
    {
        $this->data = $data;
        $this->cluster = $cluster;
        $this->logger = StreamLogger::getInstance('DS-APPLIANCE');
    }

    /**
     * @throws \Exception
     */
    public function update(): void
    {
        try {
            if (!$this->validateDataStructure()) {
                throw new \Exception("Not valid input data structure");
            }
            $this->appliance = $this->findAppliance();
            if (false === $this->appliance) {
                throw new \Exception("Appliance is not found");
            }
            $this->updateApplianceCore();
            $this->updateApplianceModules();
            $this->updateApplianceManagementDataPort();
        } catch (\Throwable $e) {
            $this->logger->error('[ip]='.$this->data->ip.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->data));
            throw new \Exception("Error: [ip]=".$this->data->ip);
        }
    }

    /**
     * @return Appliance|bool
     */
    private function findAppliance()
    {
        $appliance = Appliance::findBySerialVendor($this->data->platformSerial, $this->data->platformVendor);
        return (false !== $appliance) ? $appliance : Appliance::findByManagementIpVrf($this->data->ip, $this->data->vrf_name);
    }

    /**
     * @throws \Exception
     */
    private function updateApplianceCore(): void
    {
        $location = Office::findByColumn('lotusId', $this->data->LotusId);
        if (false === $location) {
            throw new \Exception("Office is not found");
        }
        $vendor = Vendor::getInstanceByTitle($this->data->platformVendor);
        $type = ApplianceType::getInstanceByType($this->data->applianceType);
        $platform = $this->updatePlatformItem();
        $software = $this->updateSoftwareItem();
        $this->appliance->details->hostname = $this->data->hostname;
        $this->appliance->fill([
            'vendor' => $vendor,
            'type' => $type,
            'location' => $location,
            'platform' => $platform,
            'software' => $software,
            'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
        ]);
        if (!is_null($this->cluster)) {
            $this->appliance->cluster = $this->cluster;
        }
        $this->appliance->save();
    }

    /**
     * @return PlatformItem
     * @throws \T4\Core\MultiException
     */
    private function updatePlatformItem(): PlatformItem
    {
        $vendor = Vendor::getInstanceByTitle($this->data->platformVendor);
        $platformTitle = trim(mb_ereg_replace(' +', ' ', mb_ereg_replace('Cisco|CISCO|-CHASSIS', '', $this->data->chassis)));
        $platform = Platform::getInstanceByVendorTitle($vendor, $platformTitle);
        $platformItem = $this->appliance->platform;
        if ($platformItem->platform->getPk() != $platform->getPk() || $platformItem->serialNumber != $this->data->platformSerial) {
            $platformItem->platform = $platform;
            $platformItem->serialNumber = $this->data->platformSerial;
            $platformItem->save();
        }
        return $platformItem;
    }

    /**
     * @return SoftwareItem
     * @throws \T4\Core\MultiException
     */
    private function updateSoftwareItem(): SoftwareItem
    {
        $vendor = Vendor::getInstanceByTitle($this->data->platformVendor);
        $updatedSoftware = Software::getInstanceByVendorTitle($vendor, $this->data->applianceSoft);
        $softwareItem = $this->appliance->software;
        if ($softwareItem->software->getPk() != $updatedSoftware->getPk() || $softwareItem->version != $this->data->softwareVersion) {
            $softwareItem->software = $updatedSoftware;
            $softwareItem->version = $this->data->softwareVersion;
            $softwareItem->save();
        }
        return $softwareItem;
    }

    private function updateApplianceModules(): void
    {
        foreach ($this->data->applianceModules as $data) {
            try {
                $module = ModuleItem::findByVendorSerial($this->appliance->platform->platform->vendor, $data->serial);
                if (false === $module) {
                    $module = new ModuleItem();
                }
                $this->updateModule($module, $data);
            } catch (\Throwable $e) {
                $this->logger->error('[ip]='.$this->data->ip.'; [module]='.$data->serial.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->data));
            }
        }
    }

    /**
     * @param ModuleItem $moduleItem
     * @param \stdClass $data
     * @throws \T4\Core\MultiException
     */
    private function updateModule(ModuleItem $moduleItem, \stdClass $data): void
    {
        $module = Module::getInstanceByVendorTitle($this->appliance->platform->platform->vendor, $data->product_number);
        if ($module->description != $data->description) {
            $module->description = $data->description;
            $module->save();
        }
        $moduleItem->fill([
            'module' => $module,
            'serialNumber' => $data->serial,
            'appliance' => $this->appliance,
            'location' => $this->appliance->location,
            'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
        ]);
        $moduleItem->save();
    }

    /**
     * @throws \T4\Core\MultiException
     */
    private function updateApplianceManagementDataPort(): void
    {
        if (!is_null($this->cluster)) {
            return;
        }
        $ip = (new IpTools($this->data->ip))->address;
        $updatedManagementDPort = DataPort::findByIpVrf($ip, Vrf::getInstanceByName($this->data->vrf_name));
        if (false === $updatedManagementDPort) {
            throw new \Exception("Management DataPort is not found");
        }
        if ($updatedManagementDPort->appliance->getPk() != $this->appliance->getPk()) {
            $updatedManagementDPort->delete();
            $this->appliance->uncheckExistManagementDPort();
            $updatedManagementDPort = (new DataPort)->fill([
                'appliance' => $this->appliance,
                'portType' => DPortType::getEmpty(),
                'ipAddress' => $ip,
            ]);
        }
        $updatedManagementDPort->fill([
            'isManagement' => true,
            'vrf' => Vrf::getInstanceByName($this->data->vrf_name),
            'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
        ]);
        $updatedManagementDPort->save();
        if (count($updatedManagementDPort->errors) > 0) {
            throw new \Exception($updatedManagementDPort->errors[0]);
        }
    }

    /**
     * Validate data structure
     * {
     *   "dataSetType",
     *   "applianceType",
     *   "platformVendor",
     *   "platformTitle",
     *   "platformSerial",
     *   "chassis",
     *   "applianceSoft",
     *   "softwareVersion",
     *   "LotusId",
     *   "hostname",
     *   "ip",
     *   "vrf_name",
     *   "applianceModules": [
     *     {
     *        "serial",
     *        "product_number",
     *        "description",
     *     }
     *   ]
     * }
     * @return boolean
     */
    private function validateDataStructure(): bool
    {
        if (!isset($this->data->dataSetType)
            || !isset($this->data->applianceType)
            || !isset($this->data->platformVendor)
            || !isset($this->data->platformTitle)
            || !isset($this->data->platformSerial)
            || !isset($this->data->chassis)
            || !isset($this->data->applianceSoft)
            || !isset($this->data->softwareVersion)
            || !isset($this->data->LotusId)
            || !isset($this->data->hostname)
            || !isset($this->data->ip)
            || !isset($this->data->vrf_name)
            || !isset($this->data->applianceModules)
        ) {
            return false;
        }
        foreach ($this->data->applianceModules as $module) {
            if (!isset($module->serial)
                || !isset($module->product_number)
                || !isset($module->description)
            ) {
                return false;
            }
        }
        return true;
    }
}
