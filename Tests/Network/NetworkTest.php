<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';

class NetworkTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @depends testCreateLocation
     */
    public function testCreateAppliance($location)
    {
        $vendor = (new \App\Models\Vendor())->fill(['title' => 'test'])->save();
        $this->assertInstanceOf(\App\Models\Vendor::class, $vendor);

        $applianceType = (new \App\Models\ApplianceType())
            ->fill([
                'type' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\ApplianceType::class, $applianceType);

        $software = (new \App\Models\Software())
            ->fill([
                'title' => 'test',
                'vendor' => $vendor
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Software::class, $software);

        $swItem = (new \App\Models\SoftwareItem())
            ->fill([
                'version' => 'test',
                'software' => $software
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $swItem);

        $platform = (new \App\Models\Platform())
            ->fill([
                'title' => 'test',
                'vendor' => $vendor
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Platform::class, $platform);

        $platformItem = (new \App\Models\PlatformItem())
            ->fill([
                'platform' => $platform
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\PlatformItem::class, $platformItem);

        $appliance = (new \App\Models\Appliance())
            ->fill([
                'comment' => 'test',
                'type' => $applianceType,
                'vendor' => $vendor,
                'platform' => $platformItem,
                'software' => $swItem,
                'location' => $location
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Appliance::class, $appliance);
        $this->assertInstanceOf(\App\Models\ApplianceType::class, $appliance->type);
        $this->assertInstanceOf(\App\Models\Vendor::class, $appliance->vendor);
        $this->assertInstanceOf(\App\Models\PlatformItem::class, $appliance->platform);
        $this->assertInstanceOf(\App\Models\SoftwareItem::class, $appliance->software);
        $this->assertInstanceOf(\App\Models\Office::class, $appliance->location);
        return $appliance;
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
     * @depends testCreateLocation
     */
    public function testNetwork($location)
    {
        $network = (new \App\Models\Network())
            ->fill([
                'address' => '10.1.1.0/24',
                'location' => $location
            ])
            ->save();
        $this->assertEquals(1, \App\Models\Network::findAll()->count());
        $fromDb = \App\Models\Network::findByPK($network->getPk());
        $this->assertInstanceOf(\App\Models\Network::class, $fromDb);
        $this->assertInstanceOf(\App\Models\Vrf::class, $fromDb->vrf);
        $this->assertInstanceOf(\App\Models\Office::class, $fromDb->location);
        $this->assertEquals(\App\Models\Vrf::GLOBAL_VRF_NAME, $fromDb->vrf->name);
        $this->assertEquals(\App\Models\Vrf::GLOBAL_VRF_RD, $fromDb->vrf->rd);

        //Drop all networks
        \App\Models\Network::findAll()->delete();
        $this->assertEquals(0, \App\Models\Network::findAll()->count());
    }

}