<?php
namespace App\Models;

use App\Components\AxlClient;
use App\Components\DSPappliance;
use App\Components\IpTools;
use App\Components\RisPortClient;
use App\Components\RLogger;
use Sunra\PhpSimple\HtmlDomParser;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class Phone extends Appliance
{
    const PHONE = 'phone';
    const RISPHONETYPE = 'Phone';
    const RISPANYTYPE = 'Any';
    const MAXRETURNEDDEVICES_SCH_7_1 = 200; // ограничение RisPort Service for cucm 7.1
    const MAXRETURNEDDEVICES_SCH_9_1 = 1000; // ограничение RisPort Service for cucm 9.1
    const MAXREQUESTSCOUNT = 15; // ограничение RisPort Service
    const TIMEINTERVAL = 60; // секунды
    const ALLMODELS = 255; // All phone's models
    const PHONESTATUS_REGISTERED = 'Registered';
    const PHONESTATUS_ANY = 'Any';
    const PHONESOFT = 'Phone Soft';
    const VENDOR = 'CISCO'; // Todo - пока так
    const PUBLISHER = 'cmp';


    protected $appliance;
    protected $phoneInfo;
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
     * Получить данные по зарегистрированным устройствам из cucm используя RisPort
     *
     * @param string $cucmIp
     * @param Collection $phones
     * @param Collection $nodes
     * @return Collection
     */
    protected static function findAllRegisteredDevicesIntoCucmRis(string $cucmIp, Collection $phones, Collection $nodes)
    {
        // Определить MAX RETURNED DEVICES
        switch (AxlClient::$schema->$cucmIp) {
            case '7.1':
                $maxReturnedDevices = self::MAXRETURNEDDEVICES_SCH_7_1;
                break;
            case '9.1':
                $maxReturnedDevices = self::MAXRETURNEDDEVICES_SCH_9_1;
                break;
            default:
                $maxReturnedDevices = self::MAXRETURNEDDEVICES_SCH_7_1;
        }

        $ris = RisPortClient::instance($cucmIp);

        // ЕСЛИ кол-во опрашиваемых ($phones) телефонов БОЛЬШЕ, чем кол-во $maxReturnedDevices отдаваемых callmanager за один запрос,
        // ТО опрашивать callmanager будем по телефонам из коллекции $phones в несколько запросов (не более 15 запросов в минуту)
        // 1 запрос - кол-во телефонов = $maxReturnedDevices
        $registeredDevices = new Collection();
        if ($maxReturnedDevices < $phones->count()) {
            $phonesCount = 0; // кол-во телефонов в запросе
            $requestsCount = 0; // кол-во запросов
            foreach ($phones as $phone) {
                $items[] = ['Item' => $phone->name];
                $phonesCount++;

                if ($maxReturnedDevices == $phonesCount) {
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
                        'MaxReturnedDevices' => $maxReturnedDevices,
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

                    $phonesCount = 0;
                    $items = [];
                }
            }
        } else {
            // ЕСЛИ кол-во опрашиваемых телефонов МЕНЬШЕ, чем кол-во $maxReturnedDevices отдаваемых callmanager за один запрос,
            // ТО делаем выборку всех зарегистрированных на callmanager телефонов одним запросом
            $risPhones = $ris->SelectCmDevice('',[
                'Class' => self::RISPANYTYPE,
                'Model' => self::ALLMODELS,
                'Status' => self::PHONESTATUS_REGISTERED,
                'SelectBy' => 'Name',
                'SelectItems' => [['Item' => '*']],
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
        }

        return $registeredDevices;
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
     * @return Collection
     */
    protected static function findAllCucmNodes(string $cucmIp)
    {
        $axl = AxlClient::instance($cucmIp);

        // Get all CmNodes from the publisher
        $listCmNodes = new Collection();
        $cmServers = ($axl->ListAllProcessNodes())->return->processNode;
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
     * @param array $options
     * @return Collection $appliances
     */
    public static function findAll($options = [])
    {
        return self::filterPhones(parent::findAll($options));
    }

    /**
     * @param mixed $value
     * @return null|Appliance
     */
    public static function findByPK($value)
    {
        $appliance = parent::findByPK($value);
        return (self::PHONE == $appliance->type->type) ? $appliance : null;
    }

    /**
     * @param Collection $appliances
     * @return Collection $appliances
     */
    protected static function filterPhones(Collection $appliances)
    {
        $type = self::PHONE;
        return $appliances->filter(
            function ($appliance) use($type) {
                return $type == $appliance->type->type;
            }
        );
    }


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate()
    {
        if (!isset($this->cucmName)) {
            throw new Exception('PHONE: No field cucmName');
        }
        if (!isset($this->cucmIpAddress)) {
            throw new Exception('PHONE: No field cucmIpAddress');
        }
        if (empty($this->name)) {
            throw new Exception('PHONE: Empty or No field name');
        }
        if (!isset($this->ipAddress)) {
            throw new Exception('PHONE: No field ipAddress');
        }
        if (!isset($this->description)) {
            throw new Exception('PHONE: No field description');
        }
        if (!isset($this->css)) {
            throw new Exception('PHONE: No field css');
        }
        if (!isset($this->devicePool)) {
            throw new Exception('PHONE: No field devicePool');
        }
        if (!isset($this->prefix)) {
            throw new Exception('PHONE: No field prefix');
        }
        if (!isset($this->phoneDN)) {
            throw new Exception('PHONE: No field phoneDN');
        }
        if (!isset($this->alertingName)) {
            throw new Exception('PHONE: No field alertingName');
        }
        if (!isset($this->partition)) {
            throw new Exception('PHONE: No field partition');
        }
        if (!isset($this->model)) {
            throw new Exception('PHONE: No field model');
        }

        return true;
    }


    /**
     * @return $this|bool
     * @throws Exception
     */
    public function save()
    {
        if (!$this->validate()) {
            return false;
        }

        $this->debugLogger->info('START: ' . '[name]=' . $this->ipAddress);

        // Find the location by the cucm's IP address
        $cucmLocation = (parent::findByManagementIP($this->cucmIpAddress))->location;
        if (!($cucmLocation instanceof Office)) {
            throw new Exception('PHONE: Location not found. CucmIP = ' . $this->cucmIpAddress);
        }

        $this->debugLogger->info('process: ' . '[name]=' . $this->ipAddress . '; [office]=' . $cucmLocation->title);

        // Create a DataSet for a phone(appliance)
        $softwareVersion = (1 == preg_match('~6921~', $this->model)) ? (($this->appLoadID) ?? '') : (($this->versionID) ?? '');
        $macAddress = ($this->macAddress) ?? substr($this->name,-12);
        $macAddress = implode('.', [
            substr($macAddress,0,4),
            substr($macAddress,4,4),
            substr($macAddress,8,4),
        ]);
        $phoneDataSet = (new Std())->fill([
            'applianceType' => self::PHONE,
            'platformVendor' => self::VENDOR,
            'platformTitle' => ($this->modelNumber) ?? $this->model,
            'platformSerial' => ($this->serialNumber) ?? $this->name,
            'applianceSoft' => self::PHONESOFT,
            'softwareVersion' => $softwareVersion,
            'ip' => $this->ipAddress,
            'subNetMask' => $this->subNetMask,
            'macAddress' => $macAddress,
            'LotusId' => $cucmLocation->lotusId,
            'hostname' => '',
            'chassis' => ($this->modelNumber) ?? $this->model,
            'applianceModules' => [],
        ]);


        // IF найден PhoneInfo по его имени (значит нашли и Phone)
        // THEN Update Phone
        if (($this->phoneInfo = PhoneInfo::findByColumn('name', $this->name)) instanceof PhoneInfo) {
            $this->appliance = (new DSPappliance($phoneDataSet, $this->phoneInfo->phone))->run();
        } else {
            $this->appliance = (new DSPappliance($phoneDataSet))->run();
            $this->phoneInfo = new PhoneInfo();
        }

        $this->debugLogger->info('process: ' . '[name]=' . $this->ipAddress . '; [phone]= OK');

        // Update PhoneInfo
        $this->dhcpEnabled = mb_strtolower($this->dhcpEnabled);
        $this->phoneInfo->fill([
            'phone' => $this->appliance,
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
            'dhcpEnabled' => (('yes' == $this->dhcpEnabled || 1 == $this->dhcpEnabled || 'да' == $this->dhcpEnabled) ? true : false),
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
        ]);
        $this->phoneInfo->save();

        $this->debugLogger->info('process: ' . '[name]=' . $this->ipAddress . '; [phoneInfo]= OK');
        $this->debugLogger->info('END: ' . '[name]=' . $this->ipAddress);

        return $this;
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
}
