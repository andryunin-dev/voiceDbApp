<?php
namespace App\Components;

use App\Exceptions\DblockException;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Cluster;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\PhoneInfo;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use App\Models\Vrf;
use T4\Core\Exception;
use T4\Core\Std;

class DSPphones extends Std
{
    const VENDOR = 'CISCO';
    const PHONE = 'phone';
    const PHONE_SOFT = 'Phone Soft';
    const VGC = 'vgc';
    const VGC_SOFT = '';
    const VGC_SOFT_VERSION = '';
    const DEFAULT_DATA_PORT_TYPE = 'Ethernet';
    const VIP30 = 'cisco30vip';

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
        $phoneInfo = PhoneInfo::findByColumn('name', $data->name);
        if (false === $phoneInfo) {
            if ($this->isVGC($data->name)) {
                if (!$this->createVGC($data)) {
                    return false;
                };
            } else {
                if (!$this->createPhone($data)) {
                    return false;
                };
            }
        } else {
            if ($this->isVGC($data->name)) {
                if (!$this->updateVGC($phoneInfo, $data)) {
                    return false;
                };
            } else {
                if (!$this->updatePhone($phoneInfo, $data)) {
                    return false;
                };
            }
        }
        return true;
    }


    /**
     * @param Std $data
     * @return bool
     * @throws Exception
     */
    protected function createPhone(Std $data)
    {
        $logger = RLogger::getInstance('CUCM-' . $data->publisherIp, ROOT_PATH . DS . 'Logs' . DS . 'phones_' . preg_replace('~\.~', '_', $data->publisherIp) . '.log');

        //// CREATE APPLIANCE
        $masklen = (new IpTools($data->ipAddress, $data->subNetMask))->masklen;

        $macAddress = ($data->macAddress) ?? substr($data->name, -12);
        $macAddress = implode(':', str_split(mb_strtolower(preg_replace('~[:|\-|.]~','',$macAddress)), 2));

        $modelPhone = mb_strtolower(preg_replace('~ ~','',$data->model));
        $phoneType = (self::VIP30 == $modelPhone) ? self::VIP30 : self::PHONE;

        // LOCATION for Ip Phone определяем по location defaultRouter телефона
        $defaultRouterDataPort = DataPort::findByIpVrf($data->defaultRouter, Vrf::instanceGlobalVrf());
        if (false !== $defaultRouterDataPort) {
            $location = $defaultRouterDataPort->appliance->location;
            $unknownLocation = false;
        } else {
            // Если defaultRouter телефона не определен, то офис определяем по location publisher,
            // в лог записываем, что офис телефона не верный
            $publisherIpDataPort = DataPort::findByIpVrf($data->publisherIp, Vrf::instanceGlobalVrf());
            if (false !== $publisherIpDataPort) {
                $location = $publisherIpDataPort->appliance->location;
                $unknownLocation = true;
                $logger->warning('PHONE CREATE: [message]=The office is not defined. Default router (' . $data->defaultRouter . ') is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
            } else {
                throw new Exception('PHONE CREATE: [message]=The office is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
            }
        }

        $dataAppliance = (new Std())->fill([
            'vendor' => self::VENDOR,
            'name' => $data->name,
            'ipAddress' => $data->ipAddress,
            'version' => (1 == preg_match('~6921~', $data->model)) ? (($data->appLoadID) ?? '') : (($data->versionID) ?? ''),
            'platformTitle' => ($data->modelNumber) ?? $data->model,
            'serialNumber' => ($data->serialNumber) ?? $data->name,
            'masklen' => (false === $masklen) ? null : $masklen,
            'macAddress' => $macAddress,
            'software' => self::PHONE_SOFT,
            'applianceType' => self::PHONE,
            'portType' => self::DEFAULT_DATA_PORT_TYPE,
            'vrf' => Vrf::instanceGlobalVrf(),
            'location' => $location,
            'hostname' => $data->name,
            'phoneType' => $phoneType,
        ]);
        $appliance = $this->createAppliance($dataAppliance);

        //// CREATE PHONE INFO
        $dhcpenable = mb_strtolower($data->dhcpEnabled);
        $phoneInfo = (new PhoneInfo())->fill([
            'phone' => $appliance,
            'model' => $data->model,
            'name' => $data->name,
            'prefix' => preg_replace('~\..+~','',$data->prefix),
            'phoneDN' => $data->phonedn,
            'status' => $data->status,
            'description' => $data->description,
            'css' => $data->css,
            'devicePool' => $data->devicepool,
            'alertingName' => $data->alertingname,
            'partition' => $data->partition,
            'timezone' => $data->timezone,
            'domainName' => ('Нет' == $data->domainName) ? null : $data->domainName,
            'dhcpEnabled' => ('yes' == $dhcpenable || 1 == $dhcpenable || 'да' == $dhcpenable) ? true : false,
            'dhcpServer' => (false === ($dhcpIp = (new IpTools(($data->dhcpServer) ?? ''))->address)) ? null : $dhcpIp,
            'tftpServer1' => (false === ($tftp1Ip = (new IpTools(($data->tftpServer1) ?? ''))->address)) ? null : $tftp1Ip,
            'tftpServer2' => (false === ($tftp2Ip = (new IpTools(($data->tftpServer2) ?? ''))->address)) ? null : $tftp2Ip,
            'defaultRouter' => (false === ($routerIp = (new IpTools(($data->defaultRouter) ?? ''))->address)) ? null : $routerIp,
            'dnsServer1' => (false === ($dns1Ip = (new IpTools(($data->dnsServer1) ?? ''))->address)) ? null : $dns1Ip,
            'dnsServer2' => (false === ($dns2Ip = (new IpTools(($data->dnsServer2) ?? ''))->address)) ? null : $dns2Ip,
            'callManager1' => (empty($callManager1 = preg_replace('~[ ]+~', ' ', $data->callManager1))) ? null : $callManager1,
            'callManager2' => (empty($callManager2 = preg_replace('~[ ]+~', ' ', $data->callManager2))) ? null : $callManager2,
            'callManager3' => (empty($callManager3 = preg_replace('~[ ]+~', ' ', $data->callManager3))) ? null : $callManager3,
            'callManager4' => (empty($callManager4 = preg_replace('~[ ]+~', ' ', $data->callManager4))) ? null : $callManager4,
            'vlanId' => (int)$data->vlanId,
            'userLocale' => $data->userLocale,
            'cdpNeighborDeviceId' => $data->cdpNeighborDeviceId,
            'cdpNeighborIP' => (false === ($neighborIp = (new IpTools(($data->cdpNeighborIP) ?? ''))->address)) ? null : $neighborIp,
            'cdpNeighborPort' => $data->cdpNeighborPort,
            'publisherIp' => $data->publisherIp,
            'unknownLocation' => $unknownLocation,
        ]);
        $phoneInfo->save();
        return true;
    }

    /**
     * @param PhoneInfo $phoneInfo
     * @param Std $data
     * @return bool
     * @throws Exception
     */
    protected function updatePhone(PhoneInfo $phoneInfo, Std $data)
    {
        $logger = RLogger::getInstance('CUCM-' . $data->publisherIp, ROOT_PATH . DS . 'Logs' . DS . 'phones_' . preg_replace('~\.~', '_', $data->publisherIp) . '.log');

        //// UPDATE APPLIANCE
        $masklen = (new IpTools($data->ipAddress, $data->subNetMask))->masklen;
        $macAddress = ($data->macAddress) ?? substr($data->name, -12);

        $modelPhone = mb_strtolower(preg_replace('~ ~','',$data->model));
        $phoneType = (self::VIP30 == $modelPhone) ? self::VIP30 : self::PHONE;

        // UPDATE LOCATION for Ip Phone - определяем по defaultRouter's location телефона
        $defaultRouterDataPort = DataPort::findByIpVrf($data->defaultRouter, Vrf::instanceGlobalVrf());
        if (false !== $defaultRouterDataPort) {
            $location = $defaultRouterDataPort->appliance->location;
            $unknownLocation = false;
        } else {
            // Если defaultRouter телефона не определен, то офис определяем по publisher's location,
            // в лог записываем, что офис телефона не верный
            $publisherIpDataPort = DataPort::findByIpVrf($data->publisherIp, Vrf::instanceGlobalVrf());
            if (false !== $publisherIpDataPort) {
                $location = $publisherIpDataPort->appliance->location;
                $unknownLocation = true;
                $logger->warning('PHONE UPDATE: [message]=The office is not defined. Default router (' . $data->defaultRouter . ') is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
            } else {
                throw new Exception('PHONE UPDATE: [message]=The office is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
            }
        }

        $dataAppliance = (new Std())->fill([
            'vendor' => self::VENDOR,
            'software' => self::PHONE_SOFT,
            'version' => (1 == preg_match('~6921~', $data->model)) ? (($data->appLoadID) ?? '') : (($data->versionID) ?? ''),
            'portType' => self::DEFAULT_DATA_PORT_TYPE,
            'macAddress' => $macAddress,
            'vrf' => Vrf::instanceGlobalVrf(),
            'ipAddress' => $data->ipAddress,
            'masklen' => (false === $masklen) ? null : $masklen,
            'location' => $location,
            'hostname' => $data->name,
            'phoneType' => $phoneType,
        ]);
        $this->updateAppliance($phoneInfo->phone, $dataAppliance);

        //// UPDATE PHONE INFO
        $dhcpenable = mb_strtolower($data->dhcpEnabled);
        $phoneInfo->fill([
            'prefix' => preg_replace('~\..+~','',$data->prefix),
            'phoneDN' => $data->phonedn,
            'status' => $data->status,
            'description' => $data->description,
            'css' => $data->css,
            'devicePool' => $data->devicepool,
            'alertingName' => $data->alertingname,
            'partition' => $data->partition,
            'timezone' => $data->timezone,
            'domainName' => ('Нет' == $data->domainName) ? null : $data->domainName,
            'dhcpEnabled' => ('yes' == $dhcpenable || 1 == $dhcpenable || 'да' == $dhcpenable) ? true : false,
            'dhcpServer' => (false === ($dhcpIp = (new IpTools(($data->dhcpServer) ?? ''))->address)) ? null : $dhcpIp,
            'tftpServer1' => (false === ($tftp1Ip = (new IpTools(($data->tftpServer1) ?? ''))->address)) ? null : $tftp1Ip,
            'tftpServer2' => (false === ($tftp2Ip = (new IpTools(($data->tftpServer2) ?? ''))->address)) ? null : $tftp2Ip,
            'defaultRouter' => (false === ($routerIp = (new IpTools(($data->defaultRouter) ?? ''))->address)) ? null : $routerIp,
            'dnsServer1' => (false === ($dns1Ip = (new IpTools(($data->dnsServer1) ?? ''))->address)) ? null : $dns1Ip,
            'dnsServer2' => (false === ($dns2Ip = (new IpTools(($data->dnsServer2) ?? ''))->address)) ? null : $dns2Ip,
            'callManager1' => (empty($callManager1 = preg_replace('~[ ]+~', ' ', $data->callManager1))) ? null : $callManager1,
            'callManager2' => (empty($callManager2 = preg_replace('~[ ]+~', ' ', $data->callManager2))) ? null : $callManager2,
            'callManager3' => (empty($callManager3 = preg_replace('~[ ]+~', ' ', $data->callManager3))) ? null : $callManager3,
            'callManager4' => (empty($callManager4 = preg_replace('~[ ]+~', ' ', $data->callManager4))) ? null : $callManager4,
            'vlanId' => (int)$data->vlanId,
            'userLocale' => $data->userLocale,
            'cdpNeighborDeviceId' => $data->cdpNeighborDeviceId,
            'cdpNeighborIP' => (false === ($neighborIp = (new IpTools(($data->cdpNeighborIP) ?? ''))->address)) ? null : $neighborIp,
            'cdpNeighborPort' => $data->cdpNeighborPort,
            'publisherIp' => $data->publisherIp,
            'unknownLocation' => $unknownLocation,
        ]);
        $phoneInfo->save();
        return true;
    }


    /**
     * @param Std $data
     * @return bool
     * @throws Exception
     */
    protected function createVGC(Std $data)
    {
        //// CREATE APPLIANCE
        // LOCATION for VGC PORT определяем по устройству VG
        $vgDataPort = DataPort::findByIpVrf($data->ipAddress, Vrf::instanceGlobalVrf());
        if (false === $vgDataPort) {
            throw new Exception('VGC PORT CREATE: [message]=The office is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
        }
        $vg = $vgDataPort->appliance;
        $unknownLocation = false;

        // CLUSTER for VGC PORT определяем по устройству VG
        $hostnameVG = $vg->details->hostname;
        if (empty($hostnameVG)) {
            throw new Exception('VGC PORT CREATE: [message]=The cluster is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
        }
        $cluster = $vg->cluster;
        if (is_null($cluster)) {
            $cluster = Cluster::findByColumn('title', $hostnameVG);
            if (false === $cluster) {
                $cluster = (new Cluster())->fill([
                    'title' => $hostnameVG,
                ]);
                $cluster->save();
            }
            $vg->fill([
                'cluster' => $cluster,
            ]);
            $vg->save();
        }

        $dataAppliance = (new Std())->fill([
            'vendor' => self::VENDOR,
            'software' => self::VGC_SOFT,
            'version' => self::VGC_SOFT_VERSION,
            'platformTitle' => $data->model,
            'serialNumber' => $data->name,
            'applianceType' => self::PHONE,
            'location' => $vg->location,
            'hostname' => $hostnameVG,
            'cluster' => $cluster,
            'phoneType' => self::VGC,
        ]);
        $appliance = $this->createAppliance($dataAppliance);

        //// CREATE PHONE INFO
        $phoneInfo = (new PhoneInfo())->fill([
            'phone' => $appliance,
            'model' => $data->model,
            'name' => $data->name,
            'prefix' => preg_replace('~\..+~','',$data->prefix),
            'phoneDN' => $data->phonedn,
            'status' => $data->status,
            'description' => $data->description,
            'css' => $data->css,
            'devicePool' => $data->devicepool,
            'alertingName' => $data->alertingname,
            'partition' => $data->partition,
            'publisherIp' => $data->publisherIp,
            'dhcpEnabled' => false,
            'unknownLocation' => $unknownLocation,
        ]);
        $phoneInfo->save();
        return true;
    }

    /**
     * @param PhoneInfo $phoneInfo
     * @param Std $data
     * @return bool
     * @throws Exception
     */
    protected function updateVGC(PhoneInfo $phoneInfo, Std $data)
    {
        //// UPDATE APPLIANCE
        // UPDATE LOCATION for VGC PORT - определяем по устройству VG
        $vgDataPort = DataPort::findByIpVrf($data->ipAddress, Vrf::instanceGlobalVrf());
        if (false === $vgDataPort) {
            throw new Exception('VGC PORT CREATE: [message]=The office is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
        }
        $vg = $vgDataPort->appliance;
        $unknownLocation = false;

        // UPDATE CLUSTER for VGC PORT - определяем по устройству VG
        $hostnameVG = $vg->details->hostname;
        if (empty($hostnameVG)) {
            throw new Exception('VGC PORT CREATE: [message]=The cluster is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
        }
        $cluster = $vg->cluster;
        if (is_null($cluster)) {
            $cluster = Cluster::findByColumn('title', $hostnameVG);
            if (false === $cluster) {
                $cluster = (new Cluster())->fill([
                    'title' => $hostnameVG,
                ]);
                $cluster->save();
            }
            $vg->fill([
                'cluster' => $cluster,
            ]);
            $vg->save();
        }

        $dataAppliance = (new Std())->fill([
            'vendor' => self::VENDOR,
            'software' => self::VGC_SOFT,
            'version' => self::VGC_SOFT_VERSION,
            'location' => $vg->location,
            'hostname' => $hostnameVG,
            'cluster' => $cluster,
            'phoneType' => self::VGC,
        ]);
        $this->updateAppliance($phoneInfo->phone, $dataAppliance);

        //// UPDATE PHONE INFO
        $phoneInfo->fill([
            'model' => $data->model,
            'prefix' => preg_replace('~\..+~','',$data->prefix),
            'phoneDN' => $data->phonedn,
            'status' => $data->status,
            'description' => $data->description,
            'css' => $data->css,
            'devicePool' => $data->devicepool,
            'alertingName' => $data->alertingname,
            'partition' => $data->partition,
            'publisherIp' => $data->publisherIp,
            'dhcpEnabled' => false,
            'unknownLocation' => $unknownLocation,
        ]);
        $phoneInfo->save();
        return true;
    }


    /**
     * @param Std $data
     * @return Appliance
     * @throws Exception
     */
    protected function createAppliance(Std $data)
    {
        // Block the dbLock file before start of the transaction
        if (false === $this->dbLock()) {
            throw new Exception('PHONE CREATE: Can not get the lock file');
        }

        try {
            // Start transaction
            Appliance::getDbConnection()->beginTransaction();

            // create Appliance - define VENDOR
            $vendor = Vendor::findByColumn('title', $data->vendor);
            if (false === $vendor) {
                $vendor = (new Vendor())->fill([
                    'title' => $data->vendor,
                ]);
                $vendor->save();
            }

            // create Appliance - define SOFTWARE
            $software = Software::findByColumn('title', $data->software);
            if (false === $software) {
                $software = (new Software())->fill([
                    'vendor' => $vendor,
                    'title' => $data->software,
                ]);
                $software->save();
            }

            // create Appliance - define SOFTWARE ITEM for Ip Phone
            $softwareItem = (new SoftwareItem())->fill([
                'software' => $software,
                'version' => $data->version,
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
                'serialNumber' => $data->serialNumber,
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
                'location' => $data->location,
                'cluster' => ($data->cluster) ?? null,
                'details' => [
                    'hostname' => $data->hostname,
                ],
                'inUse' => true,
                'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
            ]);
            $appliance->save();

            // create DATA PORT for Appliance
            if (self::VGC != $data->phoneType && self::VIP30 != $data->phoneType) {
                $portType = DPortType::findByColumn('type', $data->portType);
                if (false === $portType) {
                    $portType = (new DPortType())->fill([
                        'type' => $data->portType,
                    ]);
                    $portType->save();
                }
                $existDataPort = DataPort::findByIpVrf($data->ipAddress, $data->vrf);
                if (false !== $existDataPort) {
                    $existDataPort->delete();
                }
                $dataPort = (new DataPort())->fill([
                    'appliance' => $appliance,
                    'portType' => $portType,
                    'macAddress' => $data->macAddress,
                    'ipAddress' => $data->ipAddress,
                    'vrf' => $data->vrf,
                    'masklen' => $data->masklen,
                    'isManagement' => true,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                ]);
                $dataPort->save();
            }

            // End transaction
            Appliance::getDbConnection()->commitTransaction();
        } catch (Exception $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            $this->dbUnLock();
            throw new Exception($e->getMessage());
        }
        $this->dbUnLock();
        return $appliance;
    }

    /**
     * @param Appliance $appliance
     * @param Std $data
     * @return Appliance
     * @throws Exception
     */
    protected function updateAppliance(Appliance $appliance, Std $data)
    {
        // Block the dbLock file before start of the transaction
        if (false === $this->dbLock()) {
            throw new Exception('PHONE CREATE: Can not get the lock file');
        }

        try {
            // Start transaction
            Appliance::getDbConnection()->beginTransaction();

            // UPDATE LOCATION
            if ($data->location->lotusId != $appliance->location->lotusId) {
                $appliance->fill([
                    'location' => $data->location,
                ]);
            }

            // UPDATE SOFTWARE
            if ($data->software != $appliance->software->software->title) {
                $software = Software::findByColumn('title', $data->software);
                if (false === $software) {
                    $software = (new Software())->fill([
                        'vendor' => Vendor::findByColumn('title', $data->vendor),
                        'title' => $data->software,
                    ]);
                    $software->save();
                }
                $appliance->software->fill([
                    'software' => $software,
                ]);
                $appliance->software->save();
            }

            // UPDATE SOFTWARE ITEM
            if ($data->version != $appliance->software->version) {
                $appliance->software->fill([
                    'version' => $data->version,
                ]);
                $appliance->software->save();
            }

            // UPDATE CLUSTER
            if (self::VGC == $data->phoneType && $data->cluster->title != $appliance->cluster->title) {
                $appliance->fill([
                    'cluster' => $data->cluster,
                ]);
            }

            // UPDATE APPLIANCE
            if (is_null($appliance->details) || !$appliance->details instanceof Std) {
                $appliance->details = new Std(['hostname' => $data->hostname]);
            } else {
                $appliance->details->hostname = $data->hostname;
            }
            $appliance->fill([
                'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
            ]);
            $appliance->save();

            // UPDATE DATA PORT
            if (self::VGC != $data->phoneType && self::VIP30 != $data->phoneType) {
                $foundDataPort = DataPort::findByIpVrf($data->ipAddress, $data->vrf);
                $foundDataPortMac = mb_strtolower(preg_replace('~[:|\-|.]~','',$foundDataPort->macAddress));
                $dataPortDataMac = mb_strtolower(preg_replace('~[:|\-|.]~','',$data->macAddress));
                if (false !== $foundDataPort && $foundDataPortMac == $dataPortDataMac) {
                    $phoneDataPort = $foundDataPort;
                } else {
                    if (false !== $foundDataPort) {
                        $foundDataPort->delete();
                    }
                    $phoneDataPort = $appliance->dataPorts->first();
                    if (is_null($phoneDataPort)) {
                        $phoneDataPort = new DataPort();
                    }
                }
                $portType = DPortType::findByColumn('type', $data->portType);
                if (false === $portType) {
                    $portType = (new DPortType())->fill([
                        'type' => $data->portType,
                    ]);
                    $portType->save();
                }
                $phoneDataPort->fill([
                    'appliance' => $appliance,
                    'portType' => $portType,
                    'macAddress' => implode(':', str_split($dataPortDataMac, 2)),
                    'ipAddress' => $data->ipAddress,
                    'vrf' => $data->vrf,
                    'masklen' => $data->masklen,
                    'isManagement' => true,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                ]);
                $phoneDataPort->save();
                if (1 < $appliance->dataPorts->count()) {
                    foreach ($appliance->dataPorts as $dataPort) {
                        if ($dataPort->getPk() != $phoneDataPort->getPk()) {
                            $dataPort->delete();
                        }
                    }
                }
            }
            if (self::VGC == $data->phoneType || self::VIP30 == $data->phoneType) {
                // У VGC PORT недолжно быть data ports, так как он должен быть в кластере
                if (0 < $appliance->dataPorts->count()) {
                    foreach ($appliance->dataPorts as $dataPort) {
                        $dataPort->delete();
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
        return $appliance;
    }


    /**
     * @param string $name
     * @return bool
     */
    protected function isVGC(string $name)
    {
        return (1 == preg_match('~^vgc|an~', mb_strtolower($name))) ? true : false;
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
