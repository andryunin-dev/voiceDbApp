<?php
namespace App\Models;

use App\Components\AxlClient;
use App\Components\IpTools;
use App\Components\RisPortClient;
use App\Components\RLogger;
use App\Exceptions\DblockException;
use Sunra\PhpSimple\HtmlDomParser;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class Phone extends Appliance
{
    const PHONE = 'phone';
    const VGC = 'vg';
    const VGCSOFTWARE = '';
    const RISPHONETYPE = 'Phone';
    const RISPANYTYPE = 'Any';
    const MAXRETURNEDDEVICES_SCH_7_1 = 200; // ограничение RisPort Service for cucm 7.1
    const MAXRETURNEDDEVICES_SCH_9_1 = 1000; // ограничение RisPort Service for cucm 9.1
    const MAXREQUESTSCOUNT = 15; // per minute - ограничение RisPort Service for cucm 7.1, 9.1
    const TIMEINTERVAL = 60; // секунды
    const ALLMODELS = 255; // All phone's models
    const PHONESTATUS_REGISTERED = 'Registered';
    const PHONESTATUS_ANY = 'Any';
    const PHONESOFT = 'Phone Soft';
    const VENDOR = 'CISCO'; // Todo - пока так
    const PUBLISHER = 'cmp';
    const DATAPORTTYPE = 'Ethernet';
    const SLEEPTIME = 500; // микросекунды
    const ITERATIONS = 6000000; // Колличество попыток получить доступ к db.lock файлу
    const DBLOCKFILE = ROOT_PATH_PROTECTED . '/db.lock';

    public $appliance;
    public $phoneInfo;
    protected $debugLogger;


    public function __construct()
    {
        $this->debugLogger = RLogger::getInstance('Phone', realpath(ROOT_PATH . '/Logs/phones.log'));
    }


    /**
     * @param string $cucmIp
     * @return Collection
     */
    public static function findAllRegisteredIntoCucm(string $cucmIp)
    {
        $logger = RLogger::getInstance('Phone', realpath(ROOT_PATH . '/Logs/phones.log'));

        // Get list of all subscribers and publisher in the cluster
        $listAllProcessNodes = self::findAllCucmNodes($cucmIp);

        // Получить все телефоны из AXL
        $axlPhones = self::findAllIntoCucmAxl($cucmIp);

        // Получить все устройства из AXL
        $axlDevices = self::findAllDevicesIntoCucmAxl($cucmIp);

        // Получить зарегистрированные устройства из RisPort
        $registeredDevices = self::findAllRegisteredDevicesIntoCucmRis($cucmIp, $axlDevices, $listAllProcessNodes);

        // Найти зарегистрированные телефоны
        $registeredPhones = new Collection();
        foreach ($registeredDevices as $device) {
            $phone = $axlPhones->findByAttributes(['name' => $device->name]);

            if (!is_null($phone)) {
                $phone->fill([
                    'publisherIp' => $cucmIp,
                    'cucmName' => $device->cucmName,
                    'cucmIpAddress' => $device->cucmIpAddress,
                    'ipAddress' => $device->ipAddress,
                    'status' => $device->status,
                ]);
                $registeredPhones->add($phone);
            }else {
                $logger->info('REGISTERED DEVICE: [message]=It is not found in AXL; [class]=' . $device->class . '; [name]=' . $device->name . ';  [description]=' . $device->description . ';  [dirNumber]=' . ((empty($device->dirNumber)) ? '""' : $device->dirNumber) . '; [cucm]=' . $cucmIp );
            }
        }

        // Опросить кажждый телефон по его IP через WEB Interface
        foreach ($registeredPhones as $phone) {
            // ------------------- DeviceInformationX -------------------------------------
            if (is_null($phone->getDataFromWebDevInfo())) {
                $logger->info('PHONE: ' . '[name]=' . $phone->name . ' [ip]=' . $phone->ipAddress . ' [publisher]=' . $cucmIp . ' [model]=' . $phone->model . ' [message]=It does not have web access');
                continue;
            }
            // ------------------- NetworkConfigurationX -------------------------------------
            if (is_null($phone->getDataFromWebNetConf())) {
                $logger->info('PHONE: ' . '[model]=' . $phone->model . ' [ip]=' . $phone->ipAddress . ' [publisher]=' . $cucmIp . ' [message]=It does not have web access by HTML for NetworkConfiguration');
            }
            // ------------------- PortInformationX -------------------------------------
            if (is_null($phone->getDataFromWebPortInfo())) {
                $logger->info('PHONE: ' . '[model]=' . $phone->model . ' [ip]=' . $phone->ipAddress . ' [publisher]=' . $cucmIp . ' [message]=It does not have web access by HTML for PortInformation');
            }
        }

        return $registeredPhones;
    }

    /**
     * @return Collection
     */
    protected static function findAllCucmNodes(string $cucmIp)
    {
        $axl = AxlClient::getInstance($cucmIp)->client;

        // Get all CmNodes from the publisher
        switch (AxlClient::getInstance($cucmIp)->schema) {
            case '7.1':
                $cmServers = ($axl->ListAllProcessNodes())->return->processNode;
                break;
            case '9.1':
                $cmServers = ($axl->ListProcessNode([
                    'searchCriteria' => [
                        'name' => '%',
                    ],
                    'returnedTags' => [
                        'name' => '',
                        'description' => '',
                    ]
                ]))->return->processNode;
                break;
        }

        $listCmNodes = new Collection();
        foreach ($cmServers as $server) {
            $listCmNodes->add((new Std())
                ->fill([
                    'cmNodeIpAddress' => $server->name,
                    'cmNodeName' => $server->description,
                ])
            );
        }

        return $listCmNodes;
    }

    /**
     * Получить данные по телефонам из cucm используя AXL
     *
     * @param string $cucmIp
     * @return Collection
     */
    protected static function findAllIntoCucmAxl(string $cucmIp)
    {
        $axl = AxlClient::getInstance($cucmIp)->client;

        // Получить данные по телефонам из cucm
        $request = 'SELECT d.name AS Device, d.description,css.name AS css, css2.name AS name_off_clause, dp.name as dPool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as prefix, n.dnorpattern, n.alertingname as FIO, partition.name AS pt, tm.name AS type FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool INNER JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" INNER JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5)';


        $phones = $axl->ExecuteSQLQuery(['sql' => $request])->return->row;

        $axlPhones = new Collection();
        foreach ($phones as $phone) {
            $axlPhones->add(
                (new self())->fill([
                    'name' => mb_strtoupper(trim($phone->device)),
                    'description' => trim($phone->description),
                    'css' => trim($phone->css),
                    'devicePool' => trim($phone->dpool),
                    'prefix' => trim($phone->prefix),
                    'phoneDN' => trim($phone->dnorpattern),
                    'alertingName' => trim($phone->fio),
                    'partition' => trim($phone->pt),
                    'model' => trim($phone->type),
                ])
            );
        }

        return $axlPhones;
    }

    /**
     * Получить имена всех устройств из cucm используя AXL
     *
     * @param string $cucmIp
     * @return Collection
     */
    protected static function findAllDevicesIntoCucmAxl(string $cucmIp)
    {
        $axl = AxlClient::getInstance($cucmIp)->client;

        // Получить имена всех устройств из cucm
        $devices = ($axl->ExecuteSQLQuery(['sql' => 'SELECT d.name FROM device AS d']))->return->row;

        $axlDevices = new Collection();
        foreach ($devices as $device) {
            $axlDevices->add($device);
        }

        return $axlDevices;
    }

    /**
     * Получить данные по зарегистрированным устройствам из cucm используя RisPort
     *
     * @param string $cucmIp
     * @param Collection $devices
     * @param Collection $nodes
     * @return Collection
     */
    protected static function findAllRegisteredDevicesIntoCucmRis(string $cucmIp, Collection $devices, Collection $nodes)
    {
        $registeredDevices = new Collection();
        $ris = RisPortClient::getInstance($cucmIp);

        // Определить max Number Of Devices Returned In the Query
        switch (AxlClient::getInstance($cucmIp)->schema) {
            case '7.1':
                $maxNumberOfDevicesReturnedInQuery = self::MAXRETURNEDDEVICES_SCH_7_1;
                break;
            case '9.1':
                $maxNumberOfDevicesReturnedInQuery = self::MAXRETURNEDDEVICES_SCH_9_1;
                break;
            default:
                $maxNumberOfDevicesReturnedInQuery = self::MAXRETURNEDDEVICES_SCH_7_1;
        }

        $numberRequestedDevices = $devices->count();
        $currentNumberOfDevicesInQueries = 0;
        $currentNumberOfDevicesInRequest = 0;
        $requestsCount = 0; // кол-во запросов

        foreach ($devices as $device) {
            $items[] = ['Item' => $device->name];
            $currentNumberOfDevicesInQueries++;
            $currentNumberOfDevicesInRequest++;

            if ($maxNumberOfDevicesReturnedInQuery == $currentNumberOfDevicesInRequest || $currentNumberOfDevicesInQueries == $numberRequestedDevices) {
                // На старте включаем секундомер
                if (0 === $requestsCount) {
                    $startTime = (int)microtime(true);
                    $currentCountOfTime = (int)microtime(true) - $startTime;
                }

                // Считаем кол-во запросов
                $requestsCount++;

                // ЕСЛИ  кол-во времени прошедшее с момента первого запроса превысило self::TIMEINTERVAL
                // ТО начинаем считать запросы заново
                if ($currentCountOfTime >= self::TIMEINTERVAL) {
                    $requestsCount = 1;
                    $startTime = (int)microtime(true);
                } else {

                    // ЕСЛИ кол-во запросов превысило self::MAXREQUESTSCOUNT за время меньшее. чем self::TIMEINTERVAL
                    // ТО ждем до self::MAXREQUESTSCOUNT и начинаем считать запросы заново
                    if ($requestsCount > self::MAXREQUESTSCOUNT) {
                        sleep(self::TIMEINTERVAL - $currentCountOfTime);
                        $requestsCount = 1;
                        $startTime = (int)microtime(true);
                    }
                }

                $risPhones = $ris->SelectCmDevice('',[
                    'MaxReturnedDevices' => $maxNumberOfDevicesReturnedInQuery,
                    'Class' => self::RISPANYTYPE,
                    'Model' => self::ALLMODELS,
                    'Status' => self::PHONESTATUS_REGISTERED,
                    'SelectBy' => 'Name',
                    'SelectItems' => $items,
                ]);


                foreach (($risPhones['SelectCmDeviceResult'])->CmNodes as $cmNode) {
                    if ('ok' == strtolower($cmNode->ReturnCode)) {
                        $node = $nodes->findByAttributes(['cmNodeIpAddress' => $cmNode->Name]);

                        foreach ($cmNode->CmDevices as $cmDevice) {
                            $registeredDevices->add(
                                (new Std())->fill([
                                    'cucmName' => trim($node->cmNodeName),
                                    'cucmIpAddress' => trim($node->cmNodeIpAddress),
                                    'name' => mb_strtoupper(trim($cmDevice->Name)),
                                    'ipAddress' => trim($cmDevice->IpAddress),
                                    'description' => trim($cmDevice->Description),
                                    'status' => trim($cmDevice->Status),
                                    'dirNumber' => trim($cmDevice->DirNumber), // для вывода по devices
                                    'class' => trim($cmDevice->Class), // для вывода по devices
                                ])
                            );
                        }
                    }
                }

                // Фиксируем кол-во времени прошедшее с момента первого запроса
                $currentCountOfTime = (int)microtime(true) - $startTime;

                $currentNumberOfDevicesInRequest = 0;
                $items = [];
            }
        }

        return $registeredDevices;
    }

    /**
     * @return $this|null
     * @throws Exception
     */
    public function getDataFromWebDevInfo()
    {
        if (!isset($this->ipAddress)) {
            throw new Exception('PHONE: No field ipAddress');
        }

        $phoneData = simplexml_load_file('http://' . $this->ipAddress . '/DeviceInformationX');

        if (false === $phoneData) {
            return null;
        }

        $this->fill([
            'macAddress' => trim($phoneData->MACAddress->__toString()),
            'serialNumber' => trim($phoneData->serialNumber->__toString()),
            'modelNumber' => trim($phoneData->modelNumber->__toString()),
            'versionID' => trim($phoneData->versionID->__toString()),
            'appLoadID' => trim($phoneData->appLoadID->__toString()),
            'timezone' => trim($phoneData->timezone->__toString()),
        ]);

        return $this;
    }

    /**
     * @return $this|null
     * @throws Exception
     */
    public function getDataFromWebNetConf()
    {
        if (!isset($this->ipAddress) && !isset($this->model)) {
            throw new Exception('PHONE: No field ipAddress or no field model');
        }

        // Чтение XML
        $phoneData = simplexml_load_file('http://' . $this->ipAddress . '/NetworkConfigurationX');
        if (false !== $phoneData) {
            $this->fill([
                'dhcpEnabled' => trim($phoneData->DHCPEnabled->__toString()),
                'dhcpServer' => trim($phoneData->DHCPServer->__toString()),
                'domainName' => trim($phoneData->DomainName->__toString()),
                'subNetMask' => trim($phoneData->SubNetMask->__toString()),
                'tftpServer1' => trim($phoneData->TFTPServer1->__toString()),
                'tftpServer2' => trim($phoneData->TFTPServer2->__toString()),
                'defaultRouter' => trim($phoneData->DefaultRouter1->__toString()),
                'dnsServer1' => trim($phoneData->DNSServer1->__toString()),
                'dnsServer2' => trim($phoneData->DNSServer2->__toString()),
                'callManager1' => trim($phoneData->CallManager1->__toString()),
                'callManager2' => trim($phoneData->CallManager2->__toString()),
                'callManager3' => trim($phoneData->CallManager3->__toString()),
                'callManager4' => trim($phoneData->CallManager4->__toString()),
                'vlanId' => trim($phoneData->VLANId->__toString()),
                'userLocale' => trim($phoneData->UserLocale->__toString()),
            ]);

            $this->debugLogger->info('PHONE: ' . '[model]=' . $this->model . '[name]=' . $this->name . ' [ip]=' . $this->ipAddress . ' [message]=Web NetConf - XML     OK');

        } else {
            // Чтение HTML
            $dom = HtmlDomParser::str_get_html(file_get_contents('http://' . $this->ipAddress . '/NetworkConfiguration'));
            if (false !== $dom) {
                // Define the phone's environment
                preg_match('~\d+~', $this->model, $matches);
                switch ($matches[0]) {
                    case '7912':
                        $rows = $dom->find('form table tr');
                        $item = 1;
                        break;
                    case '7905':
                        $rows = $dom->find('form table tr');
                        $item = 1;
                        break;
                    case '6921':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    default:
                        $rows = [];
                }

                $phoneFields = [
                    'dhcpenabled' => 'dhcpEnabled',
                    'dhcpвключен' => 'dhcpEnabled',
                    'dhcpserver' => 'dhcpServer',
                    'domainname' => 'domainName',
                    'tftpserver1' => 'tftpServer1',
                    'tftpserver2' => 'tftpServer2',
                    'defaultrouter' => 'defaultRouter',
                    'defaultrouter1' => 'defaultRouter',
                    'dnsserver1' => 'dnsServer1',
                    'dnsserver2' => 'dnsServer2',
                    'callmanager1' => 'callManager1',
                    'unifiedcm1' => 'callManager1',
                    'callmanager2' => 'callManager2',
                    'callmanager3' => 'callManager3',
                    'callmanager4' => 'callManager4',
                    'operationalvlanid' => 'vlanId',
                    'действующийкодvlan' => 'vlanId',
                    'userlocale' => 'userLocale',
                    'subnetmask' => 'subNetMask',
                    'маскаподсети' => 'subNetMask',
                ];

                foreach ($rows as $row) {
                    $field = $phoneFields[mb_ereg_replace(' ', '', mb_strtolower((is_null($row->find('td', 0))) ? '' : $row->find('td', 0)->text()))];
                    if (!is_null($field)) {
                        $var = trim((is_null($row->find('td', $item))) ? '' : $row->find('td', $item)->text());
                        $this->fill([
                            $field => $var,
                        ]);
                    }
                }

                $this->debugLogger->info('PHONE: ' . '[model]=' . $this->model . '[name]=' . $this->name . ' [ip]=' . $this->ipAddress . ' [message]=Web NetConf - HTML     OK');

            } else {
                return null;
            }
        }

        return $this;
    }

    /**
     * @return $this|null
     * @throws Exception
     */
    public function getDataFromWebPortInfo()
    {
        if (!isset($this->ipAddress) && isset($this->model)) {
            throw new Exception('PHONE: No field ipAddress or no field model');
        }

        // Чтение XML
        $phoneData = simplexml_load_file('http://' . $this->ipAddress . '/PortInformationX?1');
        if (false !== $phoneData) {
            preg_match('~\d+~', $this->model, $matches);
            switch ($matches[0]) {
                case '7940':
                    $this->fill([
                        'cdpNeighborDeviceId' => trim($phoneData->deviceId->__toString()),
                        'cdpNeighborIP' => trim($phoneData->ipAddress->__toString()),
                        'cdpNeighborPort' => trim($phoneData->port->__toString()),
                    ]);
                    break;
                case '7911':
                    $this->fill([
                        'cdpNeighborDeviceId' => trim($phoneData->NeighborDeviceId->__toString()),
                        'cdpNeighborIP' => trim($phoneData->NeighborIP->__toString()),
                        'cdpNeighborPort' => trim($phoneData->NeighborPort->__toString()),
                    ]);
                    break;
                default:
                    $this->fill([
                        'cdpNeighborDeviceId' => trim($phoneData->CDPNeighborDeviceId->__toString()),
                        'cdpNeighborIP' => trim($phoneData->CDPNeighborIP->__toString()),
                        'cdpNeighborPort' => trim($phoneData->CDPNeighborPort->__toString()),
                    ]);
            }

            $this->debugLogger->info('PHONE: ' . '[model]=' . $this->model . '[name]=' . $this->name . ' [ip]=' . $this->ipAddress . ' [message]=Web PortInfo - XML     OK');

        } else {
            // Чтение HTML
            $dom = HtmlDomParser::str_get_html(file_get_contents('http://' . $this->ipAddress . '/PortInformation?1'));

            if (false !== $dom) {
                // Define the phone's environment
                preg_match('~\d+~', $this->model, $matches);
                switch ($matches[0]) {
                    case '6921':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    case '7911':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    default:
                        $rows = [];
                }

                $phoneFields = [
                    'идентустройствасоседа' => 'cdpNeighborDeviceId',
                    'neighbordeviceid' => 'cdpNeighborDeviceId',
                    'ipадрессоседа' => 'cdpNeighborIP',
                    'neighboripaddress' => 'cdpNeighborIP',
                    'портсоседа' => 'cdpNeighborPort',
                    'neighborport' => 'cdpNeighborPort',
                ];

                foreach ($rows as $row) {
                    $field = $phoneFields[mb_ereg_replace('[ -]', '', mb_strtolower((is_null($row->find('td', 0))) ? '' : $row->find('td', 0)->text()))];
                    if (!is_null($field)) {
                        $var = trim((is_null($row->find('td', $item))) ? '' : $row->find('td', $item)->text());
                        $this->fill([
                            $field => $var,
                        ]);
                    }
                }

                $this->debugLogger->info('PHONE: ' . '[model]=' . $this->model . '[name]=' . $this->name . ' [ip]=' . $this->ipAddress . ' [message]=Web PortInfo - HTML     OK');

            } else {
                return null;
            }
        }

        return $this;
    }



    /**
     * @return bool
     * @throws Exception
     */
    protected function validate()
    {
        if (empty($this->name)) {
            throw new Exception('PHONE: Empty or No field name');
        }
        if (!isset($this->ipAddress)) {
            throw new Exception('PHONE ' . $this->name . ': No field ipAddress');
        }
        if (!isset($this->cucmName)) {
            throw new Exception('PHONE ' . $this->name . ': No field cucmName');
        }
        if (!isset($this->cucmIpAddress)) {
            throw new Exception('PHONE ' . $this->name . ': No field cucmIpAddress');
        }
        if (!isset($this->description)) {
            throw new Exception('PHONE ' . $this->name . ': No field description');
        }
        if (!isset($this->css)) {
            throw new Exception('PHONE ' . $this->name . ': No field css');
        }
        if (!isset($this->devicePool)) {
            throw new Exception('PHONE ' . $this->name . ': No field devicePool');
        }
        if (!isset($this->prefix)) {
            throw new Exception('PHONE ' . $this->name . ': No field prefix');
        }
        if (!isset($this->phoneDN)) {
            throw new Exception('PHONE ' . $this->name . ': No field phoneDN');
        }
        if (!isset($this->alertingName)) {
            throw new Exception('PHONE ' . $this->name . ': No field alertingName');
        }
        if (!isset($this->partition)) {
            throw new Exception('PHONE ' . $this->name . ': No field partition');
        }
        if (!isset($this->model)) {
            throw new Exception('PHONE ' . $this->name . ': No field model');
        }

        return true;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $phoneInfo = PhoneInfo::findByColumn('name', $this->name);

        if (false !== $phoneInfo) {

            // Ура - телефон найден
            $appliance = $phoneInfo->phone;

            // -- Update Phone--
            try {

                // Start transaction
                if (false === $this->dbLock()) {
                    throw new DblockException('Phone (update): Can not get the lock file');
                }
                Phone::getDbConnection()->beginTransaction();


                // -- Update fields VGC port or Ip Phones--

                if (1 == preg_match('~^vgc|an~', mb_strtolower($this->name))) {

                    // -- Update VGC port --

                    // Update Location and Cluster for VGC port - location and cluster определяем по устройству VGS
                    if (!empty($this->ipAddress)) {

                        // Проверяем на валидность VGS's ipaddress
                        $vgsIp = (new IpTools($this->ipAddress))->address;
                        if (false !== $vgsIp) {

                            // Ищем VGS's dataport
                            $vgsDataPort = DataPort::findByColumn('ipAddress', $vgsIp);
                            if (false !== $vgsDataPort) {

                                // Определили устройство VGS
                                $vgc = $vgsDataPort->appliance;

                                // Определили VGS's location
                                $vgcLocation = $vgc->location;

                                // Если VGS's офис и офис VGS port разные -> изменить офис VGS port на VGS's офис
                                if ($vgcLocation->lotusId != $appliance->location->lotusId) {
                                    $appliance->fill([
                                        'location' => $vgcLocation,
                                    ]);
                                }

                                // Снимаем флаг
                                $unknownLocation = false;


                                // Определили Cluster for VGC port
                                $cluster = $vgc->cluster;

                                // Есди кластер не определен для устройства VGS -> определим его
                                if (empty($cluster) && !empty($hostnameVgc = $vgc->details->hostname)) {
                                    $cluster = Cluster::findByColumn('title', $hostnameVgc);

                                    if (false === $cluster) {
                                        $cluster = (new Cluster())->fill([
                                            'title' => $hostnameVgc,
                                        ]);
                                        $cluster->save();
                                    }

                                    $vgc->fill([
                                        'cluster' => $cluster,
                                    ]);
                                    $vgc->save();
                                }

                                // Есди VGC и VGS port находятся в разных кластерах -> изменить кластер VGS port на кластер VGS
                                if (!empty($cluster) && $cluster->title != $appliance->cluster->title) {
                                    $appliance->fill([
                                        'cluster' => $cluster,
                                        'details' => [
                                            'hostname' => $cluster->title,
                                        ]
                                    ]);
                                }

                            } else {
                                // Does not found VGS's dataport
                                $unknownLocation = true;
                            }
                        } else {
                            // No valid VGS's ipaddress
                            $unknownLocation = true;
                        }
                    } else {
                        // The VGC is not defined
                        $unknownLocation = true;
                    }


                    // Update IP address - У VGC port недолжно быть data port, так как они должны быть в кластере

                    if (0 < $appliance->dataPorts->count()) {
                        foreach ($appliance->dataPorts as $dataPort) {
                            $dataPort->delete();
                        }
                    }


                    // Update software for VGC port

                    $softwareItem = $appliance->software;
                    $software = $softwareItem->software;
                    if (self::VGCSOFTWARE != $softwareItem->version || self::VGCSOFTWARE != $software->title) {

                        $software = Software::findByColumn('title', self::VGCSOFTWARE);
                        if (false === $software) {
                            $software = (new Software())->fill([
                                'vendor' => $appliance->vendor,
                                'title' => self::VGCSOFTWARE,
                            ]);
                            $software->save();
                        }

                        $softwareItem->fill([
                            'version' => self::VGCSOFTWARE,
                            'software' => $software,
                        ]);
                        $softwareItem->save();
                    }


                    // Update hostname for VGC port

                    $appliance->fill([
                        'details' => [
                            'hostname' => $vgc->details->hostname,
                        ]
                    ]);

                } else {

                    // -- Update Ip Phones --

                    // Update Location for Ip Phone - Location for Ip Phone определяем по location defaultRouter телефона
                    if (!empty($this->defaultRouter)) {

                        // Проверяем на валидность defaultRouter's ipaddress
                        $defaultRouterIp = (new IpTools($this->defaultRouter))->address;
                        if (false !== $defaultRouterIp) {

                            // Ищем defaultRouter's dataport
                            $defaultRouterDataPort = DataPort::findByColumn('ipAddress', $defaultRouterIp);
                            if (false !== $defaultRouterDataPort) {

                                // Определили defaultRouter's location
                                $defaultRouterLocation = $defaultRouterDataPort->appliance->location;

                                // Если defaultRouter's офис и офис телефона разные -> изменить офис телефона
                                if ($defaultRouterLocation->lotusId != $appliance->location->lotusId) {
                                    $appliance->fill([
                                        'location' => $defaultRouterLocation,
                                    ]);
                                }

                                $unknownLocation = false;

                            } else {
                                // Does not found defaultRouter's dataport
                                $unknownLocation = true;
                            }
                        } else {
                            // No valid defaultRouter's ipaddress
                            $unknownLocation = true;
                        }
                    } else {
                        // The defaultRouter is not defined
                        $unknownLocation = true;
                    }


                    // Update software version for Ip Phones

                    $softwareVersion = (1 == preg_match('~6921~', $this->model)) ? (($this->appLoadID) ?? '') : (($this->versionID) ?? '');
                    $softwareItem = $appliance->software;
                    if ($softwareVersion != $softwareItem->version) {
                        $softwareItem->fill([
                            'version' => $softwareVersion,
                        ]);
                        $softwareItem->save();
                    }


                    // Update IP address for Ip Phones

                    $macAddress = ($this->macAddress) ?? substr($this->name, -12);
                    $macAddress = implode('.', [
                        substr($macAddress, 0, 4),
                        substr($macAddress, 4, 4),
                        substr($macAddress, 8, 4),
                    ]);

                    $dataPort = DataPort::findByColumn('macAddress', $macAddress);

                    if (false !== $dataPort) {
                        //update dataport

                        if ($this->ipAddress != $dataPort->ipAddress) {
                            $vrf = Vrf::instanceGlobalVrf();

                            $existDataPort = DataPort::findByIpVrf($this->ipAddress, $vrf);
                            if (false !== $existDataPort) {
                                $existDataPort->delete();
                            }

                            $dataPort->fill([
                                'ipAddress' => $this->ipAddress,
                                'vrf' => $vrf,
                                'masklen' => (new IpTools($this->ipAddress, $this->subNetMask))->masklen,
                            ]);
                            $dataPort->save();
                        }

                        if ($appliance->getPk() != $dataPort->appliance->getPk()) {
                            $dataPort->fill([
                                'appliance' => $appliance,
                            ]);
                            $dataPort->save();
                        }

                    } else {
                        //create dataport

                        $portType = DPortType::findByColumn('type', self::DATAPORTTYPE);
                        if (false === $portType) {
                            $portType = (new DPortType())->fill([
                                'type' => self::DATAPORTTYPE,
                            ]);
                            $portType->save();
                        }
                        $result = (new IpTools($this->ipAddress, $this->subNetMask))->masklen;
                        $masklen = (false !== $result) ? $result : null;
                        $macAddress = ($this->macAddress) ?? substr($this->name, -12);
                        $macAddress = implode('.', [
                            substr($macAddress, 0, 4),
                            substr($macAddress, 4, 4),
                            substr($macAddress, 8, 4),
                        ]);
                        $vrf = Vrf::instanceGlobalVrf();

                        $existDataPort = DataPort::findByIpVrf($this->ipAddress, $vrf);
                        if (false !== $existDataPort) {
                            $existDataPort->delete();
                        }

                        $dataPort = (new DataPort())->fill([
                            'appliance' => $appliance,
                            'portType' => $portType,
                            'macAddress' => $macAddress,
                            'ipAddress' => $this->ipAddress,
                            'vrf' => $vrf,
                            'masklen' => $masklen,
                            'isManagement' => true,
                        ]);
                        $dataPort->save();
                    }


                    // Update hostname for Ip Phones

                    $appliance->fill([
                        'details' => [
                            'hostname' => $this->name,
                        ]
                    ]);
                }


                // Update Appliance

                $appliance->fill([
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                ]);
                $appliance->save();


                // Update phoneInfo

                $dhcpenable = mb_strtolower($this->dhcpEnabled);
                $phoneInfo->fill([
                    'prefix' => preg_replace('~\..+~','',$this->prefix),
                    'phoneDN' => $this->phoneDN,
                    'status' => $this->status,
                    'description' => $this->description,
                    'css' => $this->css,
                    'devicePool' => $this->devicePool,
                    'alertingName' => $this->alertingName,
                    'partition' => $this->partition,
                    'timezone' => $this->timezone,
                    'domainName' => ('Нет' == $this->domainName) ? null : $this->domainName,
                    'dhcpEnabled' => ('yes' == $dhcpenable || 1 == $dhcpenable || 'да' == $dhcpenable) ? true : false,
                    'dhcpServer' => (false === ($dhcpIp = (new IpTools(($this->dhcpServer) ?? ''))->address)) ? null : $dhcpIp,
                    'tftpServer1' => (false === ($tftp1Ip = (new IpTools(($this->tftpServer1) ?? ''))->address)) ? null : $tftp1Ip,
                    'tftpServer2' => (false === ($tftp2Ip = (new IpTools(($this->tftpServer2) ?? ''))->address)) ? null : $tftp2Ip,
                    'defaultRouter' => (false === ($routerIp = (new IpTools(($this->defaultRouter) ?? ''))->address)) ? null : $routerIp,
                    'dnsServer1' => (false === ($dns1Ip = (new IpTools(($this->dnsServer1) ?? ''))->address)) ? null : $dns1Ip,
                    'dnsServer2' => (false === ($dns2Ip = (new IpTools(($this->dnsServer2) ?? ''))->address)) ? null : $dns2Ip,
                    'callManager1' => preg_replace('~[ ]+~', ' ', $this->callManager1),
                    'callManager2' => preg_replace('~[ ]+~', ' ', $this->callManager2),
                    'callManager3' => preg_replace('~[ ]+~', ' ', $this->callManager3),
                    'callManager4' => preg_replace('~[ ]+~', ' ', $this->callManager4),
                    'vlanId' => (int)$this->vlanId,
                    'userLocale' => $this->userLocale,
                    'cdpNeighborDeviceId' => $this->cdpNeighborDeviceId,
                    'cdpNeighborIP' => (false === ($neighborIp = (new IpTools(($this->cdpNeighborIP) ?? ''))->address)) ? null : $neighborIp,
                    'cdpNeighborPort' => $this->cdpNeighborPort,
                    'publisherIp' => $this->publisherIp,
                    'unknownLocation' => $unknownLocation,
                ]);
                $phoneInfo->save();


                // End transaction
                Phone::getDbConnection()->commitTransaction();
                $this->dbUnLock();

            } catch (Exception $e) {
                Phone::getDbConnection()->rollbackTransaction();
                throw new Exception($e->getMessage());
            } catch (DblockException $e) {
                throw new Exception($e->getMessage());
            }

            echo 'update ' . $this->name;
            return true;

        } else {

            // Создать новый телефон
            try {

                // Start transaction
                if (false === $this->dbLock()) {
                    throw new DblockException('Phone: Can not get the lock file');
                }
                Phone::getDbConnection()->beginTransaction();


                // Vendor

                $vendor = Vendor::findByColumn('title', self::VENDOR);
                if (false === $vendor) {
                    $vendor = (new Vendor())->fill([
                        'title' => self::VENDOR,
                    ]);
                    $vendor->save();
                }


                // Any fiels for VGS port or Ip phone

                if (1 == preg_match('~^vgc|an~', mb_strtolower($this->name))) {

                    // -- VGS port --

                    // Location and cluster for VGC port определяем по устройству VGS
                    if (!empty($this->ipAddress)) {

                        // Проверяем на валидность VGS's ipaddress
                        $vgsIp = (new IpTools($this->ipAddress))->address;
                        if (false !== $vgsIp) {

                            // Ищем VGS's dataport
                            $vgsDataPort = DataPort::findByColumn('ipAddress', $vgsIp);
                            if (false !== $vgsDataPort) {

                                // Определили устройство VGS
                                $vgc = $vgsDataPort->appliance;

                                // Определили location for VGS port
                                $location = $vgc->location;
                                $unknownLocation = false;


                                // Определили Cluster for VGC port
                                $cluster = $vgc->cluster;
                                $hostnameVgc = $vgc->details->hostname;

                                // Есди кластер не определен для устройства VGS, то определим его
                                if (empty($cluster) && !empty($hostnameVgc)) {
                                    $cluster = Cluster::findByColumn('title', $hostnameVgc);

                                    if (false === $cluster) {
                                        $cluster = (new Cluster())->fill([
                                            'title' => $hostnameVgc,
                                        ]);
                                        $cluster->save();
                                    }

                                    $vgc->fill([
                                        'cluster' => $cluster,
                                    ]);
                                    $vgc->save();
                                }

                            } else {
                                // Does not found VGS's dataport
                                $location = false;
                            }
                        } else {
                            // No valid VGS's ipaddress
                            $location = false;
                        }
                    } else {
                        // The VGC is not defined
                        $location = false;
                    }


                    // Есди не удалось определить location по устройству VGS, то определяем его по location cucm
                    if (false === $location && !empty($this->cucmIpAddress)) {

                        // Проверяем на валидность cumc's ipaddress
                        $cucmIpAddress = (new IpTools($this->cucmIpAddress))->address;
                        if (false !== $cucmIpAddress) {

                            // Ищем cumc's dataport
                            $cucmDataPort = DataPort::findByColumn('ipAddress', $cucmIpAddress);
                            if (false !== $cucmDataPort) {

                                $location = $cucmDataPort->appliance->location;

                                // Поднимаем флаг, так как location cucm - это неточная location для телефона
                                $unknownLocation = true;

                            } else {
                                // Does not found cumc's dataport
                                $location = false;
                            }
                        } else {
                            // No valid cumc's ipaddress
                            $location = false;
                        }
                    }


                    // Есди не удалось определить location ни по VGS , ни по location cucm
                    if (false === $location) {
                        throw new Exception('Phone '. $this->name . ' (publisher = ' . $this->publisherIp . '): The office is not defined');
                    }


                    // Software for VGC port

                    $software = Software::findByColumn('title', self::VGCSOFTWARE);
                    if (false === $software) {
                        $software = (new Software())->fill([
                            'vendor' => $vendor,
                            'title' => self::VGCSOFTWARE,
                        ]);
                        $software->save();
                    }


                    // Software Item for VGC port

                    $softwareItem = (new SoftwareItem())->fill([
                        'software' => $software,
                        'version' => self::VGCSOFTWARE,
                    ]);
                    $softwareItem->save();

                } else {

                    // -- Ip Phone --

                    // Location for Ip Phone определяем по location defaultRouter телефона
                    if (!empty($this->defaultRouter)) {

                        // Проверяем на валидность defaultRouter's ipaddress
                        $defaultRouterIp = (new IpTools($this->defaultRouter))->address;
                        if (false !== $defaultRouterIp) {

                            // Ищем defaultRouter's dataport
                            $defaultRouterDataPort = DataPort::findByColumn('ipAddress', $defaultRouterIp);
                            if (false !== $defaultRouterDataPort) {

                                $location = $defaultRouterDataPort->appliance->location;
                                $unknownLocation = false;

                            } else {
                                // Does not found defaultRouter's dataport
                                $location = false;
                            }
                        } else {
                            // No valid defaultRouter's ipaddress
                            $location = false;
                        }
                    } else {
                        // The defaultRouter is not defined
                        $location = false;
                    }


                    // Есди не удалось определить location по defaultRouter телефона, то определяем его по location cucm
                    if (false === $location && !empty($this->cucmIpAddress)) {

                        // Проверяем на валидность cumc's ipaddress
                        $cucmIpAddress = (new IpTools($this->cucmIpAddress))->address;
                        if (false !== $cucmIpAddress) {

                            // Ищем cumc's dataport
                            $cucmDataPort = DataPort::findByColumn('ipAddress', $cucmIpAddress);
                            if (false !== $cucmDataPort) {

                                $location = $cucmDataPort->appliance->location;

                                // Поднимаем флаг, так как location cucm - это неточная location для телефона
                                $unknownLocation = true;

                            } else {
                                // Does not found cumc's dataport
                                $location = false;
                            }
                        } else {
                            // No valid cumc's ipaddress
                            $location = false;
                        }
                    }


                    // Есди не удалось определить location ни по defaultRouter телефона, ни по location cucm
                    if (false === $location) {
                        throw new Exception('Phone '. $this->name . ' (publisher = ' . $this->publisherIp . '): The office is not defined');
                    }


                    // Software for Ip Phone

                    $software = Software::findByColumn('title', self::PHONESOFT);
                    if (false === $software) {
                        $software = (new Software())->fill([
                            'vendor' => $vendor,
                            'title' => self::PHONESOFT,
                        ]);
                        $software->save();
                    }


                    // Software Item for Ip Phone

                    $softwareItem = (new SoftwareItem())->fill([
                        'software' => $software,
                        'version' => (1 == preg_match('~6921~', $this->model)) ? (($this->appLoadID) ?? '') : (($this->versionID) ?? ''),
                    ]);
                    $softwareItem->save();

                }


                // Platform

                $platformTitle = ($this->modelNumber) ?? $this->model;
                $platform = Platform::findByColumn('title', $platformTitle);
                if (false === $platform) {
                    $platform = (new Platform())->fill([
                        'vendor' => $vendor,
                        'title' => $platformTitle,
                    ]);
                    $platform->save();
                }


                // Platform Item

                $platformItem = (new PlatformItem())->fill([
                    'platform' => $platform,
                    'serialNumber' => ($this->serialNumber) ?? $this->name,
                ]);
                $platformItem->save();


                // Appliance Type

                $applianceType = ApplianceType::findByColumn('type', self::PHONE);
                if (false === $applianceType) {
                    $applianceType = (new ApplianceType())->fill([
                        'type' => self::PHONE,
                    ]);
                    $applianceType->save();
                }


                // Appliance

                $appliance = (new Appliance())->fill([
                    'type' => $applianceType,
                    'platform' => $platformItem,
                    'software' => $softwareItem,
                    'vendor' => $vendor,
                    'cluster' => ($cluster) ?? null,
                    'location' => $location,
                    'inUse' => true,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                    'details' => [
                        'hostname' => ($hostnameVgc) ?? $this->name,
                    ],
                ]);
                $appliance->save();


                // Data Port

                if (1 != preg_match('~^vgc|an~', mb_strtolower($this->name))) {

                    $portType = DPortType::findByColumn('type', self::DATAPORTTYPE);
                    if (false === $portType) {
                        $portType = (new DPortType())->fill([
                            'type' => self::DATAPORTTYPE,
                        ]);
                        $portType->save();
                    }
                    $result = (new IpTools($this->ipAddress, $this->subNetMask))->masklen;
                    $masklen = (false !== $result) ? $result : null;
                    $macAddress = ($this->macAddress) ?? substr($this->name, -12);
                    $macAddress = implode('.', [
                        substr($macAddress, 0, 4),
                        substr($macAddress, 4, 4),
                        substr($macAddress, 8, 4),
                    ]);
                    $vrf = Vrf::instanceGlobalVrf();

                    $existDataPort = DataPort::findByIpVrf($this->ipAddress, $vrf);
                    if (false !== $existDataPort) {
                        $existDataPort->delete();
                    }

                    $dataPort = (new DataPort())->fill([
                        'appliance' => $appliance,
                        'portType' => $portType,
                        'macAddress' => $macAddress,
                        'ipAddress' => $this->ipAddress,
                        'vrf' => $vrf,
                        'masklen' => $masklen,
                        'isManagement' => true,
                    ]);
                    $dataPort->save();
                }


                // PhoneInfo

                $dhcpenable = mb_strtolower($this->dhcpEnabled);
                $phoneInfo = (new PhoneInfo())->fill([
                    'phone' => $appliance,
                    'model' => $this->model,
                    'name' => $this->name,
                    'prefix' => preg_replace('~\..+~','',$this->prefix),
                    'phoneDN' => $this->phoneDN,
                    'status' => $this->status,
                    'description' => $this->description,
                    'css' => $this->css,
                    'devicePool' => $this->devicePool,
                    'alertingName' => $this->alertingName,
                    'partition' => $this->partition,
                    'timezone' => $this->timezone,
                    'domainName' => ('Нет' == $this->domainName) ? null : $this->domainName,
                    'dhcpEnabled' => ('yes' == $dhcpenable || 1 == $dhcpenable || 'да' == $dhcpenable) ? true : false,
                    'dhcpServer' => (false === ($dhcpIp = (new IpTools(($this->dhcpServer) ?? ''))->address)) ? null : $dhcpIp,
                    'tftpServer1' => (false === ($tftp1Ip = (new IpTools(($this->tftpServer1) ?? ''))->address)) ? null : $tftp1Ip,
                    'tftpServer2' => (false === ($tftp2Ip = (new IpTools(($this->tftpServer2) ?? ''))->address)) ? null : $tftp2Ip,
                    'defaultRouter' => (false === ($routerIp = (new IpTools(($this->defaultRouter) ?? ''))->address)) ? null : $routerIp,
                    'dnsServer1' => (false === ($dns1Ip = (new IpTools(($this->dnsServer1) ?? ''))->address)) ? null : $dns1Ip,
                    'dnsServer2' => (false === ($dns2Ip = (new IpTools(($this->dnsServer2) ?? ''))->address)) ? null : $dns2Ip,
                    'callManager1' => preg_replace('~[ ]+~', ' ', $this->callManager1),
                    'callManager2' => preg_replace('~[ ]+~', ' ', $this->callManager2),
                    'callManager3' => preg_replace('~[ ]+~', ' ', $this->callManager3),
                    'callManager4' => preg_replace('~[ ]+~', ' ', $this->callManager4),
                    'vlanId' => (int)$this->vlanId,
                    'userLocale' => $this->userLocale,
                    'cdpNeighborDeviceId' => $this->cdpNeighborDeviceId,
                    'cdpNeighborIP' => (false === ($neighborIp = (new IpTools(($this->cdpNeighborIP) ?? ''))->address)) ? null : $neighborIp,
                    'cdpNeighborPort' => $this->cdpNeighborPort,
                    'publisherIp' => $this->publisherIp,
                    'unknownLocation' => $unknownLocation,
                ]);
                $phoneInfo->save();

                // End transaction
                Phone::getDbConnection()->commitTransaction();
                $this->dbUnLock();

            } catch (Exception $e) {
                Phone::getDbConnection()->rollbackTransaction();
                throw new Exception($e->getMessage());
            } catch (DblockException $e) {
                throw new Exception($e->getMessage());
            }

            echo 'new ' . $this->name;
            return true;
        }
    }



    /**
     * Заблокировать db.lock файл
     *
     * @return bool
     * @throws Exception
     */
    protected function dbLock()
    {
        $this->dbLockFile = fopen(self::DBLOCKFILE, 'w');

        if (false === $this->dbLockFile) {
            throw new Exception('Phone: Can not open the lock file');
        }

        $blockedFile = flock($this->dbLockFile, LOCK_EX | LOCK_NB);

        $n = self::ITERATIONS; // Кол-во попыток доступа к db.lock
        while (false === $blockedFile && 0 !== $n--) {
            usleep(self::SLEEPTIME);
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


    /**
     * @param $name
     * @param $cucmIp
     * @return Phone|bool
     */
    public static function findByNameIntoCucm($name, $cucmIp)
    {
        //// Get phone's data from cucm's axl
        $axl = AxlClient::getInstance($cucmIp)->client;
        $request = 'SELECT d.name AS Device, d.description,css.name AS css, css2.name AS name_off_clause, dp.name as dPool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as prefix, n.dnorpattern, n.alertingname as FIO, partition.name AS pt, tm.name AS type FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 AND  d.name = "' . $name . '" INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool INNER JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" INNER JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5)';

        $axlResult = $axl->ExecuteSQLQuery(['sql' => $request])->return->row;


        //// Get phone's data from cucm's ris
        $ris = RisPortClient::getInstance($cucmIp);
        $risResult = $ris->SelectCmDevice('',[
            'Class' => self::RISPHONETYPE,
            'Model' => self::ALLMODELS,
            'Status' => self::PHONESTATUS_REGISTERED,
            'SelectBy' => 'Name',
            'SelectItems' => [['Item' => $name]],
        ]);


        //// Если телефон не найден ни в Axl, ни в Ris, то возвращаем false
        if (is_null($axlResult) && 0 === $risResult['SelectCmDeviceResult']->TotalDevicesFound) {
            return false;
        }


        //// Fill in phone data from Axl and Ris
        $phone = new self();

        if (!is_null($axlResult)) {
            $phone->fill([
                'name' => trim($axlResult->device),
                'css' => trim($axlResult->css),
                'devicePool' => trim($axlResult->dpool),
                'model' => trim($axlResult->type),
                'prefix' => trim($axlResult->prefix),
                'phoneDN' => trim($axlResult->dnorpattern),
                'alertingName' => trim($axlResult->fio),
                'partition' => trim($axlResult->pt),
                'description' => trim($axlResult->description),
            ]);
        }

        if (1 == $risResult['SelectCmDeviceResult']->TotalDevicesFound) {
            $cmNodes = $risResult['SelectCmDeviceResult']->CmNodes;
            foreach ($cmNodes as $cmNode) {
                if ('ok' == strtolower($cmNode->ReturnCode)) {
                    $phone->fill([
                        'cucmIpAddress' => trim($cmNode->Name),
                        'ipAddress' => trim($cmNode->CmDevices[0]->IpAddress),
                        'status' => trim($cmNode->CmDevices[0]->Status),
                    ]);
                }
            }
        }


        //// Get phone's data from WEB - DeviceInformationX
        $webDevInfoResult = simplexml_load_file('http://' . $phone->ipAddress . '/DeviceInformationX');
        if (false !== $webDevInfoResult) {
            $phone->fill([
                'macAddress' => trim($webDevInfoResult->MACAddress->__toString()),
                'serialNumber' => trim($webDevInfoResult->serialNumber->__toString()),
                'modelNumber' => trim($webDevInfoResult->modelNumber->__toString()),
                'versionID' => trim($webDevInfoResult->versionID->__toString()),
                'appLoadID' => trim($webDevInfoResult->appLoadID->__toString()),
                'timezone' => trim($webDevInfoResult->timezone->__toString()),
            ]);
        }


        //// Get phone's data from WEB - NetworkConfigurationX
        /// Чтение XML
        $webNetConfigResult = simplexml_load_file('http://' . $phone->ipAddress . '/NetworkConfigurationX');
        if (false !== $webNetConfigResult) {
            $phone->fill([
                'dhcpEnabled' => trim($webNetConfigResult->DHCPEnabled->__toString()),
                'dhcpServer' => trim($webNetConfigResult->DHCPServer->__toString()),
                'domainName' => trim($webNetConfigResult->DomainName->__toString()),
                'subNetMask' => trim($webNetConfigResult->SubNetMask->__toString()),
                'tftpServer1' => trim($webNetConfigResult->TFTPServer1->__toString()),
                'tftpServer2' => trim($webNetConfigResult->TFTPServer2->__toString()),
                'defaultRouter' => trim($webNetConfigResult->DefaultRouter1->__toString()),
                'dnsServer1' => trim($webNetConfigResult->DNSServer1->__toString()),
                'dnsServer2' => trim($webNetConfigResult->DNSServer2->__toString()),
                'callManager1' => trim($webNetConfigResult->CallManager1->__toString()),
                'callManager2' => trim($webNetConfigResult->CallManager2->__toString()),
                'callManager3' => trim($webNetConfigResult->CallManager3->__toString()),
                'callManager4' => trim($webNetConfigResult->CallManager4->__toString()),
                'vlanId' => trim($webNetConfigResult->VLANId->__toString()),
                'userLocale' => trim($webNetConfigResult->UserLocale->__toString()),
            ]);

        } else {

            /// Чтение HTML
            $dom = HtmlDomParser::str_get_html(file_get_contents('http://' . $phone->ipAddress . '/NetworkConfiguration'));
            if (false !== $dom) {
                // Define the phone's environment
                preg_match('~\d+~', $phone->model, $matches);
                switch ($matches[0]) {
                    case '7912':
                        $rows = $dom->find('form table tr');
                        $item = 1;
                        break;
                    case '7905':
                        $rows = $dom->find('form table tr');
                        $item = 1;
                        break;
                    case '6921':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    default:
                        $rows = [];
                }

                $phoneFields = [
                    'dhcpenabled' => 'dhcpEnabled',
                    'dhcpвключен' => 'dhcpEnabled',
                    'dhcpserver' => 'dhcpServer',
                    'domainname' => 'domainName',
                    'tftpserver1' => 'tftpServer1',
                    'tftpserver2' => 'tftpServer2',
                    'defaultrouter' => 'defaultRouter',
                    'defaultrouter1' => 'defaultRouter',
                    'dnsserver1' => 'dnsServer1',
                    'dnsserver2' => 'dnsServer2',
                    'callmanager1' => 'callManager1',
                    'unifiedcm1' => 'callManager1',
                    'callmanager2' => 'callManager2',
                    'callmanager3' => 'callManager3',
                    'callmanager4' => 'callManager4',
                    'operationalvlanid' => 'vlanId',
                    'действующийкодvlan' => 'vlanId',
                    'userlocale' => 'userLocale',
                    'subnetmask' => 'subNetMask',
                    'маскаподсети' => 'subNetMask',
                ];

                foreach ($rows as $row) {
                    $field = $phoneFields[mb_ereg_replace(' ', '', mb_strtolower((is_null($row->find('td', 0))) ? '' : $row->find('td', 0)->text()))];
                    if (!is_null($field)) {
                        $var = trim((is_null($row->find('td', $item))) ? '' : $row->find('td', $item)->text());
                        $phone->fill([
                            $field => $var,
                        ]);
                    }
                }

            }
        }


        //// Get phone's data from WEB - PortInformationX
        /// Чтение XML
        $webPortInfoResult = simplexml_load_file('http://' . $phone->ipAddress . '/PortInformationX?1');
        if (false !== $webPortInfoResult) {
            preg_match('~\d+~', $phone->model, $matches);
            switch ($matches[0]) {
                case '7940':
                    $phone->fill([
                        'cdpNeighborDeviceId' => trim($webPortInfoResult->deviceId->__toString()),
                        'cdpNeighborIP' => trim($webPortInfoResult->ipAddress->__toString()),
                        'cdpNeighborPort' => trim($webPortInfoResult->port->__toString()),
                    ]);
                    break;
                case '7911':
                    $phone->fill([
                        'cdpNeighborDeviceId' => trim($webPortInfoResult->NeighborDeviceId->__toString()),
                        'cdpNeighborIP' => trim($webPortInfoResult->NeighborIP->__toString()),
                        'cdpNeighborPort' => trim($webPortInfoResult->NeighborPort->__toString()),
                    ]);
                    break;
                default:
                    $phone->fill([
                        'cdpNeighborDeviceId' => trim($webPortInfoResult->CDPNeighborDeviceId->__toString()),
                        'cdpNeighborIP' => trim($webPortInfoResult->CDPNeighborIP->__toString()),
                        'cdpNeighborPort' => trim($webPortInfoResult->CDPNeighborPort->__toString()),
                    ]);
            }


        } else {
            // Чтение HTML
            $dom = HtmlDomParser::str_get_html(file_get_contents('http://' . $phone->ipAddress . '/PortInformation?1'));

            if (false !== $dom) {
                // Define the phone's environment
                preg_match('~\d+~', $phone->model, $matches);
                switch ($matches[0]) {
                    case '6921':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    case '7911':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    default:
                        $rows = [];
                }

                $phoneFields = [
                    'идентустройствасоседа' => 'cdpNeighborDeviceId',
                    'neighbordeviceid' => 'cdpNeighborDeviceId',
                    'ipадрессоседа' => 'cdpNeighborIP',
                    'neighboripaddress' => 'cdpNeighborIP',
                    'портсоседа' => 'cdpNeighborPort',
                    'neighborport' => 'cdpNeighborPort',
                ];

                foreach ($rows as $row) {
                    $field = $phoneFields[mb_ereg_replace('[ -]', '', mb_strtolower((is_null($row->find('td', 0))) ? '' : $row->find('td', 0)->text()))];
                    if (!is_null($field)) {
                        $var = trim((is_null($row->find('td', $item))) ? '' : $row->find('td', $item)->text());
                        $phone->fill([
                            $field => $var,
                        ]);
                    }
                }
            }
        }

        return $phone;
    }
}
