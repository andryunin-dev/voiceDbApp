<?php
namespace App\Models;

use App\Components\AxlClient;
use App\Components\RisPortClient;
use App\Components\RLogger;
use Sunra\PhpSimple\HtmlDomParser;
use T4\Core\Collection;
use T4\Core\Exception;

class Phone extends Appliance
{
    const PHONE = 'phone';
    const VGC = 'vg';
    const PUBLISHER = 'cmp';
    const IP_CM_MOSKOW_CC_558 = '10.30.30.70';
    const PREFIX_CM_MOSKOW_CC_558 = 558;
    const IP_CM_MOSKOW_CC_559 = '10.30.30.21';
    const PREFIX_CM_MOSKOW_CC_559 = 559;
    const VGCSOFTWARE = '';
    const RISPHONETYPE = 'Phone';
    const RISPANYTYPE = 'Any';
    const MAXRETURNEDDEVICES_SCH_7_1 = 200; // ограничение RisPort Service for cucm 7.1
    const MAXRETURNEDDEVICES_SCH_8_6 = 200; // ограничение RisPort Service for cucm 8.6
    const MAXRETURNEDDEVICES_SCH_9_1 = 1000; // ограничение RisPort Service for cucm 9.1
    const MAXREQUESTSCOUNT = 15; // per minute - ограничение RisPort Service for cucm 7.1, 9.1
    const TIMEINTERVAL = 60; // секунды
    const ALLMODELS = 255; // All phone's models
    const PHONESTATUS_REGISTERED = 'Registered';
    const PHONESTATUS_ANY = 'Any';
    const PHONESOFT = 'Phone Soft';
    const VENDOR = 'CISCO'; // Todo - пока так
    const DATAPORTTYPE = 'Ethernet';
    const ERROR_EXCEEDED_RATE = 'ns1:Server.RateControl';
    const TIME_BETWEEN_RISPORT_POLLING_ATTEMPTS = 1;
    const NUMBER_OF_ATTEMPTS_TO_REQUEST_PHONE = 6;
    const TIME_BETWEEN_PHONE_POLLING_ATTEMPTS = 2;

    public $appliance;
    public $phoneInfo;


