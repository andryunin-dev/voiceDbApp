<?php

require __DIR__ . '/../protected/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../protected/boot.php';

class ModelsTest extends \PHPUnit\Framework\TestCase
{
    public function compareModels($model1, $model2)
    {
        foreach ($model1 as $key => $value) {
            if (!isset($model2->$key)) {
                echo get_class($model2) . '->' . $key . ' not found in ' . get_class($model2) . "\n";
                return false;
            }
            if ($model1->$key !== $model2->$key) {
                if ($key != get_class($model1)::PK) {
                    echo get_class($model1) . '->' . $key . ' != ' . get_class($model2) . '->' . $key . "\n";
                    return false;
                }
                if ($key == get_class($model1)::PK && !empty($model1->$key) && !empty($model1->$key)) {
                    echo get_class($model1) . '->' . $key . ' != ' . get_class($model2) . '->' . $key . "\n";
                    return false;
                }
            }
        }
        return true;
    }

    public function testInit()
    {
        $app = \T4\Console\Application
            ::instance()
            ->setConfig(new \T4\Core\Config(ROOT_PATH_PROTECTED . '/config.php'));
        $app->db->default = $app->db->phpUnitTest;
        $conn = $app->db->default;
        \T4\Orm\Model::setConnection($conn);
        $this->assertInstanceOf('\T4\Dbal\Connection', \T4\Orm\Model::getDbConnection());
    }

    /**
     * GEOLOCATION TESTS
     *
     * @depends testInit
     */
    public function testRegionModel()
    {
        $region = (new \App\Models\Region())
            ->fill([
                'title' => 'title region'
            ])
            ->save();
        $fromDb = \App\Models\Region::findByPK($region->getPk());
        $this->assertEquals(true, $this->compareModels($region, $fromDb));
        return $region;
    }

    /**
     * @depends testRegionModel
     */
    public function testCityModel(\App\Models\Region $region)
    {
        $city = (new \App\Models\City())
            ->fill([
                'title' => 'title city',
                'diallingCode' => '1234',
                'region' => $region
            ])
            ->save();
        $fromDb = \App\Models\City::findByPK($city->getPk());
        $this->assertEquals(true, $this->compareModels($city, $fromDb));
        return $city;
    }

    /**
     * @depends testCityModel
     */
    public function testAddress($city)
    {
        $address = (new \App\Models\Address())
            ->fill([
                'address' => 'addresses text',
                'city' => $city
            ])
            ->save();
        $fromDb = \App\Models\Address::findByPK($address->getPk());
        $this->assertEquals(true, $this->compareModels($address, $fromDb));
        return $address;
    }

    /**
     * COMPANY TESTS
     * @depends testInit
     *
     */
    public function testOfficeStatusModel()
    {
        $officeStatus = (new \App\Models\OfficeStatus())
            ->fill([
                'title' => 'title office status'
            ])
            ->save();
        $fromDb = \App\Models\OfficeStatus::findByPK($officeStatus->getPk());
        $this->assertEquals(true, $this->compareModels($officeStatus, $fromDb));
        return $officeStatus;
    }

    /**
     * @depends testOfficeStatusModel
     * @depends testAddress
     *
     */
    public function testOfficeModel($officeStatus, $address)
    {
        $office = (new \App\Models\Office())
            ->fill([
                'title' => 'title office',
                'lotusId' => rand(0, 2147483647),
                'details' => ['propName' => 'propValue'],
                'comment' => 'comment',
                'status' => $officeStatus,
                'address' => $address
            ])
            ->save();
        $fromDb = \App\Models\Office::findByPK($office->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($office, $fromDb));
        return $office;
    }

