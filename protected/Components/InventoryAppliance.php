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
use T4\Core\Std;

class InventoryAppliance
{
    private $data;
    private $appliance;
    private $cluster;
    private $logger;

    public function update(): void
    {
        try {
            if (!$this->isDataValid()) { throw new \Exception('Not valid data'); }
            $this
                ->updateApplianceCore()
                ->updateApplianceModules()
                ->updateApplianceManagementDataPort();
        } catch (\Throwable $e) {
            $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . $this->data->ip);
            throw new \Exception('Runtime error');
        }
    }

    /**
     * @return $this
     * @throws \Exception
     */
    private function updateApplianceCore()
    {
        try {
            Appliance::getDbConnection()->beginTransaction();
            if (is_null($this->appliance()->details)) {
                $this->appliance()->details = new Std();
            }
            $this->appliance()->details->hostname = $this->data->hostname;
            $this->appliance()
                ->fill([
                    'cluster' => $this->cluster,
                    'vendor' => $this->vendor(),
                    'type' => ApplianceType::instanceWithType($this->data->applianceType),
                    'location' => $this->location(),
                    'platform' => $this->updatedPlatform(),
                    'software' => $this->updatedSoftware(),
                    'lastUpdate' => $this->lastUpdate(),
                ])
                ->save();
            Appliance::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            throw new \Exception($e->getMessage());
        }
        return $this;
    }

    /**
     * !!! Не рабочие (не опросившиеся) модули остаются на текущем устройстве до момента их опроса на каком-либо устройстве
     * !!! При перемещении модуля на другое устройство, данные в (details, comment) не удаляются
     *
     * @return $this
     */
    private function updateApplianceModules()
    {
        foreach ($this->data->applianceModules as $updatedData) {
            try {
                $module = ModuleItem::findBySerialVendor($updatedData->serial, $this->vendor());
                $this->updateModule((false !== $module) ? $module : new ModuleItem(), $updatedData);
            } catch (\Throwable $e) {
                $this->logger->error('[message]=' . $e->getMessage() . ' [module]=' . $updatedData->serial . ' [ip]=' . $this->data->ip);
            }
        }
        return $this;
    }

    /**
     * @throws MultiException
     */
    private function updateApplianceManagementDataPort()
    {
        if (!is_null($this->cluster)) {
            return $this;
        }
        $port = $this->appliance()->findDataPortByIpVrf((new IpTools($this->data->ip))->address, Vrf::instanceWithName($this->data->vrf_name));
        if (false !== $port && $port->isManagement) {
            return $this;
        }
        if (false !== $port && !$port->isManagement) {
            $this->makeManageable($port);
            return $this;
        }
        $this->createPort();
        return $this;
    }

    /**
     * @throws \Exception
     */
    private function createPort(): void
    {
        try {
            DataPort::getDbConnection()->beginTransaction();
            $ipTool = new IpTools($this->data->ip);
            $this->deleteDustDataPort($ipTool->address, Vrf::instanceWithName($this->data->vrf_name));
            $port = new DataPort();
            $port
                ->fill([
                    'ipAddress' => $ipTool->address,
                    'masklen' => $ipTool->masklen,
                    'vrf' => Vrf::instanceWithName($this->data->vrf_name),
                    'isManagement' => true,
                    'portType' => DPortType::getEmpty(),
                    'appliance' => $this->appliance(),
                    'lastUpdate' => $this->lastUpdate(),
                ])
                ->save();
            if (count($port->errors) > 0) { throw new \Exception($port->errors[0]); }
            DataPort::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            DataPort::getDbConnection()->rollbackTransaction();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param DataPort $port
     * @throws \Exception
     */
    private function makeManageable(DataPort $port): void
    {
        try {
            DataPort::getDbConnection()->beginTransaction();
            $this->makeExistDataPortsNotManagement();
            $port->fill(['isManagement' => true])->save();
            if (count($port->errors) > 0) { throw new \Exception($port->errors[0]); }
            DataPort::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            DataPort::getDbConnection()->rollbackTransaction();
            throw new \Exception($e->getMessage());
        }
    }

    private function makeExistDataPortsNotManagement(): void
    {
        foreach ($this->appliance()->dataPorts as $port) {
            if ($port->isManagement) {
                $port->fill(['isManagement' => false])->save();
                if (count($port->errors) > 0) {
                    $this->logger->error('[message]=' . $port->errors[0] . ' [ip]=' . $port->ipAddress . ' [managementIp]=' . $this->data->ip);
                }
            }
        }
    }

    /**
     * @return PlatformItem
     * @throws MultiException
     */
    private function updatedPlatform(): PlatformItem
    {
        if ($this->isApplianceNewEmpty()) {
            $this->appliance()->platform
                ->fill([
                    'serialNumber' => $this->data->platformSerial,
                    'platform' => Platform::instanceWithTitleVendor($this->platformTitle(), $this->vendor())
                ])
                ->save();
        }
        if ($this->hasSerialOrPlatformChanged()) {
            throw new \Exception("The appliance has different serial number or platform");
        }
        return $this->appliance()->platform;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function isApplianceNewEmpty(): bool
    {
        return empty($this->appliance()->platform->serialNumber);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function hasSerialOrPlatformChanged(): bool
    {
        return $this->data->platformSerial != $this->appliance()->platform->serialNumber
            || $this->platformTitle() != $this->appliance()->platform->platform->title;
    }

    /**
     * @return SoftwareItem
     * @throws MultiException
     */
    private function updatedSoftware(): SoftwareItem
    {
        if ($this->hasSoftVersionOrSoftChanged()) {
            $this->appliance()->software
                ->fill([
                    'version' => $this->data->softwareVersion,
                    'software' => Software::instanceWithTitleVendor($this->data->applianceSoft, $this->vendor())
                ])
                ->save();
        }
        return $this->appliance()->software;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function hasSoftVersionOrSoftChanged(): bool
    {
        return $this->data->softwareVersion != $this->appliance()->software->version
            || $this->data->applianceSoft != $this->appliance()->software->software->title;
    }

    /**
     * @param ModuleItem $moduleItem
     * @param \stdClass $data
     * @throws MultiException
     */
    private function updateModule(ModuleItem $moduleItem, \stdClass $data): void
    {
        if (!$moduleItem->isNew() && $data->product_number != $moduleItem->module->title) {
            throw new \Exception("Module has different title");
        }
        $moduleItem
            ->fill([
                'serialNumber' => $data->serial,
                'module' => $this->moduleWith($data),
                'appliance' => $this->appliance(),
                'location' => $this->appliance()->location,
                'lastUpdate' => $this->lastUpdate(),
            ])
            ->save();
    }

    /**
     * @param \stdClass $data
     * @return Module
     * @throws MultiException
     */
    private function moduleWith(\stdClass $data): Module
    {
        $module = Module::instanceWithTitleVendor($data->product_number, $this->vendor());
        if ($data->description != $module->description) {
            $module->fill(['description' => $data->description])->save();
        }
        return $module;
    }

    /**
     * @param string $ip
     * @param Vrf $vrf
     * @throws \Exception
     */
    private function deleteDustDataPort(string $ip, Vrf $vrf): void
    {
        $port = DataPort::findByIpVrf($ip, $vrf);
        if (false !== $port && $port->appliance->getPk() != $this->appliance()->getPk()) {
            $port->delete();
        }
    }

    /**
     * @return Appliance
     * @throws \Exception
     */
    private function appliance(): Appliance
    {
        if (is_null($this->appliance)) {
            $this->appliance = false;
            if (!empty($this->data->platformSerial)) {
                $this->appliance = $this->findApplianceBySerialVendor();
            }
            if (false === $this->appliance) {
                $this->appliance = $this->findNewEmptyAppliance();
            }
            if (false === $this->appliance) {
                throw new \Exception("Appliance is not found");
            }
        }
        return $this->appliance;
    }

    /**
     * @return mixed
     */
    private function findApplianceBySerialVendor()
    {
        return Appliance::findBySerialVendor($this->data->platformSerial, $this->data->platformVendor);
    }

    /**
     * @return mixed
     */
    private function findNewEmptyAppliance()
    {
        $appliance = Appliance::findByManagementIpVrf((new IpTools($this->data->ip))->address, $this->data->vrf_name);
        return (false !== $appliance && empty($appliance->platform->serialNumber)) ? $appliance : false;
    }

    private function platformTitle(): string
    {
        return trim(mb_ereg_replace(' +', ' ', mb_ereg_replace('Cisco|CISCO|-CHASSIS', '', $this->data->chassis)));
    }

    /**
     * @return Office
     * @throws \Exception
     */
    private function location(): Office
    {
        if (false === $office = Office::findByColumn('lotusId', $this->data->LotusId)) {
            throw new \Exception("Office is not found");
        }
        return $office;
    }

    /**
     * @return Vendor
     * @throws MultiException
     */
    private function vendor(): Vendor
    {
        return Vendor::instanceWithTitle($this->data->platformVendor);
    }

    /**
     * @return string
     * @throws \Exception
     */
    private function lastUpdate(): string
    {
        return (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P');
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
    private function isDataValid(): bool
    {
        if (!isset(
            $this->data->dataSetType,
            $this->data->applianceType,
            $this->data->platformVendor,
            $this->data->platformTitle,
            $this->data->platformSerial,
            $this->data->chassis,
            $this->data->applianceSoft,
            $this->data->softwareVersion,
            $this->data->LotusId,
            $this->data->hostname,
            $this->data->ip,
            $this->data->vrf_name,
            $this->data->applianceModules
        )) {
            return false;
        }
        foreach ($this->data->applianceModules as $module) {
            if (!isset(
                $module->serial,
                $module->product_number,
                $module->description
            )) {
                return false;
            }
        }
        return true;
    }

    public function __construct(\stdClass $data, Cluster $cluster = null)
    {
        $this->data = $data;
        $this->cluster = $cluster;
        $this->logger = StreamLogger::instanceWith('DS-APPLIANCE');
    }
}
