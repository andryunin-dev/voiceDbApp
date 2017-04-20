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

    public function providerValidNetworkForSanitize()
    {
        return [
            ['1.1.1.0/24'],
            ['  1.1.1.0\24  ']
        ];
    }

    /**
     * @dataProvider providerValidNetworkForSanitize
     */
    public function testSanitizeNetworkAddress($address)
    {
        $net = (new \App\Models\Network())
            ->fill([
                'address' => $address
            ]);
        $this->assertInstanceOf(\App\Models\Network::class, $net);
        $this->assertEquals('1.1.1.0/24', $net->address);
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

    public function providerInvalidNetwork()
    {
        return [
            ['1.1.1.1/24'],
            [1234],
            ['1.1.1.1/33']
        ];
    }
    /**
     * @dataProvider providerInvalidNetwork
     * @depends testCreateVrf
     */
    public function testInvalidNetwork($address, $vrf)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\Network())
            ->fill([
                'address' => $address,
                'vrf' => $vrf
            ]);
    }


    public function providerValidNetwork()
    {
        return [
            ['1.1.1.0/24'],
            ['1.1.1.0/26'],
            ['1.1.1.64/26'],
            ['1.1.1.0/25'],
            ['1.1.1.128/25'],
            ['1.1.2.0/24']
        ];
    }

    /**
     * after test in DB we have:
     * 1.1.1.0/24
     *   1.1.1.0/25
     *     1.1.1.0/26
     *     1.1.1.64/26
     *   1.1.1.128/25
     * 1.1.2.0/24
     *
     * @dataProvider  providerValidNetwork
     * @depends testCreateLocation
     * @depends testCreateVrf
     * @depends testCreateVlan
     * @param string $network
     * @param \App\Models\Office $location
     * @param \App\Models\Vrf $vrf
     * @param \App\Models\Vlan $vlan
     */
    public function testAppendNetwork($network, $location, $vrf, $vlan)
    {
        $network = (new \App\Models\Network())
            ->fill([
                'address' => $network,
                'location' => $location,
                'vrf' => $vrf,
                'vlan' => $vlan
            ])
            ->save();
        $fromDb = \App\Models\Network::findByPK($network->getPk());
        $this->assertInstanceOf(\App\Models\Network::class, $fromDb);
        $this->assertInstanceOf(\App\Models\Vrf::class, $fromDb->vrf);
        $this->assertInstanceOf(\App\Models\Office::class, $fromDb->location);
        $this->assertEquals($vrf->name, $fromDb->vrf->name);
        $this->assertEquals($vrf->rd, $fromDb->vrf->rd);

        //Drop all networks
//        \App\Models\Network::findAll()->delete();
//        $this->assertEquals(0, \App\Models\Network::findAll()->count());
    }

}