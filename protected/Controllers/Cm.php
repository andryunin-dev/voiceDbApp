<?php
namespace App\Controllers;

use App\Components\CucmPhones;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Mvc\Controller;

class Cm extends Controller
{
    public function actionDefault()
    {
//        $cucmPhones = new CucmPhones('10.101.2.132');
        $cucmPhones = new CucmPhones('10.101.2.10');
//        var_dump($cucmPhones);
        var_dump($cucmPhones->run());
        die;


        try {
            // Publishers IP
            $publishersIP = (new Std())
                ->fill([
                    'rs-cucm0-sam' => '10.101.2.132',
                    'rs-cucm0-nsk' => '10.101.2.10',
                    'rs-cucm0-brn' => '10.101.165.10',
                    'rs-cucm0-ekt' => '10.101.3.10',
                    'rs-cucm0-vlg' => '10.101.2.210',
                    'rs-cucm0-rst' => '10.101.6.130',
                    'rs-cucm0-spb' => '10.101.3.131',
                    'rs-cucm0-nng' => '10.101.3.80',
                    'rs-cucm0-irk' => '10.101.192.10',
                    'rs-cucm' => '10.101.15.10',
                    'rs-cucm0-omsk' => '10.101.164.10',
//                    'rs-cucm0-kzn' => '10.101.165.210',
                ]);

            // Common client's options
            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'ciphers' => 'HIGH',
                ]
            ]);
            $username = 'netcmdbAXL';
            $password = 'Dth.dAXL71';


            foreach ($publishersIP as $publisherIP) {

                // AXL client's options
                $location = 'https://' . $publisherIP . ':8443/axl';
                $schema = 'sch7_1';
                $wsdl = realpath(ROOT_PATH . '/AXLscheme/' . $schema . '/AXLAPI.wsdl');

                // AXL client
                $client = new \SoapClient($wsdl, [
                    'trace' => true,
                    'exception' => true,
                    'location' => $location,
                    'login' => $username,
                    'password' => $password,
                    'stream_context' => $context,
                ]);
//                var_dump($client);
//                var_dump($client->__getFunctions());

                // Poll the phones registered to the publisher
                $phones = $client->ExecuteSQLQuery(['sql' => '
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
                ']);
//                var_dump($phones);


                // RisPort client's options
                $locationRisPort = 'https://' . $publisherIP . ':8443/realtimeservice/services/RisPort';
                $wsdlRisPort = 'https://' . $publisherIP . ':8443/realtimeservice/services/RisPort?wsdl';

                // RisPort client
                $clientRisPort = new \SoapClient($wsdlRisPort, [
                    'trace' => true,
                    'exception' => true,
                    'location' => $locationRisPort,
                    'login' => $username,
                    'password' => $password,
                    'stream_context' => $context,
                ]);
//                var_dump($clientRisPort);
//                var_dump($clientRisPort->__getFunctions());

                $responce = $clientRisPort->GetServerInfo();
                var_dump($responce);
            }

            die;





//            $responce = $client->ListPhoneByName(['searchString' => '%']);
//            var_dump($responce);

//            $responce = $client->GetPhone(['phoneId' => '{7F2D2B07-BB75-4DC1-A1EA-013C08BF849D}']); // css = 'HQ_50_City'
//            var_dump($responce);


            // -- Список всех серверов в кластере
//            $processNodes = ($client->ListAllProcessNodes())->return->processNode; // TODO здесь нет поля 'nodeid' (есть 'uuid'), если это критично, то надо будет использовать sql запрос
//            var_dump($processNodes);

            // -- Список всех серверов в кластере
//            $serversOfCluster = $client->ExecuteSQLQuery(['sql' => '
//                SELECT name, nodeid, description
//                FROM ProcessNode
//            ']);
//            var_dump($serversOfCluster);

            // Для программистов необходима была таблица с префиксами
//            $prefixes = $client->ExecuteSQLQuery(['sql' => '
//                SELECT n2."dnorpattern", n2."description"
//                FROM numplan AS n2
//                WHERE n2."tkpatternusage" = 3 AND
//                      n2."calledpartytransformationmask" IS NOT NULL AND
//                      n2."dnorpattern" LIKE "5%" AND
//                      LENGTH (n2."dnorpattern") = 8
//            ']);
//            var_dump($prefixes);



            $wsdlRisPort = 'https://' . $publisherIP . ':8443/realtimeservice/services/RisPort?wsdl';
            $locationRisPort = 'https://' . $publisherIP . ':8443/realtimeservice/services/RisPort';
            $clientRisPort = new \SoapClient($wsdlRisPort, [
                'trace' => true,
                'exception' => true,
                'location' => $locationRisPort,
                'login' => $username,
                'password' => $password,
                'stream_context' => $context,
            ]);
            var_dump($clientRisPort);
            var_dump($clientRisPort->__getFunctions());

            $responce = $clientRisPort->GetServerInfo();
            var_dump($responce);


            $items['SelectItem[0]']['Item'] = 'SEP001956778188';
            $responce = $clientRisPort->SelectCmDevice('',[
                'MaxReturnedDevices' => 1000,
                'Class' => 'Phone',
                'Model' => 255,
                'Status' => 'Any',
                'NodeName' => '',
                'SelectBy' => 'Name',
                'SelectItems' => $items,
            ]);
            var_dump($responce);


            $responce = file_get_contents('http://10.101.186.54/DeviceInformationX');
            $responce = new \SimpleXMLElement($responce);
            var_dump($responce);

//            $responce = $client->ListRoutePlanByType(['usage' => 'Device']);
            $responce = $client->ListRoutePlanByType(['usage' => 'Translation']);
            var_dump($responce);




        } catch (\SoapFault $e) {
            echo $e->getMessage();
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        die;
    }
}
