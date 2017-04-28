<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';
require_once __DIR__ . '/../EnvironmentTrait.php';


class RServerTest extends \PHPUnit\Framework\TestCase
{
    use DbTrait;
    use EnvironmentTrait;

    public function providerValidDataSetAppliance()
    {
        return [
            [
                '{
                    "platformSerial":"testPS",
                    "applianceModules":[
                        {
                            "serial":"sn 1",
                            "product_number":"pr_num 1",
                            "description":"desc 1"
                        },
                        {
                            "serial":"sn 2",
                            "product_number":"pr_num 2",
                            "description":"desc 2"
                        }
                    ],
                    "LotusId":"1",
                    "hostname":"host",
                    "applianceType":"device",
                    "softwareVersion":"ver soft",
                    "chassis":"ch 1",
                    "platformTitle":"pl_title 1",
                    "ip":"10.100.240.195/24",
                    "applianceSoft":"soft",
                    "platformVendor":"CISCO"}
                ',
                202
            ]
        ];
    }

    public function pushData($jsonDataSet)
    {
        $url = "http://10.99.120.170/rserver/test";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonDataSet);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $requestResult =  json_decode(curl_exec($curl));
        curl_close($curl);

        return $requestResult;
    }

    /**
     * @param $jdataSet
     * @param $codeResult
     *
     * @dataProvider providerValidDataSetAppliance
     */
    public function testValidAppliance($jdataSet, $codeResult)
    {
        $this->createOffice();

        $resultRequest = $this->pushData($jdataSet);
        $this->assertEquals($codeResult, $resultRequest->httpStatusCode);

        $dataSet = json_decode($jdataSet);

        $vendors = \App\Models\Vendor::findAllByTitle($dataSet->platformVendor);
        $this->assertEquals(1, $vendors->count());
        $this->assertInstanceOf(\App\Models\Vendor::class, $vendors->first());

        $platforms = \App\Models\Platform::findAllByTitle($dataSet->platformTitle);
        $this->assertEquals(1, $platforms->count());
        $platform = $platforms->first();
        $this->assertEquals(true, $platform->vendor->title == $dataSet->platformVendor);


//        $requestPlatformTitle = $dataSet->platformTitle;
//        $platform = $vendor->platforms->filter(
//            function($platform) use ($requestPlatformTitle) {
//                return $requestPlatformTitle == $platform->title;
//            }
//        )->first();

//        $requestPlatformSerial = $dataSet->platformSerial;
//        $platformItem = $platform->platformItems->filter(
//            function($platformItem) use ($requestPlatformSerial) {
//                return $requestPlatformSerial == $platformItem->serialNumber;
//            }
//        )->first();

//        $requestApplianceSoft = $dataSet->applianceSoft;
//        $software = $vendor->software->filter(
//            function($software) use ($requestApplianceSoft) {
//                return $requestApplianceSoft == $software->title;
//            }
//        )->first();

//        $requestSoftwareVersion = $dataSet->softwareVersion;
//        $softwareItem = $software->softwareItems->filter(
//            function($softwareItem) use ($requestSoftwareVersion) {
//                return $requestSoftwareVersion == $softwareItem->version;
//            }
//        )->first();

//        $applianceType = \App\Models\ApplianceType::findByType($dataSet->applianceType);
//        $appliance = $platformItem->appliance;



//        $this->assertInstanceOf(\App\Models\PlatformItem::class, $platformItem);
//        $this->assertInstanceOf(\App\Models\Software::class, $software);
//        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $softwareItem);
//        $this->assertInstanceOf(\App\Models\ApplianceType::class, $applianceType);
//        $this->assertInstanceOf(\App\Models\Appliance::class, $appliance);

//        foreach ($dataSet->applianceModules as $applianceModule) {
//            $requestModuleTitle = $applianceModule->product_number;
//            $module = $vendor->modules->filter(
//                function($module) use ($requestModuleTitle) {
//                    return $requestModuleTitle == $module->title;
//                }
//            )->first();
//
//            $moduleItemSerial = $applianceModule->serial;
//            $moduleItem = $module->moduleItems->filter(
//                function($moduleItem) use ($moduleItemSerial) {
//                    return $moduleItemSerial == $moduleItem->serialNumber;
//                }
//            )->first();
//
//            $this->assertInstanceOf(\App\Models\Module::class, $module);
//            $this->assertInstanceOf(\App\Models\ModuleItem::class, $moduleItem);
//        }
//
//        $ip = $dataSet->ip;
//        $vrf = $dataSet->vrf ?? \App\Models\Vrf::instanceGlobalVrf();
//        $dataPort = \App\Models\DataPort::findByIpVrf($ip, $vrf);
//
//        $this->assertInstanceOf(\App\Models\DataPort::class, $dataPort);
    }
}

