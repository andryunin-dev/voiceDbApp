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
use T4\Core\MultiException;

class WorkAppliance
{
    private $appliance;
    private $actualData;
    private $actualCluster;
    private $logger;

    /**
     * WorkAppliance constructor.
     * @param \stdClass $actualData
     * @param Cluster|null $actualCluster
     * @throws \Exception
     */
    public function __construct(\stdClass $actualData, Cluster $actualCluster = null)
    {
        $this->actualData = $actualData;
        if (!$this->validateDataStructure()) {
            throw new \Exception("Not valid input data structure");
        }
        $this->appliance = $this->findAppliance();
        if (false === $this->appliance) {
            throw new \Exception("Appliance is not found");
        }
        $this->actualCluster = $actualCluster;
        $this->logger = StreamLogger::getInstance('DS-APPLIANCE');
    }

    /**
     * @return Appliance|bool
     */
    private function findAppliance()
    {
        $appliance = Appliance::findBySerialVendor($this->actualData->platformSerial, $this->actualData->platformVendor);
        if (false === $appliance) {
            // вариант заведеного устройства с пустым серийным номером
            $emptyAppliance = Appliance::findByManagementIpVrf($this->actualData->ip, $this->actualData->vrf_name);
            if (false !== $emptyAppliance && empty($emptyAppliance->platform->serialNumber)) {
                $appliance = $emptyAppliance;
            }
        }
        return $appliance;
    }

