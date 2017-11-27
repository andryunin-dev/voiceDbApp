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


    /**
     * @param Std $data
     * @return bool
     */
    public function process(Std $data)
    {
        $vendor = Vendor::findByColumn('title', $data->platformVendor);
        if (false !== $vendor) {
            // Find the Appliance by the serial number
            $appliance = PlatformItem::findByVendorSerial($vendor, $data->platformSerial)->appliance;
            if (!is_null($appliance)) {
                return $this->updateAppliance($appliance, $data);
            } else {
                // Find the Appliance by the management IP
                $dataPortIp = (new IpTools($data->ip))->address;
                $dataPortVrf = Vrf::instanceGlobalVrf();
                $foundDataPort = DataPort::findByIpVrf($dataPortIp, $dataPortVrf);
                if (false !== $foundDataPort && true === $foundDataPort->isManagement && empty($foundDataPort->appliance->platform->serialNumber)) {
                    $appliance = $foundDataPort->appliance;
                    return $this->updateAppliance($appliance, $data);
                } else {
                    return $this->createAppliance($data);
                }
            }
        } else {
            return $this->createAppliance($data);
        }
    }

    /**
     * @param Std $data
     * @return bool
     * @throws Exception
     */
    protected function createAppliance(Std $data)
    {
        // create Appliance - define LOCATION
        $location = Office::findByColumn('lotusId', $data->LotusId);
        if (false === $location) {
            throw new Exception('APPLIANCE CREATE: [message]=Location not found, [LotusId]=' . $data->LotusId);
        }

        // Block the dbLock file before start of the transaction
        if (false === $this->dbLock()) {
            throw new Exception('APPLIANCE CREATE: [message]=Can not get the lock file');
        }

        try {
            // Start transaction
            Appliance::getDbConnection()->beginTransaction();

            // create Appliance - define VENDOR
            $vendor = Vendor::findByColumn('title', $data->platformVendor);
            if (false === $vendor) {
                $vendor = (new Vendor())->fill([
                    'title' => $data->platformVendor,
                ]);
                $vendor->save();
            }

            // create Appliance - define SOFTWARE
            $software = Software::findByColumn('title', $data->applianceSoft);
            if (false === $software) {
                $software = (new Software())->fill([
                    'vendor' => $vendor,
                    'title' => $data->applianceSoft,
                ]);
                $software->save();
            }

            // create Appliance - define SOFTWARE ITEM for Ip Phone
            $softwareItem = (new SoftwareItem())->fill([
                'software' => $software,
                'version' => $data->softwareVersion,
            ]);
            $softwareItem->save();

            // create Appliance - define PLATFORM
            $platform = Platform::findByColumn('title', $data->platformTitle);
            if (false === $platform) {
                $platform = (new Platform())->fill([
                    'vendor' => $vendor,
                    'title' => $data->platformTitle,
                ]);
                $platform->save();
            }

            // create Appliance - define PLATFORM ITEM
            $platformItem = (new PlatformItem())->fill([
                'platform' => $platform,
                'serialNumber' => $data->platformSerial,
            ]);
            $platformItem->save();

            // create Appliance - define APPLIANCE TYPE
            $applianceType = ApplianceType::findByColumn('type', $data->applianceType);
            if (false === $applianceType) {
                $applianceType = (new ApplianceType())->fill([
                    'type' => $data->applianceType,
                ]);
                $applianceType->save();
            }

            // create Appliance
            $appliance = (new Appliance())->fill([
                'vendor' => $vendor,
                'type' => $applianceType,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'location' => $location,
                'cluster' => ($data->cluster instanceof Cluster) ? $data->cluster : null,
                'details' => [
                    'hostname' => $data->hostname,
                ],
                'inUse' => true,
                'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
            ]);
            $appliance->save();

            // create appliance's MODULES
            foreach ($data->applianceModules as $moduleData) {
                $module = Module::findByVendorTitle($vendor, $moduleData->product_number);
                if (false === $module) {
                    $module = (new Module())->fill([
                        'vendor' => $vendor,
                        'title' => $moduleData->product_number,
                        'description' => $moduleData->description,
                    ]);
                    $module->save();
                }
                $moduleItem = (new ModuleItem())->fill([
                    'appliance' => $appliance,
                    'location' => $location,
                    'module' => $module,
                    'serialNumber' => $moduleData->serial,
                    'inUse' => true,
                    'notFound' => false,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                ]);
                $moduleItem->save();
            }

            // create MANAGEMENT DATA PORT for Appliance
            if (is_null($appliance->cluster) && is_null($data->ip)) {
                throw new Exception('APPLIANCE CREATE: [message]=Appliance does not have the management ip; [data]=' . json_encode($data));
            }
            if ((is_null($appliance->cluster) && !is_null($data->ip)) || (!is_null($appliance->cluster) && !is_null($data->ip))) {
                $managementDataPortIp = (new IpTools($data->ip))->address;
                $managementDataPortType = DPortType::getEmpty();
                $managementDataPortVrf = Vrf::instanceGlobalVrf();
                $existDataPort = DataPort::findByIpVrf($managementDataPortIp, $managementDataPortVrf);
                if (false !== $existDataPort) {
                    $existDataPort->delete();
                }
                $managementDataPort = (new DataPort())->fill([
                    'appliance' => $appliance,
                    'portType' => $managementDataPortType,
                    'macAddress' => $data->macAddress,
                    'ipAddress' => $managementDataPortIp,
                    'vrf' => $managementDataPortVrf,
                    'isManagement' => true,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                ]);
                $managementDataPort->save();
            }

            // End transaction
            Appliance::getDbConnection()->commitTransaction();
        } catch (\Throwable $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            $this->dbUnLock();
            throw new Exception($e->getMessage());
        }
        $this->dbUnLock();
        return true;
    }

    /**
     * @param Appliance $appliance
     * @param Std $data
     * @return bool
     * @throws Exception
     */
    protected function updateAppliance(Appliance $appliance, Std $data)
    {
        // Update LOCATION
        if ($data->LotusId != $appliance->location->lotusId) {
            $location = Office::findByColumn('lotusId', $data->LotusId);
            if (false === $location) {
                throw new Exception('APPLIANCE UPDATE: [message]=Location not found, [LotusId]=' . $data->LotusId);
            }
            $appliance->fill([
                'location' => $location,
            ]);
        } else {
            $location = $appliance->location;
        }

        // Block the dbLock file before start of the transaction
        if (false === $this->dbLock()) {
            throw new Exception('APPLIANCE UPDATE: [message]=Can not get the lock file');
        }

        try {
            // Start transaction
            Appliance::getDbConnection()->beginTransaction();

            // Update VENDOR
            $vendor = $appliance->vendor;

            // Update SOFTWARE
            if ($data->applianceSoft != $appliance->software->software->title) {
                $software = Software::findByColumn('title', $data->applianceSoft);
                if (false === $software) {
                    $software = (new Software())->fill([
                        'vendor' => $vendor,
                        'title' => $data->applianceSoft,
                    ]);
                    $software->save();
                }
                $appliance->software->fill([
                    'software' => $software,
                ]);
                $appliance->software->save();
            }

            // Update SOFTWARE ITEM
            if ($data->softwareVersion != $appliance->software->version) {
                $appliance->software->fill([
                    'version' => $data->softwareVersion,
                ]);
                $appliance->software->save();
            }

            // Update CLUSTER
            //($data->cluster instanceof Cluster) ? $data->cluster : null
            if (($data->cluster instanceof Cluster) && ($data->cluster->title != $appliance->cluster->title)) {
                $appliance->fill([
                    'cluster' => $data->cluster,
                ]);
            }

            // Update APPLIANCE
            if (is_null($appliance->details) || !$appliance->details instanceof Std) {
                $appliance->details = new Std(['hostname' => $data->hostname]);
            } else {
                $appliance->details->hostname = $data->hostname;
            }
            $appliance->fill([
                'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
            ]);
            $appliance->save();

            // Update appliance's MODULES (in use)
            $inUseModules = [];
            foreach ($data->applianceModules as $moduleData) {
                $foundModuleItem = ModuleItem::findByVendorSerial($vendor, $moduleData->serial);
                if (false !== $foundModuleItem && $foundModuleItem->appliance->getPk() == $appliance->getPk()) {
                    $moduleItem = $foundModuleItem;
                    $moduleItem->fill([
                        'notFound' => false,
                        'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                    ]);
                    $moduleItem->save();
                    $inUseModules[] = $moduleItem->getPk();
                } else {
                    if (false !== $foundModuleItem) {
                        $foundModuleItem->delete();
                    }
                    // create appliance's MODULE
                    $module = Module::findByVendorTitle($vendor, $moduleData->product_number);
                    if (false === $module) {
                        $module = (new Module())->fill([
                            'vendor' => $vendor,
                            'title' => $moduleData->product_number,
                            'description' => $moduleData->description,
                        ]);
                        $module->save();
                    }
                    $moduleItem = (new ModuleItem())->fill([
                        'appliance' => $appliance,
                        'location' => $location,
                        'module' => $module,
                        'serialNumber' => $moduleData->serial,
                        'inUse' => true,
                        'notFound' => false,
                        'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                    ]);
                    $moduleItem->save();
                    $inUseModules[] = $moduleItem->getPk();
                }
            }
            foreach ($appliance->modules as $module) {
                if (!in_array($module->getPk(),$inUseModules)) {
                    $module->fill([
                        'notFound' => true,
                    ]);
                    $module->save();
                }
            }

            // Update MANAGEMENT DATA PORT
            if (is_null($appliance->cluster) && is_null($data->ip)) {
                throw new Exception('APPLIANCE UPDATE: [message]=Appliance does not have the management ip; [data]=' . json_encode($data));
            }
            if ((is_null($appliance->cluster) && !is_null($data->ip)) || (!is_null($appliance->cluster) && !is_null($data->ip))) {
                $managementDataPortIp = (new IpTools($data->ip))->address;
                $managementDataPortVrf = Vrf::instanceGlobalVrf();
                $foundDataPort = DataPort::findByIpVrf($managementDataPortIp, $managementDataPortVrf);
                if (false !== $foundDataPort && $foundDataPort->appliance->getPk() == $appliance->getPk()) {
                    $managementDataPort = $foundDataPort;
                } else {
                    if (false !== $foundDataPort) {
                        $foundDataPort->delete();
                    }
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
                if (1 < $appliance->dataPorts->count()) {
                    foreach ($appliance->dataPorts as $dataPort) {
                        if ($dataPort->getPk() != $managementDataPort->getPk() && true === $dataPort->isManagement) {
                            $dataPort->fill([
                                'isManagement' => false,
                            ]);
                            $dataPort->save();
                        }
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
