<?php
namespace App\Components;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Cluster;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use App\Models\Vrf;

class InventoryCluster
{

    private $data;
    private $cluster;
    private $updatedAppliances;
    private $logger;

    public function update(): void
    {
        try {
            if (!$this->isDataValid()) { throw new \Exception('Not valid data'); }
            $this->updateAppliances();
            $this->removeDustAppliances();
        } catch (\Throwable $e) {
            $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . $this->data->ip);
            throw new \Exception('Runtime error');
        }
    }

    /**
     * @throws \Exception
     */
    private function updateAppliances(): void
    {
        foreach ($this->data->clusterAppliances as $k => $data) {
            try {
                $this->updateAppliance($data);
                $this->updateApplianceManagementDataPort($data, $k);
                $this->addApplianceToUpdated($data);
            } catch (\Throwable $e) {
                $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . $this->data->ip . ' [serial]=' . $data->platformSerial);
                throw new \Exception('Runtime error');
            }
        }
    }

    /**
     * @param \stdClass $data
     * @param int $rank
     * @throws \T4\Core\MultiException
     */
    private function updateApplianceManagementDataPort(\stdClass $data, int $rank): void
    {
        if (0 !== $rank) {
            $this->deleteExistDataPorts($data);
            return;
        }
        $port = $this->applianceWith($data)->findDataPortBy((new IpTools($data->ip))->address, Vrf::instanceWithName($data->vrf_name));
        if (false !== $port && $port->isManagement) {
            return;
        }
        if (false !== $port && !$port->isManagement) {
            $this->makeManageable($port);
            return;
        }
        $this->createPort($data);
    }

    /**
     * @param \stdClass $data
     */
    private function deleteExistDataPorts(\stdClass $data): void
    {
        foreach ($this->applianceWith($data)->dataPorts as $port) {
            $port->delete();
        }
    }

    /**
     * @param DataPort $port
     * @throws \Exception
     */
    private function makeManageable(DataPort $port): void
    {
        $port->fill(['isManagement' => true])->save();
        if (count($port->errors) > 0) { throw new \Exception($port->errors[0]); }
    }

    /**
     * @param \stdClass $data
     * @throws \Exception
     */
    private function createPort(\stdClass $data): void
    {
        try {
            DataPort::getDbConnection()->beginTransaction();
            $ipTool = new IpTools($data->ip);
            $this->deleteDustDataPort($data);
            $port = (new DataPort())->fill([
                'ipAddress' => $ipTool->address,
                'masklen' => $ipTool->masklen,
                'vrf' => Vrf::instanceWithName($data->vrf_name),
                'isManagement' => true,
                'portType' => DPortType::getEmpty(),
                'appliance' => $this->applianceWith($data),
                'lastUpdate' => $this->lastUpdate(),
            ]);
            $port->save();
            if (count($port->errors) > 0) { throw new \Exception($port->errors[0]); }
            DataPort::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            DataPort::getDbConnection()->rollbackTransaction();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param \stdClass $data
     * @throws \T4\Core\MultiException
     */
    private function deleteDustDataPort(\stdClass $data): void
    {
        $port = DataPort::findByIpVrf((new IpTools($data->ip))->address, Vrf::instanceWithName($data->vrf_name));
        if (false !== $port && $port->appliance->getPk() != $this->applianceWith($data)->getPk()) {
            $port->delete();
        }
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
     * @param \stdClass $data
     * @throws \T4\Core\MultiException
     */
    private function updateAppliance(\stdClass $data): void
    {
        if ($this->isApplianceDoesNotExist($data)) {
            $this->createBlankAppliance($data);
        }
        (new InventoryAppliance($data, $this->cluster()))->update();
    }

    /**
     * @param \stdClass $data
     * @return bool
     */
    private function isApplianceDoesNotExist(\stdClass $data): bool
    {
        return false === $this->applianceWith($data);
    }

    /**
     * @param \stdClass $data
     * @return mixed
     */
    private function applianceWith(\stdClass $data)
    {
        return Appliance::findBySerialVendor($data->platformSerial, $data->platformVendor);
    }

    /**
     * @param \stdClass $data
     * @throws \Exception
     */
    private function createBlankAppliance(\stdClass $data): void
    {
        try {
            Appliance::getDbConnection()->beginTransaction();
            $platform = (new PlatformItem())
                ->fill([
                    'serialNumber' => $data->platformSerial,
                    'platform' => Platform::instanceWithTitleVendor($this->platformTitle($data), Vendor::instanceWithTitle($data->platformVendor))
                ])
                ->save();
            $software = (new SoftwareItem())
                ->fill([
                    'version' => $data->softwareVersion,
                    'software' => Software::instanceWithTitleVendor($data->applianceSoft, Vendor::instanceWithTitle($data->platformVendor))
                ])
                ->save();
            (new Appliance())
                ->fill([
                    'vendor' => Vendor::instanceWithTitle($data->platformVendor),
                    'type' => ApplianceType::instanceWithType($data->applianceType),
                    'location' => Office::findByColumn('lotusId', $data->LotusId),
                    'platform' => $platform,
                    'software' => $software,
                ])
                ->save();
            Appliance::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param \stdClass $data
     * @return string
     */
    private function platformTitle(\stdClass $data): string
    {
        return trim(mb_ereg_replace(' +', ' ', mb_ereg_replace('Cisco|CISCO|-CHASSIS', '', $data->chassis)));
    }

    /**
     * @param \stdClass $data
     */
    private function addApplianceToUpdated(\stdClass $data): void
    {
        $this->updatedAppliances[] = $this->applianceWith($data)->getPk();
    }

    /**
     * @throws \T4\Core\MultiException
     */
    private function removeDustAppliances(): void
    {
        foreach ($this->cluster()->appliances as $appliance) {
            if (!in_array($appliance->getPk(), $this->updatedAppliances)) {
                $appliance->fill(['cluster' => null])->save();
            }
        }
    }

    /**
     * @return Cluster
     * @throws \T4\Core\MultiException
     */
    private function cluster(): Cluster
    {
        if (is_null($this->cluster)) {
            $this->cluster = Cluster::instanceWithTitle($this->data->hostname);
        }
        return $this->cluster;
    }

    /**
     * Validate data structure
     * {
     *   "dataSetType",
     *   "hostname",
     *   "ip",
     *   "vrf_name",
     *   "clusterAppliances": [
     *      {
     *        "dataSetType",
     *        "applianceType",
     *        "platformVendor",
     *        "platformTitle",
     *        "platformSerial",
     *        "chassis",
     *        "applianceSoft",
     *        "softwareVersion",
     *        "LotusId",
     *        "hostname",
     *        "ip",
     *        "vrf_name",
     *        "applianceModules": [
     *          {
     *             "serial",
     *             "product_number",
     *             "description",
     *          }
     *        ]
     *      }
     *
     *   "platformSerial",  (do not used)
     *   "softwareVersion",  (do not used)
     *   "chassis",  (do not used)
     *   "LotusId",  (do not used)
     *   "applianceType",  (do not used)
     *   "platformTitle",  (do not used)
     *   "applianceSoft",  (do not used)
     *   "platformVendor",  (do not used)
     * }
     * @return boolean
     */
    private function isDataValid(): bool
    {
        if (!isset(
            $this->data->dataSetType,
            $this->data->hostname,
            $this->data->ip,
            $this->data->vrf_name,
            $this->data->clusterAppliances
        )) {
            return false;
        }
        foreach ($this->data->clusterAppliances as $appliance) {
            if (!isset(
                $appliance->dataSetType,
                $appliance->applianceType,
                $appliance->platformVendor,
                $appliance->platformTitle,
                $appliance->platformSerial,
                $appliance->chassis,
                $appliance->applianceSoft,
                $appliance->softwareVersion,
                $appliance->LotusId,
                $appliance->hostname,
                $appliance->ip,
                $appliance->vrf_name,
                $appliance->applianceModules
            )) {
                return false;
            }
            foreach ($appliance->applianceModules as $module) {
                if (!isset(
                    $module->serial,
                    $module->product_number,
                    $module->description
                )) {
                    return false;
                }
            }
        }
        return true;
    }

    public function __construct(\stdClass $data)
    {
        $this->data = $data;
        $this->logger = StreamLogger::getInstance('DS-CLUSTER');
    }
}