    /**
     * @param $name
     * @param $cucmIp
     * @return $this|bool
     * @throws Exception
     */
    public static function findByNameIntoCucm($name, $cucmIp)
    {
        $phoneData = [
            'name' => '',
            'description' => '',
            'css' => '',
            'devicepool' => '',
            'phonedn' => '',
            'alertingname' => '',
            'model' => '',
            'prefix' => '',
            'partition' => '',
            'publisherIp' => '',

            'ipAddress' => '',
            'status' => '',
            'class' => '',

            'macAddress' => '',
            'serialNumber' => '',
            'modelNumber' => '',
            'versionID' => '',
            'appLoadID' => '',
            'timezone' => '',

            'dhcpEnabled' => '',
            'dhcpServer' => '',
            'domainName' => '',
            'subNetMask' => '',
            'tftpServer1' => '',
            'tftpServer2' => '',
            'defaultRouter' => '',
            'dnsServer1' => '',
            'dnsServer2' => '',
            'callManager1' => '',
            'callManager2' => '',
            'callManager3' => '',
            'callManager4' => '',
            'vlanId' => '',
            'userLocale' => '',

            'cdpNeighborDeviceId' => '',
            'cdpNeighborIP' => '',
            'cdpNeighborPort' => '',
        ];


        //// Get phone's data from cucm's axl
        $axlTemplate = [
            'name' => '',
            'description' => '',
            'css' => '',
            'devicepool' => '',
            'phonedn' => '',
            'alertingname' => '',
            'model' => '',
            'prefix' => '',
            'partition' => '',
            'publisherIp' => '',
        ];
        $axl = AxlClient::getInstance($cucmIp)->client;
        switch (AxlClient::getInstance($cucmIp)->schema) {
            case '7.1':
                $request = 'SELECT d.name, d.description, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as prefix, n.dnorpattern AS phonedn, n.alertingname as alertingName, partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 AND  d.name = "' . $name . '" INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool INNER JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" INNER JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1';
                break;
            case '8.6':
                $request = 'SELECT d.name, d.description, css.name AS css, dp.name as devicepool, n.dnorpattern AS phonedn, n.alertingname as alertingName, tm.name AS model FROM device AS d  INNER JOIN callingsearchspace AS css ON css.pkid=d.fkcallingsearchspace AND  d.name = "' . $name . '" INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice=d.pkid and d.tkclass=1 INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN DevicePool AS dp ON dp.pkid=d.fkDevicePool where  d.tkmodel != 72 and dmap.numplanindex = 1';
                break;
            case '9.1':
                $request = 'SELECT d.name, d.description, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as prefix, n.dnorpattern AS phonedn, n.alertingname as alertingName, partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 AND  d.name = "' . $name . '" INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool INNER JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" INNER JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr( n2.dnorpattern,LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1';
                break;
            default:
                $request = '';
        }

        /**
         * Axl return: null or Object(stdClass) or array[Object(stdClass)]
         */
        $axlResult = $axl->ExecuteSQLQuery(['sql' => $request])->return->row;

        /**
         * Further released for the next Axl returns: null or Object(stdClass)
         */
        if (!is_null($axlResult)) {
            if ($axlResult instanceof \stdClass) {
                switch ($cucmIp) {
                    case self::IP_CM_MOSKOW_CC_558:
                        $axlResult->prefix = self::PREFIX_CM_MOSKOW_CC_558;
                        break;
                    case self::IP_CM_MOSKOW_CC_559:
                        $axlResult->prefix = self::PREFIX_CM_MOSKOW_CC_559;
                        break;
                }
                $axlResult->publisherIp = $cucmIp;
                $axlResult = array_merge($axlTemplate, get_object_vars($axlResult));
                $phoneData = array_merge($phoneData, $axlResult);
            } else {
                throw new Exception('Axl\'s response not valid');
            }
        }


        //// Get phone's data from cucm's ris
        $risTemplate = [
            'ipAddress' => '',
            'status' => '',
            'class' => '',
        ];
        $ris = RisPortClient::getInstance($cucmIp);
        $risResult = false;
        do {
            $error = false;
            try {
                $risResult = $ris->SelectCmDevice('',[
                    'Class' => self::RISPHONETYPE,
                    'Model' => self::ALLMODELS,
                    'Status' => self::PHONESTATUS_REGISTERED,
                    'SelectBy' => 'Name',
                    'SelectItems' => [['Item' => $name]],
                ]);
            } catch (\SoapFault $e) {
                if (self::ERROR_EXCEEDED_RATE == $e->faultcode) {
                    $error = true;
                    sleep(self::TIME_BETWEEN_RISPORT_POLLING_ATTEMPTS);
                }
            }
        } while ($error);
        // Если телефон не найден ни в Axl, ни в Ris, то возвращаем false
        if (is_null($axlResult) && 1 != $risResult['SelectCmDeviceResult']->TotalDevicesFound) {
            return false;
        }
        foreach (($risResult['SelectCmDeviceResult'])->CmNodes as $cmNode) {
            if ('ok' == strtolower($cmNode->ReturnCode)) {
                foreach ($cmNode->CmDevices as $cmDevice) {
                    $risTemplate['ipAddress'] = $cmDevice->IpAddress ?? '';
                    $risTemplate['status'] = $cmDevice->Status ?? '';
                    $risTemplate['class'] = $cmDevice->Class ?? ''; // this is necessary for logging
                }
            }
        }
        $phoneData = array_merge($phoneData, $risTemplate);


        //// Get phone's data from WEB
        if (!empty($phoneData['ipAddress'])) {
            $attempt = 0;
            $webDevInfo = self::getDataFromWebDevInfo($phoneData['ipAddress'], $phoneData['model']);
            while (is_null($webDevInfo) && $attempt < self::NUMBER_OF_ATTEMPTS_TO_REQUEST_PHONE) {
                sleep(self::TIME_BETWEEN_PHONE_POLLING_ATTEMPTS);
                $webDevInfo = self::getDataFromWebDevInfo($phoneData['ipAddress'], $phoneData['model']);
                $attempt++;
            }
            if (!is_null($webDevInfo)) {
                $phoneData = array_merge($phoneData, $webDevInfo);

                $attempt = 0;
                $webNetConf = self::getDataFromWebNetConf($phoneData['ipAddress'], $phoneData['model']);
                while (is_null($webNetConf) && $attempt < self::NUMBER_OF_ATTEMPTS_TO_REQUEST_PHONE) {
                    sleep(self::TIME_BETWEEN_PHONE_POLLING_ATTEMPTS);
                    $webNetConf = self::getDataFromWebNetConf($phoneData['ipAddress'], $phoneData['model']);
                    $attempt++;
                }
                if (!is_null($webNetConf)) {
                    $phoneData = array_merge($phoneData, $webNetConf);
                }

                $attempt = 0;
                $webPortInfo = self::getDataFromWebPortInfo($phoneData['ipAddress'], $phoneData['model']);
                while (is_null($webPortInfo) && $attempt < self::NUMBER_OF_ATTEMPTS_TO_REQUEST_PHONE) {
                    sleep(self::TIME_BETWEEN_PHONE_POLLING_ATTEMPTS);
                    $webPortInfo = self::getDataFromWebPortInfo($phoneData['ipAddress'], $phoneData['model']);
                    $attempt++;
                }
                if (!is_null($webPortInfo)) {
                    $phoneData = array_merge($phoneData, $webPortInfo);
                }
            }
        }

        return (new self())->fromArray($phoneData);
    }

