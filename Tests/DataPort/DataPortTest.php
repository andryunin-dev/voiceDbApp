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

    public function provider_Valid_IpMac_Address()
    {
        return [
            ['1.1.1.1/24', '00-11-22-33-44-55'],
            ['1.1.1.1/32', '00-11-22-33-44-55'],
        ];
    }

    public function provider_Invalid_IpMac_Address()
    {
        return [
            ['2.1.1.0/24', '00-11-22-33-44-55'],
            ['2.1.1.1', '00-11-22-33-44-55'],
            ['2.1.1.1/24', '00-11-22-33'],
        ];
    }

    /**
     * test validator of DataPort
     *
     * @param string $ipAddress
     * @param string $macAddress
     * @param \App\Models\Vrf $vrf
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     *
     * @dataProvider provider_Valid_IpMac_Address
     * @depends testCreateVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testValidIpMacAddress($ipAddress, $macAddress, $vrf, $appliance, $portType)
    {
        $newDataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => $vrf,
                'macAddress' => $macAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ]);
        $this->assertInstanceOf(\App\Models\DataPort::class, $newDataPort);
        $this->assertEquals($ipAddress, $newDataPort->ipAddress);
        $this->assertInstanceOf(\App\Models\DPortType::class, $newDataPort->portType);
        $this->assertInstanceOf(\App\Models\Appliance::class, $newDataPort->appliance);
    }

    /**
     * @param string $ipAddress
     * @param string $macAddress
     * @param \App\Models\Vrf $vrf
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     *
     * @dataProvider provider_Invalid_IpMac_Address
     * @depends testCreateVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType

     */
    public function testInvalidDataPortIpAddress($ipAddress, $macAddress, $vrf, $appliance, $portType)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => $vrf,
                'macAddress' => $macAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ]);
    }

    /**
     * @param string $ipAddress
     * @param \App\Models\Vrf $vrf
     * @param \App\Models\Appliance $appliance
     * @param \App\Models\DPortType $portType
     *
     * @dataProvider provider_Valid_IpMac_Address
     * @depends testCreateVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testInvalidDataPortRelations($ipAddress, $vrf, $appliance, $portType)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => false,
                'portType' => $portType,
                'appliance' => $appliance,
            ])
            ->save();
        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => '3.1.1.1/24',
                'vrf' => $vrf,
                'portType' => false,
                'appliance' => $appliance,
            ])
            ->save();
        (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => '3.1.1.1/24',
                'vrf' => $vrf,
                'portType' => $portType,
                'appliance' => false,
            ])
            ->save();
    }

    /**
     * test save valid DataPort
     *
     * @dataProvider provider_Valid_IpMac_Address
     * @depends testCreateVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testValidDataPortSave($ipAddress, $macAddress, $vrf, $appliance, $portType)
    {
        $dataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => $vrf,
                'macAddress' => $macAddress,
                'portType' => $portType,
                'appliance' => $appliance,
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\DataPort::class, $dataPort);
        $this->assertEquals($ipAddress, $dataPort->ipAddress);
        $this->assertInstanceOf(\App\Models\DPortType::class, $dataPort->portType);
        $this->assertInstanceOf(\App\Models\Appliance::class, $dataPort->appliance);
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
     * @depends testCreateVrf
     * @depends testCreateAppliance
     * @depends testCreateDataPortType
     */
    public function testDataPortNetwork($ipAddress, $network, $vrf, $appliance, $portType)
    {
        $newDataPort = (new \App\Models\DataPort())
            ->fill([
                'ipAddress' => $ipAddress,
                'vrf' => $vrf,
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