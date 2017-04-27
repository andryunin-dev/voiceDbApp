<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';
require_once __DIR__ . '/../EnvironmentTrait.php';

class NetworkTest extends \PHPUnit\Framework\TestCase
{
    use DbTrait;
    use EnvironmentTrait;


    /**
     * create environment
     */
    public function testCreateLocation()
    {
        return $this->createLocation();
    }

    public function testCreateVlan()
    {
        return $this->createVlan();
    }

    public function testCreateVrf()
    {
        return $this->createVrf();
    }
    //*********end create environment*************


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
                'vrf' => \App\Models\Vrf::instanceGlobalVrf(),
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
        $existedNet = \App\Models\Network::findByAddressVrf($network, \App\Models\Vrf::instanceGlobalVrf());
        $this->assertEquals($parent, $existedNet->parent->address);
        $this->assertEquals($childrenNumbers, $existedNet->children->count());
    }


}