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
        self::setDefaultDb(self::TEST_DB_NAME);
        foreach (self::$schemaList as $schema) {
            self::truncateTables($schema);
        }
    }

    public function testVlan()
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
}