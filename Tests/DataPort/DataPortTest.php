<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';
require_once __DIR__ . '/../EnvironmentTrait.php';

class DataPortTest extends \PHPUnit\Framework\TestCase
{
    use DbTrait;
    use EnvironmentTrait;

    /**
     * create environment
     */
    public function testCreateVlan()
    {
        return $this->createVlan();
    }

    public function testCreateVrf()
    {
        return $this->createVrf();
    }

    public function testCreateDataPortType()
    {
        return $this->createDataPortType();
    }

    public function testCreateAppliance()
    {
        return $this->createAppliance();
    }
    //*********end create environment*************

    /**
     * tests DataPort
     */

    public function providerValid_IpMacVrf()
    {
        return [
            ['1.1.1.1/24', '1.1.1.0/24', '00-11-22-33-44-55', \App\Models\Vrf::GLOBAL_VRF_NAME, \App\Models\Vrf::GLOBAL_VRF_RD],
            ['1.1.2.1/25', '1.1.2.0/25', '00-11-22-33-44-55', \App\Models\Vrf::GLOBAL_VRF_NAME, \App\Models\Vrf::GLOBAL_VRF_RD],
            ['1.1.2.2/25', '1.1.2.0/25', '00-11-22-33-44-55', \App\Models\Vrf::GLOBAL_VRF_NAME, \App\Models\Vrf::GLOBAL_VRF_RD],
            ['1.1.3.4/32', '1.1.3.4/32', '00-11-22-33-44-55', \App\Models\Vrf::GLOBAL_VRF_NAME, \App\Models\Vrf::GLOBAL_VRF_RD],
            ['1.1.1.1/24', '1.1.1.0/24', '00-11-22-33-44-55', 'test', '10:10'],
            ['1.1.2.1/25', '1.1.2.0/25', '00-11-22-33-44-55', 'test', '10:10'],
            ['1.1.2.2/25', '1.1.2.0/25', '00-11-22-33-44-55', 'test', '10:10'],
            ['1.1.3.4/32', '1.1.3.4/32', '00-11-22-33-44-55', 'test', '10:10'],
        ];
    }