    /**
     * @throws \Exception
     */
    public function update(): void
    {
        try {
            $this->updateApplianceCore();
            $this->updateApplianceModules();
            $this->updateApplianceManagementDataPort();
        } catch (\Throwable $e) {
            $this->logger->error('[ip]='.$this->actualData->ip.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->actualData));
            throw new \Exception("Error: [ip]=".$this->actualData->ip);
        }
    }

    /**
     * @throws \Exception
     */
    private function updateApplianceCore(): void
    {
        $actualLocation = Office::findByColumn('lotusId', $this->actualData->LotusId);
        if (false === $actualLocation) {
            throw new \Exception("Office is not found");
        }
        $actualVendor = Vendor::getInstanceByTitle($this->actualData->platformVendor);
        $actualType = ApplianceType::getInstanceByType($this->actualData->applianceType);
        $updatePlatform = $this->updatePlatformItem();
        $updateSoftware = $this->updateSoftwareItem();
        $this->appliance->details->hostname = $this->actualData->hostname;
        $this->appliance->fill([
            'cluster' => $this->actualCluster,
            'vendor' => $actualVendor,
            'type' => $actualType,
            'location' => $actualLocation,
            'platform' => $updatePlatform,
            'software' => $updateSoftware,
            'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
        ]);
        $this->appliance->save();
    }

    /**
     * @return PlatformItem
     * @throws \T4\Core\MultiException
     */
    private function updatePlatformItem(): PlatformItem
    {
        $actualVendor = Vendor::getInstanceByTitle($this->actualData->platformVendor);
        $actualPlatformTitle = trim(mb_ereg_replace(' +', ' ', mb_ereg_replace('Cisco|CISCO|-CHASSIS', '', $this->actualData->chassis)));
        $actualPlatform = Platform::getInstanceByVendorTitle($actualVendor, $actualPlatformTitle);
        $platformItem = $this->appliance->platform;
        if ($platformItem->platform->getPk() != $actualPlatform->getPk() || $platformItem->serialNumber != $this->actualData->platformSerial) {
            $platformItem->platform = $actualPlatform;
            $platformItem->serialNumber = $this->actualData->platformSerial;
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
        $actualVendor = Vendor::getInstanceByTitle($this->actualData->platformVendor);
        $actualSoftware = Software::getInstanceByVendorTitle($actualVendor, $this->actualData->applianceSoft);
        $softwareItem = $this->appliance->software;
        if ($softwareItem->software->getPk() != $actualSoftware->getPk() || $softwareItem->version != $this->actualData->softwareVersion) {
            $softwareItem->software = $actualSoftware;
            $softwareItem->version = $this->actualData->softwareVersion;
            $softwareItem->save();
        }
        return $softwareItem;
    }

    /**
     * !!! Не рабочие (не опросившиеся) модули остаются на текущем устройстве до момента их опроса на каком-либо устройстве
     */
    private function updateApplianceModules(): void
    {
        foreach ($this->actualData->applianceModules as $actualMuduleData) {
            try {
                $module = ModuleItem::findByVendorSerial($this->appliance->platform->platform->vendor, $actualMuduleData->serial);
                if (false === $module) {
                    $module = new ModuleItem();
                }
                $this->updateModule($module, $actualMuduleData);
            } catch (\Throwable $e) {
                $this->logger->error('[ip]='.$this->actualData->ip.'; [module]='.$actualMuduleData->serial.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->actualData));
            }
        }
    }

    /**
     * !!! При перемещении модуля на другое устройство, данные в (details, comment) не удаляются
     *
     * @param ModuleItem $moduleItem
     * @param \stdClass $actualData
     * @throws \T4\Core\MultiException
     */
    private function updateModule(ModuleItem $moduleItem, \stdClass $actualData): void
    {
        $actualModule = Module::getInstanceByVendorTitle($this->appliance->platform->platform->vendor, $actualData->product_number);
        if ($actualModule->description != $actualData->description) {
            $actualModule->description = $actualData->description;
            $actualModule->save();
        }
        $moduleItem->fill([
            'module' => $actualModule,
            'serialNumber' => $actualData->serial,
            'appliance' => $this->appliance,
            'location' => $this->appliance->location,
            'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
        ]);
        $moduleItem->save();
    }

    /**
     * @throws \Exception
     */
    private function updateApplianceManagementDataPort(): void
    {
        if (!is_null($this->actualCluster)) {
            return;
        }
        $managementDPort = DataPort::findByIpVrf(
            (new IpTools($this->actualData->ip))->address,
            Vrf::getInstanceByName($this->actualData->vrf_name)
        );
        if (false === $managementDPort) {
            throw new \Exception("Management DataPort is not found");
        }
        try {
            DataPort::getDbConnection()->beginTransaction();
            if ($managementDPort->appliance->getPk() != $this->appliance->getPk()) {
                $managementDPort->delete();
                $this->appliance->uncheckExistManagementDPort();
                $managementDPort = $this->createNewManagementDataPort();
            }
            $managementDPort->fill([
                'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                'vrf' => Vrf::getInstanceByName($this->actualData->vrf_name),
            ]);
            $managementDPort->save();
            if (count($managementDPort->errors) > 0) {
                throw new \Exception($managementDPort->errors[0]);
            }
            DataPort::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            DataPort::getDbConnection()->rollbackTransaction();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @return DataPort
     * @throws MultiException
     */
    private function createNewManagementDataPort(): DataPort
    {
        $ipTools = (new IpTools($this->actualData->ip));
        return (new DataPort)->fill([
            'appliance' => $this->appliance,
            'portType' => DPortType::getEmpty(),
            'ipAddress' => $ipTools->address,
            'isManagement' => true,
            'masklen' => $ipTools->masklen,
        ]);
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
        if (!isset($this->actualData->dataSetType)
            || !isset($this->actualData->applianceType)
            || !isset($this->actualData->platformVendor)
            || !isset($this->actualData->platformTitle)
            || !isset($this->actualData->platformSerial)
            || !isset($this->actualData->chassis)
            || !isset($this->actualData->applianceSoft)
            || !isset($this->actualData->softwareVersion)
            || !isset($this->actualData->LotusId)
            || !isset($this->actualData->hostname)
            || !isset($this->actualData->ip)
            || !isset($this->actualData->vrf_name)
            || !isset($this->actualData->applianceModules)
        ) {
            return false;
        }
        foreach ($this->actualData->applianceModules as $module) {
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