    /**
     * @param string $cucmIp
     * @return Collection
     */
    public static function findAllRegisteredIntoCucm(string $cucmIp)
    {
        $logger = RLogger::getInstance('CUCM-' . $cucmIp, ROOT_PATH . DS . 'Logs' . DS . 'phones_' . preg_replace('~\.~', '_', $cucmIp) . '.log');

        // Получить все телефоны из AXL
        $axlPhones = self::findAllIntoCucmAxl($cucmIp);

        // Получить все устройства из AXL
        $axlDevices = self::findAllDevicesIntoCucmAxl($cucmIp);

        // Получить зарегистрированные устройства из RisPort
        $registeredDevices = self::findAllRegisteredDevicesIntoCucmRis($cucmIp, $axlDevices);

        // Получить зарегистрированные телефоны
        $registeredPhones = new Collection();
        foreach ($registeredDevices as $registeredDeviceName => $registeredDevice) {
            $axlPhone = $axlPhones[$registeredDeviceName];
            if (!is_null($axlPhone)) {
                $phoneData = array_merge($axlPhone, $registeredDevice);
                $webDevInfo = self::getDataFromWebDevInfo($registeredDevice['ipAddress'], $axlPhone['model']);
                if (!is_null($webDevInfo)) {
                    $phoneData = array_merge($phoneData, $webDevInfo);
                    $webNetConf = self::getDataFromWebNetConf($registeredDevice['ipAddress'], $axlPhone['model']);
                    if (!is_null($webNetConf)) {
                        $phoneData = array_merge($phoneData, $webNetConf);
                    }
//                    $webPortInfo = self::getDataFromWebPortInfo($registeredDevice['ipAddress'], $axlPhone['model']);
//                    if (!is_null($webPortInfo)) {
//                        $phoneData = array_merge($phoneData, $webPortInfo);
//                    }
                } else {
                    $logger->info('PHONE:  [message]=It does not have web access; [model]=' . $axlPhone['model'] . '; [name]=' . $registeredDeviceName . '; [ip]=' . $registeredDevice['ipAddress'] . '; [publisher]=' . $cucmIp);
                }
                $registeredPhones->add((new self())->fromArray($phoneData));
                $logger->info('REGISTERED DEVICE: [message]=It is found; [name]=' . $registeredDeviceName . '; [ip]=' . $registeredDevice['ipAddress'] . '; [publisher]=' . $cucmIp );
            }else {
                $logger->info('REGISTERED DEVICE: [message]=It is not found in AXL; [class]=' . $registeredDevice['class'] . '; [name]=' . $registeredDeviceName . '; [publisher]=' . $cucmIp );
            }
        }
        return $registeredPhones;
    }

