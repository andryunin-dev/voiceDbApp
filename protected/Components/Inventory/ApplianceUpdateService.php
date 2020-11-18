<?php
namespace App\Components\Inventory;

use App\Components\DateTimeService;
use App\Components\IpTools;
use App\Components\StreamLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
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
use Monolog\Logger;
use T4\Core\MultiException;
use T4\Core\Std;

class ApplianceUpdateService
{
    private $appliance;

    /**
     * @param Appliance $appliance
     * @param array $data
     * @throws MultiException
     */
    public function update(Appliance $appliance, array $data): void
    {
        $this->appliance = $appliance;
        $this->updateHostname($data['hostname']);
        $this->appliance
            ->fill([
                'vendor' => $this->vendor($data['platformVendor']),
                'type' => $this->applianceType($data['applianceType']),
                'location' => $this->location($data['LotusId']),
                'platform' => $this->platformItem(
                    $data['platformSerial'],
                    $data['platformTitle'],
                    $data['chassis'],
                    $this->vendor($data['platformVendor'])),
                'software' => $this->softwareItem(
                    $data['softwareVersion'],
                    $data['applianceSoft'],
                    $this->vendor($data['platformVendor'])),
                'lastUpdate' => (new DateTimeService())->now(),
            ])
            ->save();
        $this->updateModules($data['applianceModules'], $this->appliance->vendor);
        if (!is_null($data['ip']) && !is_null($data['vrf_name'])) {
            $this->updateManagementDataPort($data['ip'], $this->vrf($data['vrf_name']));
        }
    }

    /**
     * @param string $ip
     * @param Vrf $vrf
     * @throws MultiException
     */
    private function updateManagementDataPort(string $ip, Vrf $vrf): void
    {
        $port = $this->appliance->findDataPortByIpVrf((new IpTools($ip))->address, $vrf);
        if (false === $port) {
            $this->createManagementDataPort($ip, $vrf);
        } else {
            $this->makeManageable($port);
        }
    }

    /**
     * @param string $ip
     * @param Vrf $vrf
     * @throws MultiException
     */
    private function createManagementDataPort(string $ip, Vrf $vrf): void
    {
        $ipTool = new IpTools($ip);
        $this->deleteDustDataPort($ipTool->address, $vrf);
        $port = (new DataPort())
            ->fill([
                'ipAddress' => $ipTool->address,
                'masklen' => $ipTool->masklen,
                'vrf' => $vrf,
                'isManagement' => true,
                'portType' => DPortType::getEmpty(),
                'appliance' => $this->appliance,
                'lastUpdate' => (new DateTimeService())->now(),
            ]);
        $port->save();
        if (count($port->errors) > 0) {
            $this->logger()->error('[message]=' . $port->errors[0] . ' [managementIp]=' . $ip);
        } else {
            $this->makeManageable($port);
        }
    }

    /**
     * @param string $ip
     * @param Vrf $vrf
     */
    private function deleteDustDataPort(string $ip, Vrf $vrf): void
    {
        $dataPort = DataPort::findByIpVrf($ip, $vrf);
        if (false !== $dataPort) {
            $dataPort->delete();
        }
    }

    /**
     * @param DataPort $port
     * @throws MultiException
     */
    private function makeManageable(DataPort $port): void
    {
        $dataPorts = $this->appliance->dataPorts->toArray();
        array_walk(
            $dataPorts,
            function ($dataPort) {
                if ($dataPort->isManagement) {
                    $dataPort->fill(['isManagement' => false])->save();
                }
            }
        );
        $port->fill(['isManagement' => true])->save();
    }

    /**
     * @param string $vrfName
     * @return Vrf
     * @throws MultiException
     */
    private function vrf(string $vrfName): Vrf
    {
        return Vrf::instanceWithName($vrfName);
    }

