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
    }

    public function providerGlobalVrfNetworks()
    {
        return [
            ['1.1.1.192/27'],
            ['1.1.1.192/26']
        ];
    }

    /**
     * create network '1.1.1.0/26' with Global Vrf and save()
     * It have to not have parent and children
     * @param $location
     * @param $vlan
     *
     * @dataProvider providerGlobalVrfNetworks
     * @depends testCreateLocation
     * @depends testCreateVlan
     * @depends testAppendNetwork
     */
    public function testAppendGlobalVrfNetworks($network, $location, $vlan)
    {
        $network = (new \App\Models\Network())
            ->fill([
                'address' => $network,
                'location' => $location,
                'vrf' => \App\Models\Vrf::findGlobalVrf(),
                'vlan' => $vlan
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Network::class, $network);
    }

    /**
     * for custom Vrf
     * @return array
     *
     */
    public function providerNetworksAndParents_1()
    {
        return [
            ['1.1.1.0/24', false, 2],
            ['1.1.1.0/26', '1.1.1.0/25', 0],
            ['1.1.1.64/26', '1.1.1.0/25', 0],
            ['1.1.1.0/25', '1.1.1.0/24', 2],
            ['1.1.1.128/25', '1.1.1.0/24', 0],
            ['1.1.2.0/24', false, 0]
        ];
    }

    /**
     *
     * @dataProvider providerNetworksAndParents_1
     * @depends testCreateVrf
     *
     * @depends testAppendNetwork
     * @depends testAppendGlobalVrfNetworks
     */
    public function testNetworkParentAndChildren($network, $parent, $childrenNumbers, $vrf)
    {
        $existedNet = \App\Models\Network::findByAddressVrf($network, $vrf);
        $this->assertEquals($parent, $existedNet->parent->address);
        $this->assertEquals($childrenNumbers, $existedNet->children->count());
    }

    /**
     * for Global Vrf
     * @return array
     */
    public function providerNetworksAndParents_2()
    {
        return [
            ['1.1.1.192/26', false, 1],
            ['1.1.1.192/27', '1.1.1.192/26', 0]
        ];
    }

    /**
     * for Global Vrf Networks
     *
     * @dataProvider providerNetworksAndParents_2
     * @depends testAppendNetwork
     * @depends testAppendGlobalVrfNetworks
     *
     */
    public function testNetworkParentAndChildren_2($network, $parent, $childrenNumbers)
    {
        $existedNet = \App\Models\Network::findByAddressVrf($network, \App\Models\Vrf::findGlobalVrf());
        $this->assertEquals($parent, $existedNet->parent->address);
        $this->assertEquals($childrenNumbers, $existedNet->children->count());
    }


}