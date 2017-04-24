<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';

class DataPortTest extends \PHPUnit\Framework\TestCase
{
    use DbTrait;

    const TEST_DB_NAME = 'phpUnitTest';

    protected static $schemaList = [
        'geolocation',
        'equipment',
        'company',
        'network'
    ];

    public static function setUpBeforeClass()
    {
        self::setDefaultDb(self::TEST_DB_NAME);
        foreach (self::$schemaList as $schema) {
            self::truncateTables($schema);
        }
    }
    public static function tearDownAfterClass()
    {
        self::setDefaultDb(self::TEST_DB_NAME);
        foreach (self::$schemaList as $schema) {
            self::truncateTables($schema);
        }
    }

    /**
     * create location(office)
     */
    public function testCreateLocation()
    {
        $region = (new \App\Models\Region())->fill(['title' => 'test'])->save();
        $this->assertInstanceOf(\App\Models\Region::class, $region);

        $city = (new \App\Models\City())
            ->fill([
                'title' => 'test',
                'region' => $region
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\City::class, $city);

        $address = (new \App\Models\Address())
            ->fill([
                'address' => 'test',
                'city' => $city
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Address::class, $address);

        $status = (new \App\Models\OfficeStatus())->fill(['title' => 'test'])->save();
        $this->assertInstanceOf(\App\Models\OfficeStatus::class, $status);

        $location = $office = (new \App\Models\Office())
            ->fill([
                'title' => 'test',
                'status' => $status,
                'address' => $address
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Office::class, $location);

        return $location;
    }

    public function testCreateVlan()
    {
        $vlan = (new \App\Models\Vlan())
            ->fill([
                'id' => 1,
                'name' => 'test',
                'comment' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Vlan::class, $vlan);
        return $vlan;
    }

    public function testCreateVrf()
    {
        $vrf = (new \App\Models\Vrf())
            ->fill([
                'name' => 'test',
                'rd' => '10:100',
                'comment' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Vrf::class, $vrf);
        return $vrf;
    }

    /**
     * create appliance
     * @depends testCreateLocation
     */
    public function testCreateAppliance($location)
    {
        $vendor = (new \App\Models\Vendor())->fill(['title' => 'test'])->save();
        $this->assertInstanceOf(\App\Models\Vendor::class, $vendor);

        $software = (new \App\Models\Software())->fill([
            'title' => 'test',
            'vendor' => $vendor
        ])->save();
        $this->assertInstanceOf(\App\Models\Software::class, $software);

        $softwareItem = (new \App\Models\SoftwareItem())
            ->fill([
                'version' => 'test',
                'software' => $software
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $softwareItem);

        $platform = (new \App\Models\Platform())
            ->fill([
                'title' => 'test',
                'vendor' => $vendor
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Platform::class, $platform);

        $platformItem = (new \App\Models\PlatformItem())
            ->fill([
                'version' => 'test',
                'platform' => $platform
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\PlatformItem::class, $platformItem);

        $applianceType = (new \App\Models\ApplianceType())
            ->fill([
                'type' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\ApplianceType::class, $applianceType);

        $appliance = (new \App\Models\Appliance())
            ->fill([
                'type' => $applianceType,
                'vendor' => $vendor,
                'platform' => $platformItem,
                'software' => $softwareItem,
                'location' => $location
            ])
            ->save();

        return $appliance;
    }

    public function testCreateDataPortType()
    {
        $dataPortType = (new \App\Models\DPortType())
            ->fill([
                'type' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\DPortType::class, $dataPortType);
        return $dataPortType;
    }


    /**
     * tests DataPort
     */

    public function providerValidDataPortIpAddress()
    {
        return [
            ['1.1.1.1/24'],
            ['1.1.1.1/32'],
        ];
    }

    /**
     * @dataProvider providerValidDataPortIpAddress
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testValidDataPortIpAddress($ipAddress, $appliance, $portType)
    {
        $newDataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ]);
        $this->assertInstanceOf(\App\Models\DataPort::class, $newDataPort);
        $this->assertEquals($ipAddress, $newDataPort->ipAddress);
        $this->assertInstanceOf(\App\Models\DPortType::class, $newDataPort->portType);
        $this->assertInstanceOf(\App\Models\Appliance::class, $newDataPort->appliance);
    }

    public function providerInvalidDataPortIpAddress()
    {
        return [
            ['2.1.1.0/24'],
            ['2.1.1.1'],
        ];
    }
    /**
     * @dataProvider providerInvalidDataPortIpAddress
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */

    public function testInvalidDataPortIpAddress($ipAddress, $appliance, $portType)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ]);
    }

    /**
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testInvalidDataPort($appliance, $portType)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => '3.1.1.1/24',
                'portType' => false,
                'appliance' => $appliance,
            ])
            ->save();
        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => '3.1.1.1/24',
                'portType' => $portType,
                'appliance' => false,
            ])
            ->save();
    }

    public function providerValidDataPort()
    {
        return [
            ['4.1.1.1/24'],
            ['4.1.1.2/32'],
        ];
    }

    /**
     * @dataProvider providerValidDataPort
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testValidDataPort($ipAddress, $appliance, $portType)
    {
        $newDataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ])
            ->save();
        $fromDb = \App\Models\DataPort::findByPK($newDataPort->getPk());
        $this->assertInstanceOf(\App\Models\DataPort::class, $fromDb);
        $this->assertEquals($ipAddress, $fromDb->ipAddress);
        $this->assertInstanceOf(\App\Models\DPortType::class, $fromDb->portType);
        $this->assertInstanceOf(\App\Models\Appliance::class, $fromDb->appliance);
    }

    public function providerDataPortNetwork()
    {
        return [
            ['5.1.1.1/24', '5.1.1.0/24'],
            ['5.1.2.1/32', '5.1.2.1/32'],
        ];
    }

    /**
     * @dataProvider providerDataPortNetwork
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testDataPortNetwork($ipAddress, $network, $appliance, $portType)
    {
        $newDataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ])
            ->save();
        $fromDb = \App\Models\DataPort::findByPK($newDataPort->getPk());
        $this->assertInstanceOf(\App\Models\DataPort::class, $fromDb);
        $this->assertEquals($ipAddress, $fromDb->ipAddress);
        $this->assertInstanceOf(\App\Models\Network::class, $fromDb->network);
        $this->assertEquals($network, $fromDb->network->address);
    }


}