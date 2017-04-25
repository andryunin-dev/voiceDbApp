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

}