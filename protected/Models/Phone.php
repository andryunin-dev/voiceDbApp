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
                $logger->info('PHONE: ' . '[name]=' . $phone->name . ' [ip]=' . $phone->ipAddress . ' [publisher]=' . $cucmIp . ' [message]=It does not have web access');
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
        $axl = AxlClient::instance($cucmIp);

        // Get all CmNodes from the publisher
        switch (AxlClient::$schema->$cucmIp) {
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
        $axl = AxlClient::instance($cucmIp);

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
        $axl = AxlClient::instance($cucmIp);

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
        $ris = RisPortClient::instance($cucmIp);

        // Определить max Number Of Devices Returned In the Query
        switch (AxlClient::$schema->$cucmIp) {
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
     * @param string $name
     * @param string|null $cucmIp
     * @return Phone|null
     */
    public static function findByNameIntoCucm(string $name, string $cucmIp = null)
    {
        $phone = new self();
        $phone->fill([
            'name' => mb_strtoupper($name),
        ]);

        // ------------------- AXL -------------------------------------
        if (is_null($phone->getDataFromCucmAxl($cucmIp))) {
            $phone->debugLogger->info('PHONE: ' . '[name]=' . $phone->name . ' [publisher]=' . $cucmIp . ' [message]=It is not found in AXL on the CUCM');
            return null;
        }
        // ------------------- RisPort Service -------------------------------------
        if (is_null($phone->getDataFromCucmRis($cucmIp))) {
            $phone->debugLogger->info('PHONE: ' . '[name]=' . $phone->name . ' [publisher]=' . $cucmIp . ' [message]=It is not found in RisPort on the CUCM');
            return null;
        }
        // ------------------- DeviceInformation -------------------------------------
        if (is_null($phone->getDataFromWebDevInfo())) {
            $phone->debugLogger->info('PHONE: ' . '[name]=' . $phone->name . ' [ip]=' . $phone->ipAddress . ' [publisher]=' . $cucmIp . ' [message]=It does not have web access');
            return $phone;
        }
        // ------------------- NetworkConfiguration -------------------------------------
        if (is_null($phone->getDataFromWebNetConf())) {
            $phone->debugLogger->info('PHONE: ' . '[model]=' . $phone->model . ' [ip]=' . $phone->ipAddress . ' [publisher]=' . $cucmIp . ' [message]=It does not have web access by HTML for NetworkConfiguration');
        }
        // ------------------- PortInformation -------------------------------------
        if (is_null($phone->getDataFromWebPortInfo())) {
            $phone->debugLogger->info('PHONE: ' . '[model]=' . $phone->model . ' [ip]=' . $phone->ipAddress . ' [publisher]=' . $cucmIp . ' [message]=It does not have web access by HTML for PortInformation');
        }

        return $phone;
    }

    /**
     * @param string|null $cucmIp
     * @return $this|null
     * @throws Exception
     * @throws MultiException
     */
    public function getDataFromCucmRis(string $cucmIp = null)
    {
        if (!isset($this->name)) {
            throw new Exception('PHONE: No field name');
        }

        if (is_null($cucmIp) && isset($this->cucmIpAddress)) {
            $cucmIp = $this->cucmIpAddress;
        }

        // Если $cucmIp = null, ТО искать во всех CUCMs
        $cucmIps = new Collection();
        if (is_null($cucmIp)) {
            foreach (Appliance::findAllByType(self::PUBLISHER) as $publisher) {
                $cucmIps->add($publisher->managementIp);
            }
        } else {
            $cucmIps->add($cucmIp);
        }

        foreach ($cucmIps as $cucmIp) {
            try {
                $ris = RisPortClient::instance($cucmIp);
                $items[] = ['Item' => $this->name];
                $result = $ris->SelectCmDevice('',[
                    'Class' => self::RISPHONETYPE,
                    'Model' => self::ALLMODELS,
                    'Status' => self::PHONESTATUS_REGISTERED,
                    'SelectBy' => 'Name',
                    'SelectItems' => $items,
                ]);

                if (1 == $result['SelectCmDeviceResult']->TotalDevicesFound) {
                    foreach ($result['SelectCmDeviceResult']->CmNodes as $cmNode) {
                        if ('ok' == strtolower($cmNode->ReturnCode)) {
                            $this->fill([
                                'cucmIpAddress' => trim($cmNode->Name),
                                'ipAddress' => trim($cmNode->CmDevices[0]->IpAddress),
                                'status' => trim($cmNode->CmDevices[0]->Status),
                            ]);

                            return $this;
                        }
                    }
                }

            } catch (\SoapFault $e) {
                $this->debugLogger->info('PHONE: ' . '[name]=' . $this->name . ' [publisher]=' . $cucmIp . ' [message]=' . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * @param string|null $cucmIp
     * @return $this|null
     * @throws Exception
     */
    public function getDataFromCucmAxl(string $cucmIp = null)
    {
        if (!isset($this->name)) {
            throw new Exception('PHONE: No field name');
        }

        // Если $cucmIp = null, ТО искать во всех CUCMs
        $cucmIps = new Collection();
        if (is_null($cucmIp)) {
            foreach (Appliance::findAllByType(self::PUBLISHER) as $publisher) {
                $cucmIps->add($publisher->managementIp);
            }
        } else {
            $cucmIps->add($cucmIp);
        }

        // Искать CUCM на котором телефон заведен
        foreach ($cucmIps as $cucmIp) {
            try {
                $axl = AxlClient::instance($cucmIp);
                if (isset(($axl->ListPhoneByName(['searchString' => $this->name]))->return->phone)) {
                    $cucm = (self::findAllCucmNodes($cucmIp))->findByAttributes(['cmNodeIpAddress' => $cucmIp]);
                    $this->fill([
                        'cucmName' => $cucm->cmNodeName,
                        'cucmIpAddress' => $cucm->cmNodeIpAddress,
                    ]);
                    $result = true;
                    break;
                }
                $result = false;
            } catch (\SoapFault $e) {
                $this->debugLogger->info('PHONE: ' . '[name]=' . $this->name . ' [publisher]=' . $cucmIp . ' [message]=' . $e->getMessage());
            }
        }
        // ЕСЛИ телефон не найден ни в одном CUCM
        if (false === $result) {
            return null;
        }

        // Get phone's data
        $request = 'SELECT d.name AS Device, d.description,css.name AS css, css2.name AS name_off_clause, dp.name as dPool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as prefix, n.dnorpattern, n.alertingname as FIO, partition.name AS pt, tm.name AS type FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool INNER JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" INNER JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5)';

        $items = $axl->ExecuteSQLQuery(['sql' => $request])->return->row;

        foreach ($items as $item) {
            if ($item->device == $this->name) {
                $this->fill([
                    'css' => trim($item->css),
                    'devicePool' => trim($item->dpool),
                    'model' => trim($item->type),
                    'prefix' => trim($item->prefix),
                    'phoneDN' => trim($item->dnorpattern),
                    'alertingName' => trim($item->fio),
                    'partition' => trim($item->pt),
                    'description' => trim($item->description),
                ]);
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

            try {

                // Start transaction
                if (false === $this->dbLock()) {
                    throw new DblockException('Phone (update): Can not get the lock file');
                }
                Phone::getDbConnection()->beginTransaction();


                // Update location
                if (1 == preg_match('~^vgc|an~', mb_strtolower($this->name))) {
                    // Для VGC портов - location определяем по location устройства VGS

                    $vgsDataPort = DataPort::findByColumn('ipAddress', $this->ipAddress);
                    if (false !== $vgsDataPort && self::VGC == $vgsDataPort->appliance->type->type) {
                        $vgc = $vgsDataPort->appliance;

                        if (false === $vgc->location) {
                            throw new Exception('Phone ' . $appliance->platform->serialNumber . ': VGS (' . $this->ipAddress . ') location does not found');
                        }
                        if ($vgc->location->lotusId != $appliance->location->lotusId) {
                            $appliance->fill([
                                'location' => $vgc->location,
                            ]);
                        }

                    } else {
                        throw new Exception('Phone (vgc) ' . $this->name . ' ' . $this->ipAddress . ': Does not found the appliance VGC for the cluster');
                    }

                } else {
                    // Для не VGC портов - location определяем по location cucm на котором данный телефон зарегистрирован

                    $cucmLocation = (DataPort::findByColumn('ipAddress', $this->cucmIpAddress))->appliance->location;
                    if (false === $cucmLocation) {
                        throw new Exception('Phone ' . $appliance->platform->serialNumber . ': cucms (' . $this->cucmIpAddress . ') location does not found');
                    }
                    if ($cucmLocation->lotusId != $appliance->location->lotusId) {
                        $appliance->fill([
                            'location' => $cucmLocation,
                        ]);
                    }
                }


                // Update cluster for VGC
                if (1 == preg_match('~^vgc|an~', mb_strtolower($this->name)) && is_null($appliance->cluster)) {
                    $cluster = $vgc->cluster;
                    $hostname = $vgc->details->hostname;

                    if (is_null($cluster)) {

                        if (is_null($hostname)) {
                            throw new Exception('Phone (vgc) ' . $this->name . ' ' . $this->ipAddress . ': VGC (' . $vgc->platform->serialNumber . ') does not have field HOSTNAME. Can not find the cluster');
                        }

                        $cluster = Cluster::findByColumn('title', $hostname);

                        if (false === $cluster) {
                            $cluster = (new Cluster())->fill([
                                'title' => $hostname,
                            ]);
                            $cluster->save();
                        }

                        $vgc->fill([
                            'cluster' => $cluster,
                        ]);
                        $vgc->save();
                    }

                    $appliance->fill([
                        'cluster' => $cluster,
                        'details' => [
                            'hostname' => $hostname,
                        ]
                    ]);
                }


                // Update software version
                $softwareVersion = (1 == preg_match('~6921~', $this->model)) ? (($this->appLoadID) ?? '') : (($this->versionID) ?? '');
                if ($softwareVersion != $appliance->software->version) {
                    $appliance->fill([
                        'version' => $softwareVersion,
                    ]);
                }


                // Update IP address
                if (1 != preg_match('~^vgc|an~', mb_strtolower($this->name))) {

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

                } else {
                    // У VGC портов недолжно быть data port, так как они должны быть в кластере
                    if (0 < count($appliance->dataPorts->count())) {
                        foreach ($appliance->dataPorts as $dataPort) {
                            $dataPort->delete();
                        }
                    }
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
                ]);
                $phoneInfo->save();


                // Stop transaction
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


                // Location
                if (1 == preg_match('~^vgc|an~', mb_strtolower($this->name))) {
                    // Для VGC портов - location определяем по location устройства VGS

                    $vgsDataPort = DataPort::findByColumn('ipAddress', $this->ipAddress);
                    if (false !== $vgsDataPort && self::VGC == $vgsDataPort->appliance->type->type) {
                        $vgc = $vgsDataPort->appliance;

                        if (false === $vgc->location) {
                            throw new Exception('Phone (create): VGS (' . $this->ipAddress . ') location does not found');
                        }

                        $location = $vgc->location;

                    } else {
                        throw new Exception('Phone (vgc) ' . $this->name . ' ' . $this->ipAddress . ': Does not found the appliance VGC for the cluster');
                    }

                } else {
                    // Для не VGC портов - location определяем по location cucm на котором данный телефон зарегистрирован

                    $location = (DataPort::findByColumn('ipAddress', $this->cucmIpAddress))->appliance->location;
                    if (false === $location) {
                        throw new Exception('Phone (create): cucms (' . $this->cucmIpAddress . ') location does not found');
                    }
                }


                // Vendor
                $vendor = Vendor::findByColumn('title', self::VENDOR);
                if (false === $vendor) {
                    $vendor = (new Vendor())->fill([
                        'title' => self::VENDOR,
                    ]);
                    $vendor->save();
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


                // Software
                $software = Software::findByColumn('title', self::PHONESOFT);
                if (false === $software) {
                    $software = (new Software())->fill([
                        'vendor' => $vendor,
                        'title' => self::PHONESOFT,
                    ]);
                    $software->save();
                }


                // Software Item
                $softwareItem = (new SoftwareItem())->fill([
                    'software' => $software,
                    'version' => (1 == preg_match('~6921~', $this->model)) ? (($this->appLoadID) ?? '') : (($this->versionID) ?? ''),
                ]);
                $softwareItem->save();


                // Appliance Type
                $applianceType = ApplianceType::findByColumn('type', self::PHONE);
                if (false === $applianceType) {
                    $applianceType = (new ApplianceType())->fill([
                        'type' => self::PHONE,
                    ]);
                    $applianceType->save();
                }


                // Cluster for VGC
                if (1 == preg_match('~^vgc|an~', mb_strtolower($this->name))) {
                    $cluster = $vgc->cluster;

                    if (is_null($cluster)) {
                        $cluster = Cluster::findByColumn('title', $vgc->details->hostname);

                        if (false === $cluster) {
                            $cluster = (new Cluster())->fill([
                                'title' => $vgc->details->hostname,
                            ]);
                            $cluster->save();
                        }

                        $vgc->fill([
                            'cluster' => $cluster,
                        ]);
                        $vgc->save();
                    }
                }


                // Appliance
                $appliance = (new Appliance())->fill([
                    'type' => $applianceType,
                    'platform' => $platformItem,
                    'software' => $softwareItem,
                    'vendor' => $vendor,
                    'cluster' => ($cluster) ?? null,
                    'location' => $location,
                    'lastUpdate'=> (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                    'inUse' => true,
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
                ]);
                $phoneInfo->save();

                // Stop transaction
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
}