    /**
     * EQUIPMENT TESTS
     * @depends testInit
     */
    public function testCluster()
    {
        $objectName = 'cluster';
        $className = '\App\Models\Cluster';
        $$objectName = (new $className())
            ->fill([
                'title' => 'cluster title',
                'details' => ['propName' => 'propValue'],
                'comment' => 'cluster comment'
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testInit
     *
     */
    public function testVendor()
    {
        $vendor = (new \App\Models\Vendor())
            ->fill([
                'title' => 'vendor name'
            ])
            ->save();
        $fromDb = \App\Models\Vendor::findByPK($vendor->getPk());
        $this->assertEquals(true, $this->compareModels($vendor, $fromDb));
        return $vendor;
    }

    /**
     * @depends testVendor
     */
    public function testSoftware($vendor)
    {
        $objectName = 'software';
        $className = '\App\Models\Software';
        $$objectName = (new $className())
            ->fill([
                'title' => 'soft title',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testSoftware
     */
    public function testSoftwareItem($software)
    {
        $objectName = 'softwareItem';
        $className = '\App\Models\SoftwareItem';
        $$objectName = (new $className())
            ->fill([
                'version' => 'soft ver',
                'details' => ['propName' => 'propValue'],
                'comment' => 'soft comment',
                'software' => $software
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testVendor
     */
    public function testPlatform($vendor)
    {
        $objectName = 'platform';
        $className = '\App\Models\Platform';
        $$objectName = (new $className())
            ->fill([
                'title' => 'platform title',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testPlatform
     */
    public function testPlatformItem($platform)
    {
        $objectName = 'platformItem';
        $className = '\App\Models\PlatformItem';
        $$objectName = (new $className())
            ->fill([
                'version' => 'platform ver',
                'inventoryNumber' => 'inventory Number',
                'serialNumber' => 'serial Number',
                'details' => ['propName' => 'propValue'],
                'comment' => 'platform comment',
                'platform' => $platform
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testInit
     */
    public function testApplianceType()
    {
        $objectName = 'applianceType';
        $className = '\App\Models\ApplianceType';
        $$objectName = (new $className())
            ->fill([
                'type' => 'appliance type'
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;

    }

   /**
     * @depends testApplianceType
     * @depends testCluster
     * @depends testVendor
     * @depends testPlatformItem
     * @depends testSoftwareItem
     * @depends testOfficeModel
     */
    public function testAppliance($applianceType, $cluster, $vendor, $platformItem, $softwareItem, $office)
    {
        $objectName = 'appliance';
        $className = '\App\Models\Appliance';
        $$objectName = (new $className())
            ->fill([
                'details' => ['propName' => 'propValue'],
                'comment' => 'platform comment',
                'type' => $applianceType,
                'cluster' => $cluster,
                'vendor' => $vendor,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'location' => $office
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testVendor
     */
    public function testModule($vendor)
    {
        $objectName = 'module';
        $className = '\App\Models\Module';
        $$objectName = (new $className())
            ->fill([
                'partNumber' => 'part Number',
                'comment' => 'comment',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testModule
     * @depends testAppliance
     */
    public function testModuleItem($module, $appliance)
    {
        $objectName = 'moduleItem';
        $className = '\App\Models\ModuleItem';
        $$objectName = (new $className())
            ->fill([
                'serialNumber' => 'part Number',
                'inventoryNumber' => 'comment',
                'details' => ['propName' => 'propValue'],
                'comment' => 'module item comment',
                'module' => $module,
                'appliance' => $appliance
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }


    /**
     * @depends testInit
     */
    public function testDPortType()
    {
        $objectName = 'dPortType';
        $className = '\App\Models\DPortType';
        $$objectName = (new $className())
            ->fill([
                'type' => 'data port type'
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testDPortType
     * @depends testAppliance
     */
    public function testDataPort($dPortType, $appliance)
    {
        $objectName = 'dataPort';
        $className = '\App\Models\DataPort';
        $$objectName = (new $className())
            ->fill([
                'ipAddress' => '192.168.1.1/24',
                'macAddress' => '08:00:2b:01:02:03',
                'details' => ['propName' => 'propValue'],
                'comment' => 'data port comment',
                'appliance' => $appliance,
                'portType' => $dPortType
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testInit
     */
    public function testVPortType()
    {
        $objectName = 'vPortType';
        $className = '\App\Models\VPortType';
        $$objectName = (new $className())
            ->fill([
                'type' => 'voice port type'
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testVPortType
     * @depends testAppliance
     */
    public function testVoicePort($vPortType, $appliance)
    {
        $objectName = 'voicePort';
        $className = '\App\Models\VoicePort';
        $$objectName = (new $className())
            ->fill([
                'details' => ['propName' => 'propValue'],
                'comment' => 'voice port comment',
                'appliance' => $appliance,
                'portType' => $vPortType
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }


    /**
     * @depends testInit
     */
    public function testOrganisation()
    {
        $objectName = 'organisation';
        $className = '\App\Models\Organisation';
        $$objectName = (new $className())
            ->fill([
                'title' => 'organisation title',
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testOrganisation
     * @depends testAddress
     */
    public function testPartnerOffice($organisation, $address)
    {
        $objectName = 'partnerOffice';
        $className = '\App\Models\PartnerOffice';
        $$objectName = (new $className())
            ->fill([
                'details' => ['propName' => 'propValue'],
                'comment' => 'partner office comment',
                'organisation' => $organisation,
                'address' => $address
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testPartnerOffice
     */
    public function testPerson($partnerOffice)
    {
        $objectName = 'person';
        $className = '\App\Models\Person';
        $$objectName = (new $className())
            ->fill([
                'name' => 'Ivanov Ivan Ivanovich',
                'position' => 'manager',
                'details' => ['propName' => 'propValue'],
                'comment' => 'comment for person',
                'office' => $partnerOffice
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testInit
     */
    public function testContactType()
    {
        $objectName = 'contactType';
        $className = '\App\Models\ContactType';
        $$objectName = (new $className())
            ->fill([
                'type' => 'contact type',
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        //$fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testContactType
     * @depends testPerson
     */
    public function testContact($contactType, $person)
    {
        $objectName = 'contact';
        $className = '\App\Models\Contact';
        $$objectName = (new $className())
            ->fill([
                'contact' => 'email or phone number etc.',
                'extension' => 'extension dialing',
                'details' => ['propName' => 'propValue'],
                'comment' => 'contact comment',
                'type' => $contactType,
                'person' => $person
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $fromDb->details = json_encode($fromDb->details->toArray());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testInit
     */
    public function testContractType()
    {
        $objectName = 'contractType';
        $className = '\App\Models\ContractType';
        $$objectName = (new $className())
            ->fill([
                'title' => 'contract type 1',
            ])
            ->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testContractType
     * @depends testPartnerOffice
     * @depends testPerson
     */
    public function testContract($contractType, $partnerOffice, $person)
    {
        $objectName = 'contract';
        $className = '\App\Models\Contract';
        $$objectName = (new $className())
            ->fill([
                'number' => 'contract 1',
                'date' => '2017-02-23',
                'pathToScan' => '/c/data/scan/договор1.pdf',
                'contractType' => $contractType,
                'partnerOffice' => $partnerOffice
            ])
            ->save();
        $$objectName->persons->add($person);
        $$objectName->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }

    /**
     * @depends testVoicePort
     * @depends testContract
     */
    public function testPstnNumber($voicePort, $contract)
    {
        $objectName = 'pstnNumber';
        $className = '\App\Models\PstnNumber';
        $$objectName = (new $className())
            ->fill([
                'number' => (string)rand(1000000000, 9999999999),
                'transferedTo' => (string)rand(1000000000, 9999999999),
                'comment' => 'pstn number comment',
                'voicePort' => $voicePort
            ])
            ->save();
        $$objectName->contracts->add($contract);
        $$objectName->save();
        $fromDb = $className::findByPK($$objectName->getPk());
        $this->assertEquals(true, $this->compareModels($$objectName, $fromDb));
        return $$objectName;
    }



}