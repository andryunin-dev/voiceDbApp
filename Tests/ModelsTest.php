<?php

require __DIR__ . '/../protected/autoload.php';
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../protected/boot.php';

class ModelsTest extends \PHPUnit\Framework\TestCase
{
    protected $schemaList = [
        ['geolocation'],
        ['company'],
        ['telephony'],
        ['equipment'],
        ['partners'],
        ['contact_book'],
        ['network']
    ];

    public function testInit()
    {
        $app = \T4\Console\Application
            ::instance()
            ->setConfig(new \T4\Core\Config(ROOT_PATH_PROTECTED . '/config.php'));
        $app->db->default = $app->db->phpUnitTest;
        $conn = $app->db->default;
        \T4\Orm\Model::setConnection($conn);
        $this->assertInstanceOf('\T4\Dbal\Connection', \T4\Orm\Model::getDbConnection());
        return $app;
    }

    public function schemaProvider()
    {
        return $this->schemaList;
    }

    /**
     * @depends testInit
     * @dataProvider schemaProvider
     */
    public function testTruncateTables($schema, $app)
    {
        /**
         * @var \T4\Dbal\Connection $connection
         */
        $connection = $app->db->default;
        $tables = $connection->query('SELECT table_name FROM information_schema.tables WHERE table_schema = :schemaName', ['schemaName' => $schema])->fetchAll(PDO::FETCH_ASSOC);

        $tables = array_map(function ($item) {return array_pop($item);}, $tables);
        foreach ($tables as $table) {
//            echo 'truncate ' . $table . ' table... ';
            $this->assertTrue($connection->execute('TRUNCATE ' . $schema . '."' . $table . '" CASCADE'));
//            echo 'Done!' . "\n";
        }
    }

    /**
     * GEOLOCATION TESTS
     *
     * @depends testInit
     */
    public function testRegion()
    {
        $region = (new \App\Models\Region())
            ->fill([
                'title' => 'region'
            ])
            ->save();
        $fromDb = \App\Models\Region::findByPK($region->getPk());
        $this->assertInstanceOf(\App\Models\Region::class, $fromDb);
        $this->assertEquals('region', $fromDb->title);
        return $region;
    }

    /**
     * @depends testRegion
     */
    public function testCity(\App\Models\Region $region)
    {
        $city = (new \App\Models\City())
            ->fill([
                'title' => 'test',
                'region' => $region
            ])
            ->save();
        $fromDb = \App\Models\City::findByPK($city->getPk());
        $this->assertInstanceOf(\App\Models\City::class, $fromDb);
        $this->assertInstanceOf(\App\Models\Region::class, $fromDb->region);
        $this->assertEquals('test', $fromDb->title);
        return $city;
    }

    /**
     * @depends testCity
     */
    public function testAddress($city)
    {
        $address = (new \App\Models\Address())
            ->fill([
                'address' => 'test',
                'city' => $city
            ])
            ->save();
        $fromDb = \App\Models\Address::findByPK($address->getPk());
        $this->assertInstanceOf(\App\Models\Address::class, $fromDb);
        $this->assertInstanceOf(\App\Models\City::class, $fromDb->city);
        $this->assertEquals('test', $fromDb->address);
        return $address;
    }

    /**
     * COMPANY TESTS
     * @depends testInit
     *
     */
    public function testOfficeStatus()
    {
        $officeStatus = (new \App\Models\OfficeStatus())
            ->fill([
                'title' => 'test'
            ])
            ->save();
        $fromDb = \App\Models\OfficeStatus::findByPK($officeStatus->getPk());
        $this->assertInstanceOf(\App\Models\OfficeStatus::class, $fromDb);
        $this->assertEquals('test', $fromDb->title);
        return $officeStatus;
    }

    /**
     * @depends testOfficeStatus
     * @depends testAddress
     *
     */
    public function testOffice($officeStatus, $address)
    {
        $office = (new \App\Models\Office())
            ->fill([
                'title' => 'test',
                'lotusId' => 1,
                'details' => ['name' => 'Value'],
                'comment' => 'comment',
                'status' => $officeStatus,
                'address' => $address
            ])
            ->save();
        $fromDb = \App\Models\Office::findByPK($office->getPk());
        $this->assertInstanceOf(\App\Models\Office::class, $fromDb);
        $this->assertEquals('test', $fromDb->title);
        $this->assertEquals('Value', $fromDb->details->name);
        $this->assertInstanceOf(\App\Models\OfficeStatus::class, $fromDb->status);
        $this->assertInstanceOf(\App\Models\Address::class, $fromDb->address);
        return $office;
    }

