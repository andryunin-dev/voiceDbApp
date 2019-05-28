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

class WorkCluster
{
    private $cluster;
    private $updatedAppliances = [];
    private $data;
    private $logger;

    /**
     * WorkCluster constructor.
     * @param \stdClass $data
     * @throws \Exception
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
        $this->logger = StreamLogger::getInstance('DS-CLUSTER');
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
            $this->cluster = Cluster::getInstanceByTitle($this->data->hostname);
            $this->updateClusterAppliances();
            $this->removeFakeAppliancesFromCluster();
        } catch (\Throwable $e) {
            $this->logger->error('[ip]='.$this->data->ip.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->data));
            throw new \Exception("Error: [ip]=".$this->data->ip);
        }
    }

    private function updateClusterAppliances(): void
    {
        foreach ($this->data->clusterAppliances as $k => $data) {
            try {
                $appliance = Appliance::findBySerialVendor($data->platformSerial, $data->platformVendor);
                if (false === $appliance) {
                    $appliance = $this->createAppliance($data);
                }
                if (0 === $k) {
                    $this->updateApplianceManagementDataPort($appliance, $data);
                } else {
                    $appliance->deleteDataPorts();
                }
                (new WorkAppliance($data, $this->cluster))->update();
                $this->updatedAppliances[] = $appliance->getPk();
            } catch (\Throwable $e) {
                $this->logger->error('[ip]='.$this->data->ip.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->data));
            }
        }
    }

    /**
     * @param Appliance $appliance
     * @param \stdClass $data
     * @throws \T4\Core\MultiException
     */
    private function updateApplianceManagementDataPort(Appliance $appliance, \stdClass $data): void
    {
        $ip = (new IpTools($data->ip))->address;
        $updatedManagementDPort = DataPort::findByIpVrf($ip, Vrf::getInstanceByName($data->vrf_name));
        if (false === $updatedManagementDPort) {
            throw new \Exception("Management DataPort is not found");
        }
        if ($updatedManagementDPort->appliance->getPk() != $appliance->getPk()) {
            $updatedManagementDPort->delete();
            $appliance->uncheckExistManagementDPort();
            $updatedManagementDPort = (new DataPort)->fill([
                'appliance' => $appliance,
                'portType' => DPortType::getEmpty(),
                'ipAddress' => $ip,
            ]);
        }
        $updatedManagementDPort->fill([
            'isManagement' => true,
            'vrf' => Vrf::getInstanceByName($data->vrf_name),
            'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
        ]);
        $updatedManagementDPort->save();
        if (count($updatedManagementDPort->errors) > 0) {
            throw new \Exception($updatedManagementDPort->errors);
        }
    }

    /**
     * @param array $realClusterAppliances
     */
    private function removeFakeAppliancesFromCluster(): void
    {
        foreach ($this->cluster->appliances as $appliance) {
            if (!in_array($appliance->getPk(), $this->updatedAppliances)) {
                $appliance->fill(['cluster' => null])->save();
            }
        }
    }

    /**
     * @param \stdClass $data
     * @return Appliance
     * @throws \Exception
     */
    private function createAppliance(\stdClass $data): Appliance
    {
        $appliance = null;
        try {
            Appliance::getDbConnection()->beginTransaction();
            $vendor = Vendor::getInstanceByTitle($data->platformVendor);
            $platform = (new PlatformItem())->fill([
                'serialNumber' => $data->platformSerial,
                'platform' => Platform::getInstanceByVendorTitle($vendor, $data->platformTitle),
            ])->save();
            $software = (new SoftwareItem())->fill([
                'version' => $data->softwareVersion,
                'software' => Software::getInstanceByVendorTitle($vendor, $data->applianceSoft),
            ])->save();
            $appliance = (new Appliance())->fill([
                'location' => Office::findByColumn('lotusId', $data->LotusId),
                'vendor' => $vendor,
                'platform' => $platform,
                'software' => $software,
                'type' => ApplianceType::getInstanceByType($data->applianceType)
            ])->save();
            Appliance::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            throw new \Exception($e->getMessage());
        }
        return $appliance;
    }

    /**
     * Validate data structure
     * {
     *   "dataSetType",
     *   "hostname",
     *   "vrf_name",
     *   "ip",
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
     *   ]
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
    private function validateDataStructure(): bool
    {
        if (!isset($this->data->dataSetType)
            || !isset($this->data->hostname)
            || !isset($this->data->vrf_name)
            || !isset($this->data->ip)
            || !isset($this->data->clusterAppliances)
        ) {
            return false;
        }
        foreach ($this->data->clusterAppliances as $appliance) {
            if (!isset($appliance->dataSetType)
                || !isset($appliance->applianceType)
                || !isset($appliance->platformVendor)
                || !isset($appliance->platformTitle)
                || !isset($appliance->platformSerial)
                || !isset($appliance->chassis)
                || !isset($appliance->applianceSoft)
                || !isset($appliance->softwareVersion)
                || !isset($appliance->LotusId)
                || !isset($appliance->hostname)
                || !isset($appliance->ip)
                || !isset($appliance->vrf_name)
                || !isset($appliance->applianceModules)
            ) {
                return false;
            }
            foreach ($appliance->applianceModules as $module) {
                if (!isset($module->serial)
                    || !isset($module->product_number)
                    || !isset($module->description)
                ) {
                    return false;
                }
            }
        }
        return true;
    }
}