    /**
     * @param array $modulesData
     * @param Vendor $vendor
     */
    private function updateModules(array $modulesData, Vendor $vendor): void
    {
        array_walk(
            $modulesData,
            function ($moduleData) use ($vendor) {
                try {
                    $moduleItem = ModuleItem::findBySerialVendor($moduleData['serial'], $vendor);
                    if (false === $moduleItem) {
                        $moduleItem = (new ModuleItem())->fill(['serialNumber' => $moduleData['serial']]);
                    }
                    $moduleItem
                        ->fill([
                            'module' => $this->module($moduleData['product_number'], $vendor, $moduleData['description']),
                            'appliance' => $this->appliance,
                            'location' => $this->appliance->location,
                            'lastUpdate' => (new DateTimeService())->now(),
                        ])
                        ->save();
                } catch (\Throwable $e) {
                    $this->logger()->error(
                        '[message]=' . $e->getMessage() .
                        ' [module serialNumber]=' . $moduleData['serial'] .
                        ' [appliance_id]=' . $this->appliance->getPk());
                }
            }
        );
    }

    /**
     * @param string $productNumber
     * @param Vendor $vendor
     * @param string $description
     * @return Module
     * @throws MultiException
     */
    private function module(string $productNumber, Vendor $vendor, string $description): Module
    {
        $module = Module::instanceWithTitleVendor($productNumber, $vendor);
        if ($description != $module->description) {
            $module->fill(['description' => $description])->save();
        }
        return $module;
    }

    /**
     * @param string $softwareVersion
     * @param string $softwareTitle
     * @param Vendor $vendor
     * @return SoftwareItem
     * @throws MultiException
     */
    private function softwareItem(string $softwareVersion, string $softwareTitle, Vendor $vendor): SoftwareItem
    {
        if ($this->appliance->isNew()) {
            $this->appliance->software = new SoftwareItem();
        }
        return $this->appliance->software->fill([
            'version' => $softwareVersion,
            'software' => Software::instanceWithTitleVendor($softwareTitle, $vendor)
        ])->save();
    }

    /**
     * @param string $serialNumber
     * @param string $platformTitle
     * @param string $chassis
     * @param Vendor $vendor
     * @return PlatformItem
     * @throws MultiException
     */
    private function platformItem(string $serialNumber, string $platformTitle, string $chassis, Vendor $vendor): PlatformItem
    {
        if ($this->appliance->isNew()) {
            $this->appliance->platform = (new PlatformItem())->fill(['serialNumber' => $serialNumber]);
        }
        if (!empty($this->appliance->platform->serialNumber) && $serialNumber != $this->appliance->platform->serialNumber) {
            throw new \Exception('Appliance is trying to change the serialNumber(' . $this->appliance->platform->serialNumber . ') to ' . $serialNumber);
        }
        return $this->appliance->platform->fill([
            'platform' => $this->platform(
                $this->platformTitle($platformTitle, $chassis),
                $vendor
            )
        ])->save();
    }

    /**
     * @param string $platformTitle
     * @param string $chassis
     * @return string
     */
    private function platformTitle(string $platformTitle, string $chassis): string
    {
        return trim(
            mb_eregi_replace(
                ' +',
                ' ',
                mb_eregi_replace(
                    'Cisco|-CHASSIS',
                    '',
                    $chassis ?? $platformTitle)));
    }

    /**
     * @param string $title
     * @param Vendor $vendor
     * @return Platform
     * @throws MultiException
     */
    private function platform(string $title, Vendor $vendor): Platform
    {
        return Platform::instanceWithTitleVendor($title, $vendor);
    }

    /**
     * @param int $lotusId
     * @return Office
     * @throws \Exception
     */
    private function location(int $lotusId): Office
    {
        $office = Office::findByColumn('lotusId', $lotusId);
        if (false === $office) {
            throw new \Exception('Office (lotusId = ' . $lotusId . ') is not found');
        }
        return $office;
    }

    /**
     * @param string $type
     * @return ApplianceType
     * @throws MultiException
     */
    private function applianceType(string $type): ApplianceType
    {
        return ApplianceType::instanceWithType($type);
    }

    /**
     * @param string $title
     * @return Vendor
     * @throws MultiException
     */
    private function vendor(string $title): Vendor
    {
        return Vendor::instanceWithTitle($title);
    }

    /**
     * @param string $hostname
     */
    private function updateHostname(string $hostname): void
    {
        $this->checkDetails();
        $this->appliance->details->hostname = $hostname;
    }

    private function checkDetails(): void
    {
        if (is_null($this->appliance->details)) {
            $this->appliance->details = new Std();
        }
    }

    /**
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function logger(): Logger
    {
        return StreamLogger::instanceWith('DS-APPLIANCE');
    }
}