    /**
     * @param string $cucmIp
     * @return array
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
            case '8.6':
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
            default:
                $cmServers = null;
        }

        $listCmNodes = [];
        foreach ($cmServers as $server) {
            $listCmNodes[$server->name] = $server;
        }

        return $listCmNodes;
    }

    /**
     * Получить данные по телефонам из cucm используя AXL
     *
     * @param string $cucmIp
     * @return array
     */
    protected static function findAllIntoCucmAxl(string $cucmIp)
    {
        $axl = AxlClient::getInstance($cucmIp)->client;

        // Получить данные по телефонам из cucm
        switch (AxlClient::getInstance($cucmIp)->schema) {
            case '7.1':
                $request = 'SELECT d.name, d.description, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as prefix, n.dnorpattern AS phonedn, n.alertingname as alertingName, partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool INNER JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" INNER JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1';
                break;
            case '8.6':
                $request = 'SELECT d.name, d.description, css.name AS css, dp.name as devicepool, n.dnorpattern AS phonedn, n.alertingname as alertingName, tm.name AS model FROM device AS d  INNER JOIN callingsearchspace AS css ON css.pkid=d.fkcallingsearchspace INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice=d.pkid and d.tkclass=1 INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN DevicePool AS dp ON dp.pkid=d.fkDevicePool where  d.tkmodel != 72 and dmap.numplanindex = 1';
                break;
            case '9.1':
                $request = 'SELECT d.name, d.description, css.name AS css, dp.name as devicepool, TRIM (TRAILING "." FROM (TRIM (TRAILING "X" FROM n2.dnorpattern))) as prefix, n.dnorpattern AS phonedn, n.alertingname as alertingName, partition.name AS partition, tm.name AS model FROM device AS d INNER JOIN callingsearchspace AS css ON css.pkid = d.fkcallingsearchspace AND d.tkclass = 1 AND  d.tkmodel != 72 INNER JOIN devicenumplanmap AS dmap ON dmap.fkdevice = d.pkid INNER JOIN numplan AS n ON dmap.fknumplan = n.pkid INNER JOIN routepartition AS partition ON partition.pkid = n.fkroutepartition INNER JOIN typemodel AS tm ON d.tkmodel = tm.enum INNER JOIN DevicePool AS dp ON dp.pkid = d.fkDevicePool INNER JOIN callingsearchspace AS css2 ON css2.clause LIKE "%" || partition.name || "%" INNER JOIN numplan AS n2 ON n2.fkcallingsearchspace_translation = css2.pkid WHERE n2.tkpatternusage = 3 AND n2.dnorpattern LIKE "5%" AND lessthan(LENGTH(substr(n2.dnorpattern, LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern))+1, LENGTH(n2.dnorpattern)-LENGTH(TRIM (TRAILING "X" FROM n2.dnorpattern)))),5) and dmap.numplanindex = 1';
                break;
            default:
                $request = '';
        }

        $phones = $axl->ExecuteSQLQuery(['sql' => $request])->return->row;

        // Для некоторых cucms жёстко прописываем prefix
        switch ($cucmIp) {
            case self::IP_CM_MOSKOW_CC_558:
                $prefix = self::PREFIX_CM_MOSKOW_CC_558;
                break;
            case self::IP_CM_MOSKOW_CC_559:
                $prefix = self::PREFIX_CM_MOSKOW_CC_559;
                break;
            default:
                $prefix = null;
        }

        $axlPhones = [];
        foreach ($phones as $phone) {
            if (!isset($phone->prefix)) {
                $phone->prefix = $prefix ?? '';
            }
            if (!isset($phone->partition)) {
                $phone->partition = '';
            }
            $phone->publisherIp = $cucmIp;
            $phone->name = mb_strtoupper($phone->name);
            $axlPhones[$phone->name] = get_object_vars($phone);
        }

        return $axlPhones;
    }

    /**
     * Получить имена всех устройств из cucm используя AXL
     *
     * @param string $cucmIp
     * @return array
     */
    protected static function findAllDevicesIntoCucmAxl(string $cucmIp)
    {
        $axl = AxlClient::getInstance($cucmIp)->client;

        // Вернуть имена всех устройств из cucm
        return ($axl->ExecuteSQLQuery(['sql' => 'SELECT d.name FROM device AS d']))->return->row;
    }