    /**
     * EQUIPMENT TESTS
     * @depends testInit
     */
    public function testCluster()
    {
        $cluster = (new \App\Models\Cluster())
            ->fill([
                'title' => 'test',
                'details' => ['name' => 'Value']
            ])
            ->save();
        $fromDb = \App\Models\Cluster::findByPK($cluster->getPk());
        $this->assertInstanceOf(\App\Models\Cluster::class, $fromDb);
        $this->assertEquals('test', $fromDb->title);
        $this->assertEquals('Value', $fromDb->details->name);
        return $cluster;
    }

    /**
     * @depends testInit
     *
     */
    public function testVendor()
    {
        $vendor = (new \App\Models\Vendor())
            ->fill([
                'title' => 'test'
            ])
            ->save();

        $fromDb = \App\Models\Vendor::findByPK($vendor->getPk());
        $this->assertInstanceOf(\App\Models\Vendor::class, $fromDb);
        $this->assertEquals('test', $fromDb->title);
        return $vendor;
    }

    /**
     * @depends testVendor
     */
    public function testSoftware($vendor)
    {
        $sw = (new \App\Models\Software())
            ->fill([
                'title' => 'test',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = \App\Models\Software::findByPK($sw->getPk());
        $this->assertInstanceOf(\App\Models\Software::class, $fromDb);
        $this->assertEquals('test', $fromDb->title);
        $this->assertInstanceOf(\App\Models\Vendor::class, $fromDb->vendor);
        return $sw;
    }

    /**
     * @depends testSoftware
     */
    public function testSoftwareItem($software)
    {
        $swItem = (new \App\Models\SoftwareItem())
            ->fill([
                'version' => 'test',
                'details' => ['name' => 'Value'],
                'software' => $software
            ])
            ->save();
        $fromDb = \App\Models\SoftwareItem::findByPK($swItem->getPk());
        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $fromDb);
        $this->assertEquals('test', $fromDb->version);
        $this->assertEquals('Value', $fromDb->details->name);
        $this->assertInstanceOf(\App\Models\Software::class, $fromDb->software);
        return $swItem;
    }

    /**
     * @depends testVendor
     */
    public function testPlatform($vendor)
    {
        $platform = (new \App\Models\Platform())
            ->fill([
                'title' => 'test',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = \App\Models\Platform::findByPK($platform->getPk());
        $this->assertInstanceOf(\App\Models\Platform::class, $fromDb);
        $this->assertEquals('test', $fromDb->title);
        $this->assertInstanceOf(\App\Models\Platform::class, $fromDb);
        return $platform;
    }

    /**
     * @depends testPlatform
     */
    public function testPlatformItem($platform)
    {
        $platformItem = (new \App\Models\PlatformItem())
            ->fill([
                'version' => 'test',
                'inventoryNumber' => 'test',
                'serialNumber' => 'test',
                'details' => ['name' => 'Value'],
                'comment' => 'test',
                'platform' => $platform
            ])
            ->save();
        $fromDb = \App\Models\PlatformItem::findByPK($platformItem->getPk());
        $this->assertInstanceOf(\App\Models\PlatformItem::class, $fromDb);
        $this->assertEquals('Value', $fromDb->details->name);
        $this->assertEquals('test', $fromDb->version);
        $this->assertEquals('test', $fromDb->inventoryNumber);
        $this->assertEquals('test', $fromDb->serialNumber);
        $this->assertEquals('test', $fromDb->comment);
        $this->assertInstanceOf(\App\Models\Platform::class, $fromDb->platform);
        return $platformItem;
    }

    /**
     * @depends testInit
     */
    public function testApplianceType()
    {
        $applianceType = (new \App\Models\ApplianceType())
            ->fill([
                'type' => 'test'
            ])
            ->save();
        $fromDb = \App\Models\ApplianceType::findByPK($applianceType->getPk());
        $this->assertInstanceOf(\App\Models\ApplianceType::class, $fromDb);
        $this->assertEquals('test', $fromDb->type);
        return $applianceType;

    }

   /**
     * @depends testApplianceType
     * @depends testCluster
     * @depends testVendor
     * @depends testPlatformItem
     * @depends testSoftwareItem
     * @depends testOffice
     */
    public function testAppliance($applianceType, $cluster, $vendor, $platformItem, $softwareItem, $office)
    {
        $appliance = (new \App\Models\Appliance())
            ->fill([
                'details' => ['name' => 'Value'],
                'comment' => 'test',
                'type' => $applianceType,
                'cluster' => $cluster,
                'vendor' => $vendor,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'location' => $office
            ])
            ->save();
        $fromDb = \App\Models\Appliance::findByPK($appliance->getPk());
        $this->assertInstanceOf(\App\Models\Appliance::class, $fromDb);
        $this->assertEquals('Value', $fromDb->details->name);
        $this->assertEquals('test', $fromDb->type);
        $this->assertInstanceOf(\App\Models\ApplianceType::class, $fromDb->type);
        $this->assertInstanceOf(\App\Models\Cluster::class, $fromDb->cluster);
        $this->assertInstanceOf(\App\Models\Vendor::class, $fromDb->vendor);
        $this->assertInstanceOf(\App\Models\PlatformItem::class, $fromDb->platform);
        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $fromDb->software);
        $this->assertInstanceOf(\App\Models\Office::class, $fromDb->location);
        return $appliance;
    }

    /**
     * @depends testVendor
     */
    public function testModule($vendor)
    {
        $module = (new \App\Models\Module())
            ->fill([
                'title' => 'test',
                'description' => 'test',
                'vendor' => $vendor
            ])
            ->save();
        $fromDb = \App\Models\Module::findByPK($module->getPk());
        $this->assertInstanceOf(\App\Models\Module::class, $fromDb);
        $this->assertEquals('test', $fromDb->title);
        $this->assertEquals('test', $fromDb->description);
        $this->assertInstanceOf(\App\Models\Vendor::class, $fromDb->vendor);
        return $module;
    }

    /**
     * @depends testModule
     * @depends testAppliance
     */
    public function testModuleItem($module, $appliance)
    {
        $moduleItem = (new \App\Models\ModuleItem())
            ->fill([
                'serialNumber' => 'test',
                'inventoryNumber' => 'test',
                'details' => ['name' => 'Value'],
                'comment' => 'test',
                'module' => $module,
                'appliance' => $appliance
            ])
            ->save();
        $fromDb = \App\Models\ModuleItem::findByPK($moduleItem->getPk());
        $this->assertInstanceOf(\App\Models\ModuleItem::class, $fromDb);
        $this->assertEquals('test', $fromDb->serialNumber);
        $this->assertEquals('test', $fromDb->inventoryNumber);
        $this->assertEquals('test', $fromDb->inventoryNumber);
        $this->assertEquals('Value', $fromDb->details->name);
        $this->assertEquals('test', $fromDb->comment);
        $this->assertInstanceOf(\App\Models\Module::class, $fromDb->module);
        $this->assertInstanceOf(\App\Models\Appliance::class, $fromDb->appliance);
        return $moduleItem;
    }


    /**
     * @depends testInit
     */
    public function testDPortType()
    {
        $dPortType = (new \App\Models\DPortType())
            ->fill([
                'type' => 'test'
            ])
            ->save();
        $fromDb = \App\Models\DPortType::findByPK($dPortType->getPk());
        $this->assertInstanceOf(\App\Models\DPortType::class, $fromDb);
        $this->assertEquals('test', $fromDb->type);
        return $dPortType;
    }

    public function testVlan()
    {
        $vlan = (new \App\Models\Vlan())
            ->fill([
                'id' => 1,
                'name' => 'test',
                'comment' => 'test'
            ])
            ->save();
        $fromDb = \App\Models\Vlan::findByPK($vlan->getPk());
        $this->assertInstanceOf(\App\Models\Vlan::class, $fromDb);
        $this->assertEquals('test', $fromDb->name);
        $this->assertEquals('test', $fromDb->comment);
        return $vlan;
    }

    public function testVrf()
    {
        $vrf = (new \App\Models\Vrf())
            ->fill([
                'name' => 'test',
                'rd' => '10:100',
                'comment' => 'test'
            ])
            ->save();
        $fromDb = \App\Models\Vrf::findByPK($vrf->getPk());
        $this->assertInstanceOf(\App\Models\Vrf::class, $fromDb);
        $this->assertEquals('test', $fromDb->name);
        $this->assertEquals('10:100', $fromDb->rd);
        return $vrf;
    }

    /**
     * @depends testDPortType
     * @depends testAppliance
     */
    public function testDataPort($dPortType, $appliance)
    {
        die;
        $dataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => '192.168.1.1/24',
                'macAddress' => '00:11:22:33:44:55',
                'details' => ['name' => 'Value'],
                'comment' => 'test',
                'appliance' => $appliance,
                'portType' => $dPortType
            ])
            ->save();
        $fromDb = \App\Models\DataPort::findByPK($dataPort->getPk());
        $this->assertInstanceOf(\App\Models\DataPort::class, $fromDb);
        $this->assertEquals('192.168.1.1/24', $fromDb->ipAddress);
        $this->assertEquals('00:11:22:33:44:55', $fromDb->macAddress);
        $this->assertEquals('Value', $fromDb->details->name);
        $this->assertEquals('test', $fromDb->comment);
        $this->assertInstanceOf(\App\Models\Appliance::class, $fromDb->appliance);
        $this->assertInstanceOf(\App\Models\DPortType::class, $fromDb->portType);
        $this->assertInstanceOf(\App\Models\Network::class, $fromDb->network);
        $this->assertEquals('192.168.1.0/24', $fromDb->network->address);
        return $dataPort;
    }

    /**
     * @depends testInit
     */
    public function testVPortType()
    {
        die;
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