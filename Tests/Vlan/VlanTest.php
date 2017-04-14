<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';

class VlanTest extends \PHPUnit\Framework\TestCase
{
    use DbTrait;

    const TEST_DB_NAME = 'phpUnitTest';

    protected static $schemaList = [
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
        \App\Models\Vlan::findAll()->delete();
    }

    public function providerValidVlanForSanitize()
    {
        return [
            [1, 'test'],
            ['1', 'test'],
            ['   1   ', 'test']
        ];
    }

    /**
     * @dataProvider providerValidVlanForSanitize
     */
    public function testSanitizeVlaId($vlanId, $name)
    {
        $vlan = (new \App\Models\Vlan())
            ->fill([
                'id' => $vlanId,
                'name' => $name,
                'comment' => 'test'
            ]);
        $this->assertInstanceOf(\App\Models\Vlan::class, $vlan);
        $this->assertInternalType('integer', $vlan->id);
        $this->assertEquals(1, $vlan->id);
    }

    public function providerValidVlan()
    {
        return [
            [1, 'test'],
            ['2', 'test'],
            [3, 'test'],
            ['  4  ', 'test'],
        ];
    }

    /**
     * @dataProvider providerValidVlan
     */
    public function testSaveVlan($vlanId, $name)
    {
        $vlan = (new \App\Models\Vlan())
            ->fill([
                'id' => $vlanId,
                'name' => $name,
                'comment' => 'test'
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Vlan::class, $vlan);
    }

    public function providerInvalidVlan()
    {
        return [
            'invalidVlanId_1' => [0, 'test'],
            'invalidVlanId_2' => ['0', 'test'],
            'invalidVlanId_3' => [4095, 'test'],
            'invalidVlanId_4' => ['4095', 'test'],
            'invalidVlanId_5' => [4096, 'test'],
            'invalidVlanId_6' => ['4096', 'test'],
            'invalidVlanId_7' => ['', 'test'],
            'invalidVlanName_1' => ['10', 123],
            'invalidVlanName_2' => ['10', 123],
            'dublingVlanId_1' => ['1', 'test'],
            'dublingVlanId_2' => [1, 'test'],
        ];
    }

    /**
     * @dataProvider providerInvalidVlan
     * @depends testSaveVlan
     */
    public function testValidateVlanError($vlanId, $name)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\Vlan())
            ->fill([
                'id' => $vlanId,
                'name' => $name,
                'comment' => 'test'
            ])
            ->save();
    }
}