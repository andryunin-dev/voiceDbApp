<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';

class IpTest extends \PHPUnit\Framework\TestCase
{
    public function validateTestProvider()
    {
        return [
            ['1.1.1.1/24', true],
            ['1.1.1.1/32', true],
            ['1.1.1.0/24', true],
            ['256.0.0.0/24', false],
            ['1.1.1.1/33', false],
            ['256.0.0.0/24', false],
            ['test', false],
        ];
    }

    /**
     * @dataProvider validateTestProvider
     */
    public function testValidate($ip, $expected)
    {
        $ip = new \App\Components\Ip($ip);
        $this->assertEquals($expected, $ip->is_valid);
    }

    public function testSanitize()
    {
        $this->assertEquals('1.1.1.1/24', (new \App\Components\Ip('   1.1.1.1/24   '))->cidrAddress);
    }

    public function addressTestProvider()
    {
        return [
            ['1.1.1.1/24', '1.1.1.1', '1.1.1.1/24', true, '255.255.255.0', 24],
            ['2.2.2.2/2', '2.2.2.2', '2.2.2.2/2', true, '192.0.0.0', 2],
            ['1.1.1.0/24', '1.1.1.0', '1.1.1.0/24', false, '255.255.255.0', 24],
            ['0.0.0.0/1', '0.0.0.0', '0.0.0.0/1', false, '128.0.0.0', 1],
        ];
    }
    /**
     * @dataProvider addressTestProvider
     */
    public function testAddress($ip, $address, $cidrAddress, $is_hostIp, $mask, $masklen)
    {
        $ip = new \App\Components\Ip($ip);
        $this->assertEquals($address, $ip->address);
        $this->assertEquals($cidrAddress, $ip->cidrAddress);
        $this->assertEquals($is_hostIp, $ip->is_hostIp);
        $this->assertEquals($mask, $ip->mask);
        $this->assertEquals($masklen, $ip->masklen);
    }

    public function networkTestProvider()
    {
        return [
            ['1.1.1.1/24', '1.1.1.0', '1.1.1.255', 256, '1.1.1.0/24', true],
            ['1.1.1.0/24', '1.1.1.0', '1.1.1.255', 256, '1.1.1.0/24', false],
            ['2.2.2.0/30', '2.2.2.0', '2.2.2.3', 4, '2.2.2.0/30', false],
            ['2.2.2.2/30', '2.2.2.0', '2.2.2.3', 4, '2.2.2.0/30', true],
            ['2.2.2.5/30', '2.2.2.4', '2.2.2.7', 4, '2.2.2.4/30', true],
            ['2.2.2.2/31', '2.2.2.2', '2.2.2.3', 2, '2.2.2.2/31', false],
            ['2.2.2.2/32', '2.2.2.2', '2.2.2.2', 1, '2.2.2.2/32', false]
        ];
    }
    /**
     * @dataProvider networkTestProvider
     */
    public function testNetwork($ip, $network, $broadcast,$networkSize, $cidrNetwork, $is_hostIp)
    {
        $ip = new \App\Components\Ip($ip);
        $this->assertEquals($network, $ip->network);
        $this->assertEquals($broadcast, $ip->broadcast);
        $this->assertEquals($networkSize, $ip->networkSize);
        $this->assertEquals($cidrNetwork, $ip->cidrNetwork);
        $this->assertEquals($is_hostIp, $ip->is_hostIp);
    }
}