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
use T4\Core\Exception;
use T4\Core\Std;

class DSPappliance extends Std
{
    const SLEEP_TIME = 500; // микросекунды
    const ITERATIONS = 6000000; // Колличество попыток получить доступ к db.lock файлу
    const DB_LOCK_FILE = ROOT_PATH_PROTECTED . '/db.lock';

    private $dbLockFile;


    public function process(Std $data)
    {
        // Block the dbLock file before start of the transaction
        if (false === $this->dbLock()) {
            throw new Exception('APPLIANCE UPDATE: [message]=Can not get the lock file');
        }

        try {
            // Start transaction
            Appliance::getDbConnection()->beginTransaction();

            // Location
            $location = Office::findByColumn('lotusId', $data->LotusId);
            if (false === $location) {
                throw new Exception('APPLIANCE UPDATE: [message]=Location not found, [LotusId]=' . $data->LotusId);
            }

            // Find the Appliance by the serial number
            $vendor = Vendor::findByColumn('title', $data->platformVendor);
            if (false !== $vendor) {
                $foundAppliance = PlatformItem::findByVendorSerial($vendor, $data->platformSerial)->appliance;
                if (!is_null($foundAppliance)) {
                    $appliance = $foundAppliance;
                } else {
                    if (!is_null($data->ip)) {
                        // Find the Appliance by the management IP
                        $dataPortIp = (new IpTools($data->ip))->address;
                        $dataPortVrf = Vrf::instanceGlobalVrf();

                        $foundDataPort = DataPort::findByIpVrf($dataPortIp, $dataPortVrf);
                        if (false !== $foundDataPort && true === $foundDataPort->isManagement && empty($foundDataPort->appliance->platform->serialNumber)) {
                            $appliance = $foundDataPort->appliance;
                        } else {
                            $appliance = new Appliance();
                        }
                    } else {
                        $appliance = new Appliance();
                    }
                }
            } else {
                $appliance = new Appliance();
            }

            // Vendor
            if (false === $vendor) {
                $vendor = (new Vendor())->fill([
                    'title' => $data->platformVendor,
                ]);
                $vendor->save();
            }

            // Platform
            $data->chassis = trim(preg_replace('~Cisco|CISCO|-CHASSIS~', '', $data->chassis));
            $data->chassis = trim(preg_replace('~  +~', ' ', $data->chassis));

            $platform = Platform::findByVendorTitle($vendor, $data->chassis);
            if (false === $platform) {
                $platform = (new Platform())->fill([
                    'vendor' => $vendor,
                    'title' => $data->chassis,
                ]);
                $platform->save();
            }

            // PlatformItem
            $platformItem = ($appliance->isNew()) ? new PlatformItem() : $appliance->platform;
            if (
                $appliance->isNew() ||
                $data->platformSerial != $appliance->platform->serialNumber ||
                $data->chassis != $appliance->platform->platform->title ||
                $data->platformVendor != $appliance->vendor->title
            ) {
                $platformItem->fill([
                    'platform' => $platform,
                    'serialNumber' => $data->platformSerial,
                ]);
                $platformItem->save();
            }

            // Software
            $software = Software::findByVendorTitle($vendor, $data->applianceSoft);
            if (false === $software) {
                $software = (new Software())->fill([
                    'vendor' => $vendor,
                    'title' => $data->applianceSoft,
                ]);
                $software->save();
            }

            // SoftwareItem
            $softwareItem = ($appliance->isNew()) ? new SoftwareItem() : $appliance->software;
            if (
                $appliance->isNew() ||
                $data->softwareVersion != $appliance->software->version ||
                $data->applianceSoft != $appliance->software->software->title ||
                $data->platformVendor != $appliance->vendor->title
            ) {
                $softwareItem->fill([
                    'software' => $software,
                    'version' => $data->softwareVersion,
                ]);
                $softwareItem->save();
            }

            // ApplianceType
            $applianceType = ApplianceType::findByColumn('type', $data->applianceType);
            if (false === $applianceType) {
                $applianceType = (new ApplianceType())->fill([
                    'type' => $data->applianceType,
                ]);
                $applianceType->save();
            }

            // Cluster
            $cluster = ($data->cluster instanceof Cluster) ? $data->cluster : null;

            // Appliance
            $appliance->fill([
                'vendor' => $vendor,
                'type' => $applianceType,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'location' => $location,
                'cluster' => $cluster,
                'inUse' => true,
                'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
            ]);
            if (is_null($appliance->details) || !($appliance->details instanceof Std)) {
                $appliance->details = new Std(['hostname' => $data->hostname]);
            } else {
                $appliance->details->hostname = $data->hostname;
            }
            $appliance->save();

            // Appliance's Modules
            $inUseModules = [];
            foreach ($data->applianceModules as $moduleData) {
                $moduleItem = ModuleItem::findByVendorSerial($vendor, $moduleData->serial);
                if (false === $moduleItem) {
                    $moduleItem = new ModuleItem();
                    $moduleItem->fill([
                        'inUse' => true,
                    ]);
                }
                $module = Module::findByVendorTitle($vendor, $moduleData->product_number);
                if (false === $module) {
                    $module = (new Module())->fill([
                        'vendor' => $vendor,
                        'title' => $moduleData->product_number,
                        'description' => $moduleData->description,
                    ]);
                    $module->save();
                }
                $moduleItem->fill([
                    'appliance' => $appliance,
                    'location' => $location,
                    'module' => $module,
                    'serialNumber' => $moduleData->serial,
                    'notFound' => false,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                ]);
                $moduleItem->save();
                $inUseModules[] = $moduleItem->getPk();
            }
            foreach ($appliance->modules as $module) {
                if (!in_array($module->getPk(),$inUseModules)) {
                    $module->fill([
                        'notFound' => true,
                    ]);
                    $module->save();
                }
            }

            // Appliance's Management Data Port
            if (is_null($data->ip) && is_null($appliance->cluster)) {
                throw new Exception('APPLIANCE UPDATE: [message]=Appliance does not have the management ip; [data]=' . json_encode($data));
            }
            if (!is_null($data->ip)) {
                $managementDataPortIp = (new IpTools($data->ip))->address;
                $managementDataPortVrf = Vrf::instanceGlobalVrf();
                $foundDataPort = DataPort::findByIpVrf($managementDataPortIp, $managementDataPortVrf);
                if (false !== $foundDataPort) {
                    if ($foundDataPort->appliance->getPk() == $appliance->getPk()) {
                        $managementDataPort = $foundDataPort;
                    } else {
                        $foundDataPort->delete();
                        $managementDataPort = new DataPort();
                    }
                } else {
                    $managementDataPort = new DataPort();
                }
                $managementDataPortType = DPortType::getEmpty();
                $managementDataPort->fill([
                    'appliance' => $appliance,
                    'portType' => $managementDataPortType,
                    'macAddress' => $data->macAddress,
                    'ipAddress' => $managementDataPortIp,
                    'vrf' => $managementDataPortVrf,
                    'isManagement' => true,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                ]);
                $managementDataPort->save();
                foreach ($appliance->dataPorts as $dataPort) {
                    if ($dataPort->getPk() != $managementDataPort->getPk() && true === $dataPort->isManagement) {
                        $dataPort->fill([
                            'isManagement' => false,
                        ]);
                        $dataPort->save();
                    }
                }
            }

            // End transaction
            Appliance::getDbConnection()->commitTransaction();
        } catch (Exception $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            $this->dbUnLock();
            throw new Exception($e->getMessage());
        }
        $this->dbUnLock();
        return true;
    }

    /**
     * Заблокировать db.lock файл
     *
     * @return bool
     * @throws Exception
     */
    protected function dbLock()
    {
        $this->dbLockFile = fopen(self::DB_LOCK_FILE, 'w');
        if (false === $this->dbLockFile) {
            throw new Exception('PHONE: Can not open the lock file');
        }
        $n = self::ITERATIONS;
        $blockedFile = flock($this->dbLockFile, LOCK_EX | LOCK_NB);
        while (false === $blockedFile && 0 !== $n--) {
            usleep(self::SLEEP_TIME);
            $blockedFile = flock($this->dbLockFile, LOCK_EX | LOCK_NB);
        }
        if (false === $blockedFile) {
            fclose($this->dbLockFile);
            return false;
        }
        return true;
    }

    /**
     * Разблокировать db.lock файл
     *
     * @return bool
     */
    protected function dbUnLock()
    {
        flock($this->dbLockFile, LOCK_UN);
        fclose($this->dbLockFile);
        return true;
    }
}
