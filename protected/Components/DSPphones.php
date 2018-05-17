<?php

namespace App\Components;

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


    public function process(Std $data)
    {
        $logger = RLogger::getInstance('CUCM-' . $data->publisherIp, ROOT_PATH . DS . 'Logs' . DS . 'phones_' . preg_replace('~\.~', '_', $data->publisherIp) . '.log');

        // Block the dbLock file before start of the transaction
        if (false === $this->dbLock()) {
            throw new Exception('PHONE CREATE: Can not get the lock file');
        }

        try {
            // Start transaction
            Appliance::getDbConnection()->beginTransaction();

            // Defind PhoneInfo and Appliance
            $phoneInfo = PhoneInfo::findByColumn('name', $data->name);
            if (false !== $phoneInfo) {
                $appliance = $phoneInfo->phone;
            } else {
                $phoneInfo = new PhoneInfo();
                $appliance = new Appliance();
            }

            // (VGC | Phone) - Location, cluster, softwareTitle, softwareVersion, phone's type
            if ($this->isVGC($data->name)) {
                // Phone's type
                $phoneType = self::VGC;

                // SoftwareTitle
                $softwareTitle = self::VGC_SOFT;

                // SoftwareVersion
                $softwareVersion = self::VGC_SOFT_VERSION;

                // Location for VGC PORT определяем по устройству VG
                $vgDataPort = DataPort::findByIpVrf($data->ipAddress, Vrf::instanceGlobalVrf());
                if (false === $vgDataPort) {
                    throw new Exception('VGC PORT: [message]=The office is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
                }
                $vg = $vgDataPort->appliance;
                $location = $vg->location;
                $unknownLocation = false;

                // Cluster for VGC PORT определяем по устройству VG
                $vgHostname = $vg->details->hostname;
                if (empty($vgHostname)) {
                    throw new Exception('VGC PORT CREATE: [message]=The cluster is not defined; [name]=' . $data->name . '; [ip]=' . $data->ipAddress);
                }
                $cluster = $vg->cluster;
                if (is_null($cluster)) {
                    $cluster = Cluster::findByColumn('title', $vgHostname);
                    if (false === $cluster) {
                        $cluster = (new Cluster())->fill([
                            'title' => $vgHostname,
                        ]);
                        $cluster->save();
                    }
                    $vg->fill([
                        'cluster' => $cluster,
                    ]);
                    $vg->save();
                }

                $appliance->fill([
                    'cluster' => $cluster,
                ]);
            } else {
                // Phone's type
                $modelPhone = mb_strtolower(preg_replace('~ ~','',$data->model));
                $phoneType = (self::VIP30 == $modelPhone) ? self::VIP30 : self::PHONE;

                // SoftwareTitle
                $softwareTitle = self::PHONE_SOFT;

                // SoftwareVersion
                $softwareVersion = (1 == preg_match('~6921~', $data->model)) ? $data->appLoadID : $data->versionID;

                // Location for Ip Phone определяем по location defaultRouter телефона
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
            }

            // Vendor
            $vendor = Vendor::findByColumn('title', self::VENDOR);

            // Platform
            $model = (!empty($data->modelNumber)) ? $data->modelNumber : $data->model;
            preg_match('~\d+~', $model, $numericModelCode);
            switch ($numericModelCode[0]) {
                case '6921';
                    $model = 'CP-6921';
                    break;
                case '7905';
                    $model = 'CP-7905G';
                    break;
                case '7911';
                    $model = 'CP-7911G';
                    break;
                case '7912';
                    $model = 'CP-7912G';
                    break;
                case '7936';
                    $model = 'CP-7936';
                    break;
                case '7937';
                    $model = 'CP-7937';
                    break;
                case '7940';
                    $model = 'CP-7940G';
                    break;
                case '7942';
                    $model = 'CP-7942G';
                    break;
                case '7945';
                    $model = 'CP-7945G';
                    break;
                case '7960';
                    $model = 'CP-7960G';
                    break;
                case '7965';
                    $model = 'CP-7965G';
                    break;
                case '7975';
                    $model = 'CP-7975G';
                    break;
                case '8831';
                    $model = 'CP-8831';
                    break;
                case '8865';
                    $model = 'CP-8865';
                    break;
                case '8945';
                    $model = 'CP-8945';
                    break;
                default:
                    preg_match('~communicator~', mb_strtolower($model), $modelName);
                    if ('communicator' == $modelName[0]) {
                        $model = 'Communicator';
                    } else {
                        $model = trim(preg_replace('~Cisco|CISCO~', '', $model));
                        $model = trim(preg_replace('~  +~', ' ', $model));
                    }
            }

            $platform = Platform::findByVendorTitle($vendor, $model);
            if (false === $platform) {
                $platform = (new Platform())->fill([
                    'vendor' => $vendor,
                    'title' => $model,
                ]);
                $platform->save();
            }

            // PlatformItem
            $platformItem = ($appliance->isNew()) ? new PlatformItem() : $appliance->platform;
            $serialNumber = (!empty($data->serialNumber)) ? $data->serialNumber : $data->name;
            if (
                $appliance->isNew() ||
                $serialNumber != $appliance->platform->serialNumber ||
                $model != $appliance->platform->platform->title ||
                $vendor->title != $appliance->vendor->title
            ) {
                $platformItem->fill([
                    'platform' => $platform,
                    'serialNumber' => $serialNumber,
                ]);
                $platformItem->save();
            }

            // Software
            $software = Software::findByVendorTitle($vendor, $softwareTitle);
            if (false === $software) {
                $software = (new Software())->fill([
                    'vendor' => $vendor,
                    'title' => $softwareTitle,
                ]);
                $software->save();
            }

            // SoftwareItem
            $softwareItem = ($appliance->isNew()) ? new SoftwareItem() : $appliance->software;
            if (
                $appliance->isNew() ||
                $softwareVersion != $appliance->software->version ||
                $softwareTitle != $appliance->software->software->title ||
                $vendor->title != $appliance->vendor->title
            ) {
                $softwareItem->fill([
                    'software' => $software,
                    'version' => $softwareVersion,
                ]);
                $softwareItem->save();
            }

            // ApplianceType
            $applianceType = ApplianceType::findByColumn('type', self::PHONE);
            if (false === $applianceType) {
                $applianceType = (new ApplianceType())->fill([
                    'type' => self::PHONE,
                ]);
                $applianceType->save();
            }

            // Appliance
            $appliance->fill([
                'vendor' => $vendor,
                'type' => $applianceType,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'location' => $location,
                'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
            ]);
            if ($appliance->isNew()) {
                $appliance->fill([
                    'inUse' => true,
                ]);
            }
            if (is_null($appliance->details) || !($appliance->details instanceof Std)) {
                $appliance->details = new Std(['hostname' => $data->name]);
            } else {
                $appliance->details->hostname = $data->name;
            }
            $appliance->save();

            // Appliance's Management Data Port
            if (is_null($data->ipAddress) && is_null($appliance->cluster)) {
                throw new Exception('APPLIANCE UPDATE: [message]=Phone does not have the management ip; [data]=' . json_encode($data));
            }
            if (!is_null($data->ipAddress)) {
                if (self::VGC != $phoneType && self::VIP30 != $phoneType) {
                    // IpAddress
                    $ipAddress = (new IpTools($data->ipAddress))->address;

                    // Vrf
                    $vrf = Vrf::instanceGlobalVrf();

                    // Masklen
                    $masklen = (new IpTools($data->ipAddress, $data->subNetMask))->masklen;
                    $masklen = (false === $masklen) ? null : $masklen;

                    // Macaddress
                    if (is_null($data->macAddress)) {
                        $macAddress = (1 == preg_match('~SEP~', mb_strtoupper($data->name))) ? substr($data->name, -12) : null;
                    } else {
                        $macAddress = $data->macAddress;
                    }
                    if (!is_null($macAddress)) {
                        $macAddress = implode(':', str_split(mb_strtolower(preg_replace('~[:|\-|.]~', '', $macAddress)), 2));
                    }

                    // Dataport's type
                    $portType = DPortType::findByColumn('type', self::DEFAULT_DATA_PORT_TYPE);
                    if (false === $portType) {
                        $portType = (new DPortType())->fill([
                            'type' => self::DEFAULT_DATA_PORT_TYPE,
                        ]);
                        $portType->save();
                    }

                    // Management dataport
                    $foundDataPort = DataPort::findByIpVrf($ipAddress, $vrf);
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
                    $managementDataPort->fill([
                        'appliance' => $appliance,
                        'portType' => $portType,
                        'macAddress' => $macAddress,
                        'ipAddress' => $ipAddress,
                        'vrf' => $vrf,
                        'masklen' => $masklen,
                        'isManagement' => true,
                        'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                    ]);
                    $managementDataPort->save();
                    foreach ($appliance->dataPorts as $dataPort) {
                        if ($dataPort->getPk() != $managementDataPort->getPk()) {
                            $dataPort->delete();
                        }
                    }
                } else {
                    // У VGC PORT и Cisco 30 VIP недолжно быть data ports
                    foreach ($appliance->dataPorts as $dataPort) {
                        $dataPort->delete();
                    }
                }
            }

            // Domain name
            $domainName = (empty($data->domainName) || 'Нет' == $data->domainName) ? null : $data->domainName;

            // DHCP enable
            $dhcpEnable = mb_strtolower($data->dhcpEnabled);
            $dhcpEnable = ('yes' == $dhcpEnable || 1 == $dhcpEnable || 'да' == $dhcpEnable) ? true : false;

            // DHCP server
            $dhcpIp = (empty($data->dhcpServer)) ? '' : $data->dhcpServer;
            $dhcpIp = (new IpTools($dhcpIp))->address;
            $dhcpIp = (false === $dhcpIp) ? null : $dhcpIp;

            // TFTP server 1
            $tftpServer1 = (empty($data->tftpServer1)) ? '' : $data->tftpServer1;
            $tftpServer1 = (new IpTools($tftpServer1))->address;
            $tftpServer1 = (false === $tftpServer1) ? null : $tftpServer1;

            // TFTP server 2
            $tftpServer2 = (empty($data->tftpServer2)) ? '' : $data->tftpServer2;
            $tftpServer2 = (new IpTools($tftpServer2))->address;
            $tftpServer2 = (false === $tftpServer2) ? null : $tftpServer2;

            // Default router
            $defaultRouter = (empty($data->defaultRouter)) ? '' : $data->defaultRouter;
            $defaultRouter = (new IpTools($defaultRouter))->address;
            $defaultRouter = (false === $defaultRouter) ? null : $defaultRouter;

            // DNS server 1
            $dnsServer1 = (empty($data->dnsServer1)) ? '' : $data->dnsServer1;
            $dnsServer1 = (new IpTools($dnsServer1))->address;
            $dnsServer1 = (false === $dnsServer1) ? null : $dnsServer1;

            // DNS server 2
            $dnsServer2 = (empty($data->dnsServer2)) ? '' : $data->dnsServer2;
            $dnsServer2 = (new IpTools($dnsServer2))->address;
            $dnsServer2 = (false === $dnsServer2) ? null : $dnsServer2;

            // CDP neighbor IP
            $cdpNeighborIP = (empty($data->cdpNeighborIP)) ? '' : $data->cdpNeighborIP;
            $cdpNeighborIP = (new IpTools($cdpNeighborIP))->address;
            $cdpNeighborIP = (false === $cdpNeighborIP) ? null : $cdpNeighborIP;

            // Call manager 1
            $callManager1 = preg_replace('~[ ]+~', ' ', $data->callManager1);
            $callManager1 = (empty($callManager1)) ? null : $callManager1;

            // Call manager 2
            $callManager2 = preg_replace('~[ ]+~', ' ', $data->callManager2);
            $callManager2 = (empty($callManager2)) ? null : $callManager2;

            // Call manager 3
            $callManager3 = preg_replace('~[ ]+~', ' ', $data->callManager3);
            $callManager3 = (empty($callManager3)) ? null : $callManager3;

            // Call manager 4
            $callManager4 = preg_replace('~[ ]+~', ' ', $data->callManager4);
            $callManager4 = (empty($callManager4)) ? null : $callManager4;

            // Phone Info
            $phoneInfo->fill([
                'phone' => $appliance,
                'model' => $model,
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
                'domainName' => $domainName,
                'dhcpEnabled' => $dhcpEnable,
                'dhcpServer' => $dhcpIp,
                'tftpServer1' => $tftpServer1,
                'tftpServer2' => $tftpServer2,
                'defaultRouter' => $defaultRouter,
                'dnsServer1' => $dnsServer1,
                'dnsServer2' => $dnsServer2,
                'callManager1' => $callManager1,
                'callManager2' => $callManager2,
                'callManager3' => $callManager3,
                'callManager4' => $callManager4,
                'vlanId' => (int)$data->vlanId,
                'userLocale' => $data->userLocale,
                'cdpNeighborDeviceId' => $data->cdpNeighborDeviceId,
                'cdpNeighborIP' => $cdpNeighborIP,
                'cdpNeighborPort' => $data->cdpNeighborPort,
                'publisherIp' => $data->publisherIp,
                'unknownLocation' => $unknownLocation,
            ]);
            $phoneInfo->save();

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
     * @param string $name
     * @return bool
     */
    private function isVGC(string $name)
    {
        return (1 == preg_match('~^vgc|an~', mb_strtolower($name))) ? true : false;
    }

    /**
     * Заблокировать db.lock файл
     *
     * @return bool
     * @throws Exception
     */
    private function dbLock()
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
    private function dbUnLock()
    {
        flock($this->dbLockFile, LOCK_UN);
        fclose($this->dbLockFile);
        return true;
    }

    /**
     * @param $data
     * @throws \Exception
     */
    private function verifyData(Std $data)
    {
        if (!isset($data->name)) {
            throw new \Exception('No value: name');
        }
        if (!isset($data->description)) {
            throw new \Exception('No value: description');
        }
        if (!isset($data->css)) {
            throw new \Exception('No value: css');
        }
        if (!isset($data->devicepool)) {
            throw new \Exception('No value: devicepool');
        }
        if (!isset($data->phonedn)) {
            throw new \Exception('No value: phonedn');
        }
        if (!isset($data->alertingname)) {
            throw new \Exception('No value: alertingname');
        }
        if (!isset($data->model)) {
            throw new \Exception('No value: model');
        }
        if (!isset($data->prefix)) {
            throw new \Exception('No value: prefix');
        }
        if (!isset($data->partition)) {
            throw new \Exception('No value: partition');
        }
        if (!isset($data->publisherIp)) {
            throw new \Exception('No value: publisherIp');
        }
        if (!isset($data->ipAddress)) {
            throw new \Exception('No value: ipAddress');
        }
        if (!isset($data->status)) {
            throw new \Exception('No value: status');
        }
        if (!isset($data->class)) {
            throw new \Exception('No value: class');
        }
        if (!isset($data->macAddress)) {
            throw new \Exception('No value: macAddress');
        }
        if (!isset($data->serialNumber)) {
            throw new \Exception('No value: serialNumber');
        }
        if (!isset($data->modelNumber)) {
            throw new \Exception('No value: modelNumber');
        }
        if (!isset($data->versionID)) {
            throw new \Exception('No value: versionID');
        }
        if (!isset($data->appLoadID)) {
            throw new \Exception('No value: appLoadID');
        }
        if (!isset($data->timezone)) {
            throw new \Exception('No value: timezone');
        }
        if (!isset($data->dhcpEnabled)) {
            throw new \Exception('No value: dhcpEnabled');
        }
        if (!isset($data->dhcpServer)) {
            throw new \Exception('No value: dhcpServer');
        }
        if (!isset($data->domainName)) {
            throw new \Exception('No value: domainName');
        }
        if (!isset($data->subNetMask)) {
            throw new \Exception('No value: subNetMask');
        }
        if (!isset($data->tftpServer1)) {
            throw new \Exception('No value: tftpServer1');
        }
        if (!isset($data->tftpServer2)) {
            throw new \Exception('No value: tftpServer2');
        }
        if (!isset($data->defaultRouter)) {
            throw new \Exception('No value: defaultRouter');
        }
        if (!isset($data->dnsServer1)) {
            throw new \Exception('No value: dnsServer1');
        }
        if (!isset($data->dnsServer2)) {
            throw new \Exception('No value: dnsServer2');
        }
        if (!isset($data->callManager1)) {
            throw new \Exception('No value: callManager1');
        }
        if (!isset($data->callManager2)) {
            throw new \Exception('No value: callManager2');
        }
        if (!isset($data->callManager3)) {
            throw new \Exception('No value: callManager3');
        }
        if (!isset($data->callManager4)) {
            throw new \Exception('No value: callManager4');
        }
        if (!isset($data->vlanId)) {
            throw new \Exception('No value: vlanId');
        }
        if (!isset($data->userLocale)) {
            throw new \Exception('No value: userLocale');
        }
        if (!isset($data->cdpNeighborDeviceId)) {
            throw new \Exception('No value: cdpNeighborDeviceId');
        }
        if (!isset($data->cdpNeighborIP)) {
            throw new \Exception('No value: cdpNeighborIP');
        }
        if (!isset($data->cdpNeighborPort)) {
            throw new \Exception('No value: cdpNeighborPort');
        }
    }

    /**
     * @param Std $data
     * @return bool
     * @throws Exception
     */
    public function persist(Std $data): bool
    {
        $this->verifyData($data);
        return $this->process($data);
    }
}