    /**
     * Получить данные по зарегистрированным устройствам из cucm используя RisPort
     *
     * @param string $cucmIp
     * @param array $devices
     * @return array
     */
    protected static function findAllRegisteredDevicesIntoCucmRis(string $cucmIp, array $devices)
    {
        $ris = RisPortClient::getInstance($cucmIp);
        $registeredDevices = [];

        // Определить max Number Of Devices Returned In the Query
        switch (AxlClient::getInstance($cucmIp)->schema) {
            case '7.1':
                $maxNumberOfDevicesReturnedInQuery = self::MAXRETURNEDDEVICES_SCH_7_1;
                break;
            case '8.6':
                $maxNumberOfDevicesReturnedInQuery = self::MAXRETURNEDDEVICES_SCH_8_6;
                break;
            case '9.1':
                $maxNumberOfDevicesReturnedInQuery = self::MAXRETURNEDDEVICES_SCH_9_1;
                break;
            default:
                $maxNumberOfDevicesReturnedInQuery = self::MAXRETURNEDDEVICES_SCH_7_1;
        }

        $numberRequestedDevices = count($devices);
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
                        foreach ($cmNode->CmDevices as $cmDevice) {
                            $registeredDevice['ipAddress'] = $cmDevice->IpAddress;
                            $registeredDevice['status'] = $cmDevice->Status;
                            $registeredDevice['class'] = $cmDevice->Class; // this is necessary for logging
                            $registeredDevices[mb_strtoupper($cmDevice->Name)] = $registeredDevice;
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
     * @param $ipAddress
     * @return array
     */
    protected static function getDataFromWebDevInfo($ipAddress, $phoneModel)
    {
        $webDevInfoResult = [
            'macAddress' => '',
            'serialNumber' => '',
            'modelNumber' => '',
            'versionID' => '',
            'appLoadID' => '',
            'timezone' => '',
        ];

        // Чтение XML
        $result = simplexml_load_file('http://' . $ipAddress . '/DeviceInformationX');
        if (false !== $result) {
            $webDevInfoResult['macAddress'] = (isset($result->MACAddress)) ? trim($result->MACAddress->__toString()) : '';
            $webDevInfoResult['serialNumber'] = (isset($result->serialNumber)) ? trim($result->serialNumber->__toString()) : '';
            $webDevInfoResult['modelNumber'] = (isset($result->modelNumber)) ? trim($result->modelNumber->__toString()) : '';
            $webDevInfoResult['versionID'] = (isset($result->versionID)) ? trim($result->versionID->__toString()) : '';
            $webDevInfoResult['appLoadID'] = (isset($result->appLoadID)) ? trim($result->appLoadID->__toString()) : '';
            $webDevInfoResult['timezone'] = (isset($result->timezone)) ? trim($result->timezone->__toString()) : '';
        } else {
            // Чтение HTML
            $dom = HtmlDomParser::str_get_html(file_get_contents('http://' . $ipAddress . '/DeviceInformation'));
            if (false !== $dom) {
                // Define the phone's environment
                preg_match('~\d+~', $phoneModel, $matches);
                switch ($matches[0]) {
                    case '7940':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    case '7960':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    default:
                        $rows = [];
                }
                $phoneFields = [
                    'mac-адрес' => 'macAddress',
                    'серийныйномер' => 'serialNumber',
                    'номермодели' => 'modelNumber',
                    'версия' => 'versionID',
                ];
                foreach ($rows as $row) {
                    $td = is_null($row->find('td', 0)) ? null : $row->find('td', 0)->text();
                    $field = $phoneFields[mb_ereg_replace(' ', '', mb_strtolower($td))];
                    $iconv = false;
                    if (is_null($field)) {
                        $td = is_null($row->find('td', 0)) ? null : iconv("WINDOWS-1251","UTF-8", $row->find('td', 0)->text());
                        $field = $phoneFields[mb_ereg_replace(' ', '', mb_strtolower($td))];
                        $iconv = true;
                    }
                    if (!is_null($field)) {
                        if ($iconv) {
                            $var = trim(is_null($row->find('td', $item)) ? null : iconv("WINDOWS-1251","UTF-8", $row->find('td', $item)->text()));
                        } else {
                            $var = trim(is_null($row->find('td', $item)) ? null : $row->find('td', $item)->text());
                        }
                        $webDevInfoResult[$field] = $var;
                    }
                }
            } else {
                return null;
            }
        }
        return $webDevInfoResult;
    }

    /**
     * @param $ipAddress
     * @param $phoneModel
     * @return array|null
     */
    protected static function getDataFromWebNetConf($ipAddress, $phoneModel)
    {
        $webNetConfResult = [
            'dhcpEnabled' => '',
            'dhcpServer' => '',
            'domainName' => '',
            'subNetMask' => '',
            'tftpServer1' => '',
            'tftpServer2' => '',
            'defaultRouter' => '',
            'dnsServer1' => '',
            'dnsServer2' => '',
            'callManager1' => '',
            'callManager2' => '',
            'callManager3' => '',
            'callManager4' => '',
            'vlanId' => '',
            'userLocale' => '',
        ];

        // Чтение XML
        $result = simplexml_load_file('http://' . $ipAddress . '/NetworkConfigurationX');
        if (false !== $result) {
            $webNetConfResult['dhcpEnabled'] = (isset($result->DHCPEnabled)) ? trim($result->DHCPEnabled->__toString()) : '';
            $webNetConfResult['dhcpServer'] = (isset($result->DHCPServer)) ? trim($result->DHCPServer->__toString()) : '';
            $webNetConfResult['domainName'] = (isset($result->DomainName)) ? trim($result->DomainName->__toString()) : '';
            $webNetConfResult['subNetMask'] = (isset($result->SubNetMask)) ? trim($result->SubNetMask->__toString()) : '';
            $webNetConfResult['tftpServer1'] = (isset($result->TFTPServer1)) ? trim($result->TFTPServer1->__toString()) : '';
            $webNetConfResult['tftpServer2'] = (isset($result->TFTPServer2)) ? trim($result->TFTPServer2->__toString()) : '';
            $webNetConfResult['defaultRouter'] = (isset($result->DefaultRouter1)) ? trim($result->DefaultRouter1->__toString()) : '';
            $webNetConfResult['dnsServer1'] = (isset($result->DNSServer1)) ? trim($result->DNSServer1->__toString()) : '';
            $webNetConfResult['dnsServer2'] = (isset($result->DNSServer2)) ? trim($result->DNSServer2->__toString()) : '';
            $webNetConfResult['callManager1'] = (isset($result->CallManager1)) ? trim($result->CallManager1->__toString()) : '';
            $webNetConfResult['callManager2'] = (isset($result->CallManager2)) ? trim($result->CallManager2->__toString()) : '';
            $webNetConfResult['callManager3'] = (isset($result->CallManager3)) ? trim($result->CallManager3->__toString()) : '';
            $webNetConfResult['callManager4'] = (isset($result->CallManager4)) ? trim($result->CallManager4->__toString()) : '';
            $webNetConfResult['vlanId'] = (isset($result->VLANId)) ? trim($result->VLANId->__toString()) : '';
            $webNetConfResult['userLocale'] = (isset($result->UserLocale)) ? trim($result->UserLocale->__toString()) : '';
        } else {
            // Чтение HTML
            $dom = HtmlDomParser::str_get_html(file_get_contents('http://' . $ipAddress . '/NetworkConfiguration'));
            if (false !== $dom) {
                // Define the phone's environment
                preg_match('~\d+~', $phoneModel, $matches);
                switch ($matches[0]) {
                    case '7912':
                        $rows = $dom->find('form table tr');
                        $item = 1;
                        break;
                    case '7905':
                        $rows = $dom->find('form table tr');
                        $item = 1;
                        break;
                    case '7940':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    case '7960':
                        $rows = $dom->find('table tr');
                        $item = 2;
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
                    'dhcp-сервер' => 'dhcpServer',
                    'domainname' => 'domainName',
                    'имядомена' => 'domainName',
                    'tftpserver1' => 'tftpServer1',
                    'tftp-сервер1' => 'tftpServer1',
                    'tftpserver2' => 'tftpServer2',
                    'tftp-сервер2' => 'tftpServer2',
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
                    'локал.польз.' => 'userLocale',
                    'subnetmask' => 'subNetMask',
                    'маскаподсети' => 'subNetMask',
                ];
                foreach ($rows as $row) {
                    $td = is_null($row->find('td', 0)) ? null : $row->find('td', 0)->text();
                    $field = $phoneFields[mb_ereg_replace(' ', '', mb_strtolower($td))];
                    $iconv = false;
                    if (is_null($field)) {
                        $td = is_null($row->find('td', 0)) ? null : iconv("WINDOWS-1251","UTF-8", $row->find('td', 0)->text());
                        $field = $phoneFields[mb_ereg_replace(' ', '', mb_strtolower($td))];
                        $iconv = true;
                    }
                    if (!is_null($field)) {
                        if ($iconv) {
                            $var = trim(is_null($row->find('td', $item)) ? null : iconv("WINDOWS-1251","UTF-8", $row->find('td', $item)->text()));
                        } else {
                            $var = trim(is_null($row->find('td', $item)) ? null : $row->find('td', $item)->text());
                        }
                        $webNetConfResult[$field] = $var;
                    }
                }
            } else {
                return null;
            }
        }
        return $webNetConfResult;
    }

    /**
     * @param $ipAddress
     * @param $phoneModel
     * @return array|null
     */
    public static function getDataFromWebPortInfo($ipAddress, $phoneModel)
    {
        $webPortInfoResult = [
            'cdpNeighborDeviceId' => '',
            'cdpNeighborIP' => '',
            'cdpNeighborPort' => '',
        ];

        // Чтение XML
        $phoneData = simplexml_load_file('http://' . $ipAddress . '/PortInformationX?1');
        if (false !== $phoneData) {
            preg_match('~\d+~', $phoneModel, $matches);
            switch ($matches[0]) {
                case '7940':
                    $webPortInfoResult ['cdpNeighborDeviceId'] = (isset($phoneData->deviceId)) ? trim($phoneData->deviceId->__toString()) : '';
                    $webPortInfoResult ['cdpNeighborIP'] = (isset($phoneData->ipAddress)) ? trim($phoneData->ipAddress->__toString()) : '';
                    $webPortInfoResult ['cdpNeighborPort'] = (isset($phoneData->port)) ? trim($phoneData->port->__toString()) : '';
                    break;
                case '7911':
                    $webPortInfoResult ['cdpNeighborDeviceId'] = (isset($phoneData->NeighborDeviceId)) ? trim($phoneData->NeighborDeviceId->__toString()) : '';
                    $webPortInfoResult ['cdpNeighborIP'] = (isset($phoneData->NeighborIP)) ? trim($phoneData->NeighborIP->__toString()) : '';
                    $webPortInfoResult ['cdpNeighborPort'] = (isset($phoneData->NeighborPort)) ? trim($phoneData->NeighborPort->__toString()) : '';
                    break;
                default:
                    $webPortInfoResult ['cdpNeighborDeviceId'] = (isset($phoneData->CDPNeighborDeviceId)) ? trim($phoneData->CDPNeighborDeviceId->__toString()) : '';
                    $webPortInfoResult ['cdpNeighborIP'] = (isset($phoneData->CDPNeighborIP)) ? trim($phoneData->CDPNeighborIP->__toString()) : '';
                    $webPortInfoResult ['cdpNeighborPort'] = (isset($phoneData->CDPNeighborPort)) ? trim($phoneData->CDPNeighborPort->__toString()) : '';
            }
        } else {
            // Чтение HTML
            $dom = HtmlDomParser::str_get_html(file_get_contents('http://' . $ipAddress . '/PortInformation?1'));
            if (false !== $dom) {
                // Define the phone's environment
                preg_match('~\d+~', $phoneModel, $matches);
                switch ($matches[0]) {
                    case '6921':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    case '7911':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    case '7940':
                        $rows = $dom->find('table tr');
                        $item = 2;
                        break;
                    case '7960':
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
                    $td = is_null($row->find('td', 0)) ? null : $row->find('td', 0)->text();
                    $field = $phoneFields[mb_ereg_replace('[ -]', '', mb_strtolower($td))];
                    $iconv = false;
                    if (is_null($field)) {
                        $td = is_null($row->find('td', 0)) ? null : iconv("WINDOWS-1251","UTF-8", $row->find('td', 0)->text());
                        $field = $phoneFields[mb_ereg_replace('[ -]', '', mb_strtolower($td))];
                        $iconv = true;
                    }
                    if (!is_null($field)) {
                        if ($iconv) {
                            $var = trim(is_null($row->find('td', $item)) ? null : iconv("WINDOWS-1251","UTF-8", $row->find('td', $item)->text()));
                        } else {
                            $var = trim(is_null($row->find('td', $item)) ? null : $row->find('td', $item)->text());
                        }
                        $webPortInfoResult[$field] = $var;
                    }
                }
            } else {
                return null;
            }
        }
        return $webPortInfoResult;
    }
}