    /**
     * @param string $ipAddress
     * @param string $network
     * @param string $macAddress
     * @param string $vrfName
     * @param string $vrfRd
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     *
     * @dataProvider providerValid_IpMacVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testValidDataPort($ipAddress, $network, $macAddress, $vrfName, $vrfRd, $appliance, $portType)
    {
        if ($vrfName == \App\Models\Vrf::GLOBAL_VRF_NAME) {
            $vrf = \App\Models\Vrf::instanceGlobalVrf();
        } else {
            $vrf = (\App\Models\Vrf::findByRd($vrfRd)) ?: (new \App\Models\Vrf(['name' => $vrfName, 'rd' => $vrfRd]))->save();
        }

        $newDataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => $vrf,
                'macAddress' => $macAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\DataPort::class, $newDataPort);
        $this->assertEquals($ipAddress, $newDataPort->ipAddress);
        $this->assertEquals($network, $newDataPort->network->address);
        $this->assertInstanceOf(\App\Models\DPortType::class, $newDataPort->portType);
        $this->assertInstanceOf(\App\Models\Appliance::class, $newDataPort->appliance);
    }

    /**
     * @param string $ipAddress
     * @param string $macAddress
     * @param string $vrfName
     * @param string $vrfRd
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     *
     * @dataProvider providerValid_IpMacVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     *
     * @depends testValidDataPort
     */
    public function testDoubleDataPortError($ipAddress, $macAddress, $vrfName, $vrfRd, $appliance, $portType)
    {
        $this->expectException(\T4\Core\Exception::class);

        if ($vrfName == \App\Models\Vrf::GLOBAL_VRF_NAME) {
            $vrf = \App\Models\Vrf::instanceGlobalVrf();
        } else {
            $vrf = (\App\Models\Vrf::findByRd($vrfRd)) ?: (new \App\Models\Vrf(['name' => $vrfName, 'rd' => $vrfRd]))->save();
        }

        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => $vrf,
                'macAddress' => $macAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ])
            ->save();
    }

    public function provider_Invalid_IpMacVrf()
    {
        return [
            'invalidIp_1' => ['2.1.1.0/24', '00-11-22-33-44-55', \App\Models\Vrf::GLOBAL_VRF_NAME],
            'invalidIp_2' => ['2.1.1.1/33', '00-11-22-33-44-55', \App\Models\Vrf::GLOBAL_VRF_NAME],
            'invalidIp_3' => ['2.1.1.1', '00-11-22-33-44-55', \App\Models\Vrf::GLOBAL_VRF_NAME],
            'invalidMac' => ['2.1.1.1/24', '00-11-22-33', \App\Models\Vrf::GLOBAL_VRF_NAME],
            'invalidVrfRd_1' => ['2.1.1.2/25', '00-11-22-33-44-55', 'test'],
            'invalidVrfRd_2' => ['2.1.1.2/25', '00-11-22-33-44-55', 'test'],
            'invalidVrfRd_3' => ['2.1.1.2/25', '00-11-22-33-44-55', 'test'],
            'invalidVrfRd_4' => ['2.1.1.2/25', '00-11-22-33-44-55', 'test'],
            'invalidVrfRd_5' => ['2.1.1.2/25', '00-11-22-33-44-55', 'test'],
            'invalidVrfName_1' => ['2.1.1.2/25', '00-11-22-33-44-55', false],
            'invalidVrfName_2' => ['2.1.1.2/25', '00-11-22-33-44-55', null],
            'invalidVrfName_3' => ['2.1.1.2/25', '00-11-22-33-44-55', ''],
        ];
    }

    /**
     * @param string $ipAddress
     * @param string $macAddress
     * @param string $vrf
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     *
     * @dataProvider provider_Invalid_IpMacVrf
     * @depends testCreateVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     *
     * @depends testValidDataPort
     */
    public function testInvalidDataPort($ipAddress, $macAddress, $vrf, $appliance, $portType)
    {
        $this->expectException(\T4\Core\Exception::class);

        if (is_string($vrf) && !empty($vrf)) {
            $vrf = \App\Models\Vrf::findByName($vrf);
        }

        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => $vrf,
                'macAddress' => $macAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ])
            ->save();
    }

    /**
     * @param string $ipAddress
     * @param string $network
     * @param string $macAddress
     * @param string $vrfName
     * @param string $vrfRd
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     *
     * @dataProvider providerValid_IpMacVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     * @depends testValidDataPort
     */
    public function testDeleteDataPort($ipAddress, $network, $macAddress, $vrfName, $vrfRd, $appliance, $portType)
    {
        $vrf = \App\Models\Vrf::findByRd($vrfRd);
        $this->assertInstanceOf(\App\Models\Vrf::class, $vrf);

        $ports = \App\Models\DataPort::findAllByIpVrf($ipAddress,$vrf);
        $this->assertEquals(1, $ports->count());

        /**
         * @var \App\Models\DataPort $port
         */
        $port = $ports->first();
        $this->assertInstanceOf(\App\Models\DataPort::class, $port);

        $port->delete();
        if (32 == (new \App\Components\Ip($port->network->address))->masklen) {
            $this->assertFalse(\App\Models\Network::findByAddressVrf($network, $vrf));
        } else {
            $networkFromDb = \App\Models\Network::findByAddressVrf($network, $vrf);
            $this->assertEquals($network, $networkFromDb->address);
        }
    }

    public function providerChangeDataPort()
    {
        return [
            'change Ip on same Net' => [['1.1.1.1/24' => '1.1.1.3/24','1.1.1.2/24' => '1.1.1.4/24'], ['1.1.1.0/24'], 'gvrf', ['1.1.1.0/24'], 'gvrf'],
            'changeIp to another not existed Net (not child)' =>
                [['1.1.1.1/24' => '1.1.2.1/24'], ['1.1.1.0/24'], 'gvrf', ['1.1.1.0/24', '1.1.2.0/24'], 'gvrf'],
            'changeIp to another not existed Net (not child loopback)' =>
                [['1.1.1.1/24' => '1.1.2.1/32'], ['1.1.1.0/24'], 'gvrf', ['1.1.1.0/24', '1.1.2.1/32'], 'gvrf'],
            'changeIp to another not existed Net (child loopback)' =>
                [['1.1.1.1/24' => '1.1.1.1/32'], ['1.1.1.0/24'], 'gvrf', ['1.1.1.0/24', '1.1.1.1/32'], 'gvrf'],
            'changeIp to another not existed Net (child net)' =>
                [['1.1.1.1/24' => '1.1.1.1/25'], ['1.1.1.0/24'], 'gvrf', ['1.1.1.0/24', '1.1.1.0/25'], 'gvrf'],
            'changeIp to another existed Net (not child)' =>
                [['1.1.1.1/24' => '1.1.2.1/24', '1.1.1.2/24' => '1.1.2.2/24'], ['1.1.1.0/24'], 'gvrf', ['1.1.1.0/24', '1.1.2.0/24'], 'gvrf'],
            'changeIp to another existed Net (child net)' =>
                [['1.1.1.1/24' => '1.1.1.1/25', '1.1.2.1/24' => '1.1.1.2/25'], ['1.1.1.0/24', '1.1.2.0/24'], 'gvrf', ['1.1.1.0/24', '1.1.2.0/24', '1.1.1.0/25'], 'gvrf'],
            'changeIp to another not existed Net from loopback(not parent)' =>
                [['1.1.1.1/32' => '1.1.2.1/24'], ['1.1.1.1/32'], 'gvrf', ['1.1.2.0/24'], 'gvrf'],
            'changeIp to parent Net from loopback' =>
                [['1.1.1.1/32' => '1.1.1.1/24'], ['1.1.1.1/32'], 'gvrf', ['1.1.1.0/24'], 'gvrf'],
            'change vrf' => [['1.1.1.1/24' => '1.1.1.1/24','1.1.1.2/24' => '1.1.1.2/24'], ['1.1.1.0/24'], 'gvrf', ['1.1.1.0/24', '1.1.1.0/24'], 'test'],

        ];
    }

    /**
     * @param array $ipSet
     * @param array $beforeNetSet
     * @param string $beforeVrfName
     * @param array $afterNetSet
     * @param string $afterVrfName
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     * @param \App\Models\Vrf $vrf
     *
     * @dataProvider providerChangeDataPort
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     * @depends testCreateVrf
     */
    public function testChangeDataPort(
        $ipSet,
        $beforeNetSet,
        $beforeVrfName,
        $afterNetSet,
        $afterVrfName,
        $appliance,
        $portType,
        $vrf
    ) {
        \App\Models\DataPort::findAll()->delete();
        \App\Models\Network::findAll()->delete();
        $beforeVrf = ('gvrf' == $beforeVrfName) ? \App\Models\Vrf::instanceGlobalVrf() : $vrf;
        $afterVrf = ('gvrf' == $afterVrfName) ? \App\Models\Vrf::instanceGlobalVrf() : $vrf;

        foreach ($ipSet as $beforeIp => $afterIp) {
            $port = (new \App\Models\DataPort())
                ->fill([
                    'ipAddress' => $beforeIp,
                    'vrf' => $beforeVrf,
                    'portType' => $portType,
                    'appliance' => $appliance,
                ])
                ->save();
            $this->assertInstanceOf(\App\Models\DataPort::class, $port);
        }
        $this->assertEquals(count($beforeNetSet), \App\Models\Network::findAll()->count());
        foreach ($beforeNetSet as $net) {
            $this->assertInstanceOf(\App\Models\Network::class, \App\Models\Network::findByAddressVrf($net, $beforeVrf));
        }
        foreach ($ipSet as $beforeIp => $afterIp) {
            $port = \App\Models\DataPort::findByIpVrf($beforeIp, $beforeVrf);
            $this->assertInstanceOf(\App\Models\DataPort::class, $port);
            $port->fill([
                    'ipAddress' => $afterIp,
                    'vrf' => $afterVrf,
                ])
                ->save();
            $this->assertInstanceOf(\App\Models\DataPort::class, $port);
        }
        $this->assertEquals(count($afterNetSet), \App\Models\Network::findAll()->count());
        foreach ($afterNetSet as $net) {
            $this->assertInstanceOf(\App\Models\Network::class, \App\Models\Network::findByAddressVrf($net, $afterVrf));
        }
    }

    public function providerChangeDataPortError()
    {
        return [
            'double IP' => [['1.1.1.1/24' => '1.1.1.3/24','1.1.1.2/24' => '1.1.1.3/24'], ['1.1.1.0/24'], 'gvrf', 'gvrf'],
            'host IP in parent net' => [['1.1.1.1/24' => '1.1.1.1/25','1.1.1.2/24' => '1.1.1.2/24'], ['1.1.1.0/24'], 'gvrf', 'gvrf'],
            'from child net to parent net' => [['1.1.1.1/25' => '1.1.1.1/24'], ['1.1.1.0/25'], 'gvrf', 'gvrf'],
            'change from loopbacks to parent' => [['1.1.1.1/32' => '1.1.1.1/24','1.1.1.2/32' => '1.1.1.2/24'], ['1.1.1.1/32', '1.1.1.2/32'], 'gvrf', 'gvrf'],
            'change vrf, double IP' => [['1.1.1.1/24' => '1.1.1.1/24','1.1.1.2/24' => '1.1.1.1/24'], ['1.1.1.0/24'], 'gvrf', 'test'],
        ];
    }

    /**
     * @param array $ipSet
     * @param array $beforeNetSet
     * @param string $beforeVrfName
     * @param string $afterVrfName
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     * @param \App\Models\Vrf $vrf
     *
     * @dataProvider providerChangeDataPortError
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     * @depends testCreateVrf
     */
    public function testChangeDataPortError(
        $ipSet,
        $beforeNetSet,
        $beforeVrfName,
        $afterVrfName,
        $appliance,
        $portType,
        $vrf
    ) {
        \App\Models\DataPort::findAll()->delete();
        \App\Models\Network::findAll()->delete();
        $beforeVrf = ('gvrf' == $beforeVrfName) ? \App\Models\Vrf::instanceGlobalVrf() : $vrf;
        $afterVrf = ('gvrf' == $afterVrfName) ? \App\Models\Vrf::instanceGlobalVrf() : $vrf;

        foreach ($ipSet as $beforeIp => $afterIp) {
            $port = (new \App\Models\DataPort())
                ->fill([
                    'ipAddress' => $beforeIp,
                    'vrf' => $beforeVrf,
                    'portType' => $portType,
                    'appliance' => $appliance,
                ])
                ->save();
            $this->assertInstanceOf(\App\Models\DataPort::class, $port);
        }
        $this->assertEquals(count($beforeNetSet), \App\Models\Network::findAll()->count());
        foreach ($beforeNetSet as $net) {
            $this->assertInstanceOf(\App\Models\Network::class, \App\Models\Network::findByAddressVrf($net, $beforeVrf));
        }

        $this->expectException(\T4\Core\Exception::class);
        foreach ($ipSet as $beforeIp => $afterIp) {
            $port = \App\Models\DataPort::findByIpVrf($beforeIp, $beforeVrf);
            $this->assertInstanceOf(\App\Models\DataPort::class, $port);
            $port->fill([
                    'ipAddress' => $afterIp,
                    'vrf' => $afterVrf,
                ])
                ->save();
        }
    }

    public function providerCreateDataPortError()
    {
        return [
            'existed net with subnet' => [['1.1.1.0/24', '1.1.1.0/25'], ['1.1.1.1/24'], 'gvrf'],
        ];
    }

    /**
     * @param array $beforeNetSet
     * @param array $ipSet
     * @param string $vrfName
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     * @param \App\Models\Vrf $vrf
     *
     * @dataProvider providerCreateDataPortError
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     * @depends testCreateVrf
     */
    public function testCreateDataPortError(
        $beforeNetSet,
        $ipSet,
        $vrfName,
        $appliance,
        $portType,
        $vrf
    ) {
        \App\Models\DataPort::findAll()->delete();
        \App\Models\Network::findAll()->delete();
        $vrf = ('gvrf' == $vrfName) ? \App\Models\Vrf::instanceGlobalVrf() : $vrf;

        foreach ($beforeNetSet as $net) {
            $net = (new \App\Models\Network())
                ->fill([
                    'address' => $net,
                    'vrf' => $vrf
                ])
                ->save();
            $this->assertInstanceOf(\App\Models\Network::class, $net);
        }

        $this->expectException(\T4\Core\Exception::class);
        foreach ($ipSet as $ip) {
            (new \App\Models\DataPort())
                ->fill([
                    'ipAddress' => $ip,
                    'vrf' => $vrf,
                    'portType' => $portType,
                    'appliance' => $appliance,
                ])
                ->save();
        }
    }
}