<?php

namespace App\Controllers;

use App\Commands\TestCucmsThreads;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Phone;
use App\ViewModels\DevGeo_View;
use App\ViewModels\DevModulePortGeo;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionGetPhone($name = null)
    {

//        $name = 'SEP34BDC8C7A589';
//        $name = 'SEP0017593A3139';
        $name = 'SEP0012D940DE03';

        $cmd = 'php '.ROOT_PATH.DS.'protected'.DS.'t4.php cucmsPhones'.DS.'getPhoneByName --name='. $name;
        exec($cmd, $result);

        var_dump($result);
//  array (size=3)
//  0 => string '{"error":"Could not connect to host","cucm":"10.101.15.10"}' (length=59)
//  1 => string '{"error":"Could not connect to host","cucm":"10.101.19.100"}' (length=60)
//  2 => string '{"name":"SEP0012D940DE03","description":"Kzn 8706 SIC","css":"CSS_MSK_10_Local","devicepool":"DP_KZN_AGENT_08","phonedn":"8706","alertingname":"Kazan SIC","model":"Cisco 7940","prefix":559,"partition":"","publisherIp":"10.30.30.21","ipAddress":"10.102.130.14","status":"Registered","class":"Phone","macAddress":"0012D940DE03","serialNumber":"INM084919JL","modelNumber":"CP-7940G","versionID":"8.1(SR.2)","appLoadID":"P0030801SR02","timezone":null,"dhcpEnabled":"\u0414\u0430","dhcpServer":"10.30.33.112","domainName":"rs.ru","subNetMask":"255.255.252.0","tftpServer1":"10.30.30.50","tftpServer2":"10.30.30.21","defaultRouter":"10.102.128.1","dnsServer1":"10.30.33.100","dnsServer2":"10.30.33.152","callManager1":"10.30.30.27  Active","callManager2":"10.30.30.26  Standby","callManager3":"","callManager4":"","vlanId":"6","userLocale":"Russian_Russian_Federation","cdpNeighborDeviceId":"cc-kzn-chst33-sw-2.net.rs.ru","cdpNeighborIP":"10.100.108.6","cdpNeighborPort":"FastEthernet6\/0\/30"}' (length=988)

        die;
    }

    public function actionTt()
    {

        $cucmIp = '10.101.19.100'; // 9.1
        $cucmIp = '10.101.15.10'; // 7.1
//        $cucmIp = '10.30.30.21'; // 8.6
//        $cucmIp = '10.30.30.70'; // 8.6

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
//                'verify_peer_name' => false,
                'allow_self_signed' => true,
//                'capath' => '/etc/ssl/certs/',
//                'ciphers' => 'AES256-SHA, DHE-RSA-AES128-SHA',
//                'ciphers' => 'SHA1:HIGH:!aNULL:!kRAS:!MD5:!RC4',
            ]
        ]);

        $schema = '7.1';
        $axlClient = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . $schema . '/AXLAPI.wsdl'), [
            'trace' => true,
            'exception' => false,
            'encoding' => 'utf-8',
            'location' => 'https://' . $cucmIp . ':8443/axl/',
            'login' => 'netcmdbAXL',
            'password' => 'Dth.dAXL71',
            'stream_context' => $context,
        ]);
        var_dump($axlClient);


        try {
            $versionCucm = $axlClient->GetCCMVersion()->return->componentVersion->version;
        } catch (\SoapFault $e) {
            var_dump($e->getMessage());
            var_dump($e->getTrace());
        }

        var_dump($versionCucm);

//        $phoneName = 'SEP500604726205';
//        try {
//            $phone = Phone::findByNameIntoCucm($phoneName, $cucmIp);
//        } catch (\SoapFault $e) {
//            var_dump('llllll l l');
//            var_dump($e->getMessage());
//            var_dump($e->getCode());
//        }
//        var_dump($phone);

        die;
    }


    public function actionDeleteVeryOldAnalogPhones()
    {
        $query = (new Query())
            ->select(['appliance_id', 'appAge', 'platformSerial'])
            ->from(DevGeo_View::getTableName())
            ->where('"appType" = :appType AND ("platformTitle" = :platform_title_1 OR "platformTitle" = :platform_title_2) AND "appAge" > 300')
            ->params([
                ':appType' => 'phone',
                ':platform_title_1' => 'Analog Phone',
                ':platform_title_2' => 'VGC Phone',
            ]);

        $res = DevGeo_View::findAllByQuery($query);
        $counter = 0;
        foreach ($res as $dev) {
            $item = Appliance::findByPK($dev->appliance_id);
            if ($item instanceof Appliance) {
                $item->delete();

                echo ++$counter . ' - ' . $item->platform->platform->title . ' - has been deleted' . "\n";
            }
        }
    }
}