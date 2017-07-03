<?php
namespace App\Components;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class CucmPhones extends Std
{
    protected $axlClient;
    protected $risPortClient;
    protected $publisherIP;

    public function __construct($ip)
    {
        // Common client's options
        $publisherIP = (new IpTools($ip))->address;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'ciphers' => 'HIGH',
            ]
        ]);
        $username = '';
        $password = '';
        $schema = 'sch7_1';

        // AXL client
        $this->axlClient = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . $schema . '/AXLAPI.wsdl'), [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $publisherIP . ':8443/axl',
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
        ]);

        // RisPort client
        $this->risPortClient = new \SoapClient('https://' . $publisherIP . ':8443/realtimeservice/services/RisPort?wsdl', [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $publisherIP . ':8443/realtimeservice/services/RisPort',
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
        ]);

        $this->publisherIP = $publisherIP;
    }

    /**
     * @return bool
     */
    public function run()
    {
//        $debugLogger = RLogger::getInstance('CmPhones', realpath(ROOT_PATH . '/Logs/debug.log'));

        // Get all phones from the publisher
        $anyPhones = $this->axlClient->ExecuteSQLQuery(['sql' => '
                    SELECT d."name" AS Device,
                          d."description",
                          css."name" AS css,
                          css2."name" AS name_off_clause,
                          dp."name" AS dPool,
                          n2."dnorpattern" AS prefix,
                          n."dnorpattern",
                          n."alertingname" AS FIO,
                          partition."name" AS pt,
                          tm."name" AS type
                    FROM device AS d
                    INNER JOIN callingsearchspace AS css ON css."pkid" = d."fkcallingsearchspace"
                    INNER JOIN devicenumplanmap AS dmap ON dmap."fkdevice" = d."pkid" AND d."tkclass" = 1
                    INNER JOIN typemodel AS tm ON d."tkmodel" = tm."enum"
                    INNER JOIN numplan AS n ON dmap."fknumplan" = n."pkid"
                    INNER JOIN routepartition AS partition ON partition."pkid" = n."fkroutepartition"
                    INNER JOIN DevicePool AS dp ON dp."pkid" = d.fkDevicePool
                    INNER JOIN callingsearchspace AS css2 ON css2."clause" LIKE "%" || partition."name" || "%"
                    INNER JOIN numplan AS n2 ON n2."fkcallingsearchspace_translation" = css2."pkid"
                          WHERE n2."tkpatternusage" = 3 AND
                                n2."dnorpattern" LIKE "5%"
                '])->return->row;
//        var_dump($anyPhones);
//die;

        // Poll the registered phones
        $items = [];
        $n = 0;
        foreach ($anyPhones as $phone) {
            $items['SelectItem[' . $n .']']['Item'] = $phone->device;
            $n++;
        }
        $registeredPhones = $this->risPortClient->SelectCmDevice('',[
            'MaxReturnedDevices' => 1000,
            'Class' => 'Phone',
            'Model' => 255,
            'Status' => 'Registered',
            'NodeName' => '',
            'SelectBy' => 'Name',
            'SelectItems' => $items,
        ]);
        $cmNodes = ($registeredPhones['SelectCmDeviceResult'])->CmNodes;
//        var_dump($cmNodes);
//die;
        $registeredPhones = new Collection();
        foreach ($cmNodes as $cmNode) {
            if ('ok' == strtolower($cmNode->ReturnCode)) {
                foreach ($cmNode->CmDevices as $cmDevice) {
                    $phone = (new Std())->fill([
                        'cmName' => $cmNode->Name,
                        'Name' => $cmDevice->Name, // SEP0019AA4498D6
                        'IpAddress' => $cmDevice->IpAddress, // 10.101.64.16
                        'Description' => $cmDevice->Description, // Lenina52-UKP_FO-1400
                        'Httpd' => $cmDevice->Httpd, // Yes
                        'Status' => $cmDevice->Status, // Registered
                    ]);
                    $registeredPhones->add($phone);
                }
            }
        }
//        var_dump($registeredPhones);
//die;

        $errors = new MultiException();
        foreach ($registeredPhones as $phone) {
            try {

                $str = '<?xml version="1.0" encoding="iso-8859-1"?>
                            <DeviceInformation>
                                <MACAddress>001111111111</MACAddress>
                                <HostName>SEP000000000000</HostName>
                                <phoneDN>1301</phoneDN>
                                <appLoadID>CP7905080003SCCP070409A</appLoadID>
                                <bootLoadID>LD0100BOOT021112A</bootLoadID>
                                <versionID>8.0.3(070409A)</versionID>
                                <hardwareRevision>0x0005 0x0000</hardwareRevision>
                                <serialNumber>INM08321NVJ</serialNumber>
                                <modelNumber>CP-7905G</modelNumber>
                                <HWFeatures>0x00000002</HWFeatures>
                                <BTXMLCardsVersion>LD04-25-2002#0</BTXMLCardsVersion>
                                <MessageWaiting>NO</MessageWaiting>
                            </DeviceInformation>';

//                $response = xml_parse_into_struct(xml_parser_create(), file_get_contents('http://' . $phone->IpAddress . '/DeviceInformationX'), $values);
//                var_dump($values);

//                if (1 != $response) {
//                    throw new MultiException($phone->IpAddress . ' - Wrong XML');
//                }
//
//                foreach ($values as $k => $v) {
//                    if ('MACAddress' == $k || 'phoneDN' == $k || 'versionID' == $k || 'serialNumber' == $k || 'modelNumber' == $k) {
//                        $phone->fill([
//                            $k => $v,
//                        ]);
//                    }
//                }

//                $phone->fill([
//                    'MACAddress' => $data->MACAddress,
//                    'phoneDN' => $data->phoneDN,
//                    'versionID' => $data->versionID,
//                    'serialNumber' => $data->serialNumber,
//                    'modelNumber' => $data->modelNumber,
//                ]);

            } catch (Exception $e) {
                echo $e->getMessage();
                $errors->add('[phone]=' . ($phone->Name ?? '""') . ' [ip]=' . ($phone->IpAddress ?? '""') . ' [message]=' . ($e->getMessage() ?? '""'));
            }
        }
//        var_dump($errors);
//        var_dump($registeredPhones);



        echo 'OKK';
        return true;
    }
}
