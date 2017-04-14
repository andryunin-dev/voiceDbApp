<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';
require_once __DIR__ . '/../DbTrait.php';

class VrfTest extends \PHPUnit\Framework\TestCase
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

        \App\Models\Vrf::findAll()->delete();
    }
    public static function tearDownAfterClass()
    {
        self::setDefaultDb(self::TEST_DB_NAME);
//        foreach (self::$schemaList as $schema) {
//            self::truncateTables($schema);
//        }
    }


    public function providerValidVrf()
    {
        return [
            ['test', '1:1', 'test'],
            ['test', '192.168.1.1:1', 'test'],
            ['123', '192.168.1.1:1', 'test'],
        ];
    }

    /**
     * @dataProvider providerValidVrf
     */
    public function testValidVrf($name, $rd, $comment)
    {
        $vrf = (new \App\Models\Vrf())
            ->fill([
                'name' => $name,
                'rd' => $rd,
                'comment' => $comment
            ]);
        $this->assertInstanceOf(\App\Models\Vrf::class, $vrf);
        $this->assertEquals($name, $vrf->name);
        $this->assertEquals($rd, $vrf->rd);
        $this->assertEquals($comment, $vrf->comment);
    }

    public function providerInvalidVrf()
    {
        return [
            [true, '1:1', 'test'],
            [123, '1:1', 'test'],
            ['test', ':', 'test'],
            ['test', 1, 'test'],
            ['test', 'wrongRd', 'test'],
            ['test', '192.168.1.1', 'test'],
            ['test', '192.168.1.1:', 'test'],
            ['test', '1', 'test'],
            ['test', ':1', 'test']
        ];
    }

    /**
     * @dataProvider providerInvalidVrf
     */
    public function testInvalidVrf($name, $rd, $comment)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\Vrf())
            ->fill([
                'name' => $name,
                'rd' => $rd,
                'comment' => $comment
            ]);
    }

    public function providerVrfSave()
    {
        return [
            ['test', '1:1', 'test'],
            ['test1', '2:1', 'test'],
            ['test2', '1.1.1.1:1', 'test'],
            ['test3', '1.1.1.1:2', 'test'],
            ['test4', '1.1.1.2:1', 'test'],
            ['test5', '1.1.1.2:2', 'test'],
        ];
    }
    /**
     * @dataProvider providerVrfSave
     */
    public function testVrfSave($name, $rd, $comment)
    {
        $vrf = (new \App\Models\Vrf())
            ->fill([
                'name' => $name,
                'rd' => $rd,
                'comment' => $comment
            ])
            ->save();
        $this->assertInstanceOf(\App\Models\Vrf::class, $vrf);
    }



    public function providerVrfSaveError()
    {
        return [
            'dubleName_1' =>['test', '100:1', 'test'],
            'dubleName_2' =>['Test', '100:2', 'test'],
            'dubleName_3' =>['  test', '100:3', 'test'],
            'dubleRd_1' =>['test100', '1:1', 'test'],
            'dubleRd_2' =>['test101', '  2:1', 'test'],
            'dubleRd_3' =>['test102', '1.1.1.1:1', 'test'],
            'dubleRd_4' =>['test103', '  1.1.1.1:1', 'test'],
        ];
    }
    /**
     * @dataProvider providerVrfSaveError
     * @depends testVrfSave
     */
    public function testVrfSaveError($name, $rd, $comment)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\Vrf())
            ->fill([
                'name' => $name,
                'rd' => $rd,
                'comment' => $comment
            ])
            ->save();

    }

    public function testGlobalVrf()
    {
        $globalVrf = \App\Models\Vrf::findGlobalVrf();
        $this->assertInstanceOf(\App\Models\Vrf::class, $globalVrf);
        $this->assertEquals(\App\Models\Vrf::GLOBAL_VRF_NAME, $globalVrf->name);
        $this->assertEquals(\App\Models\Vrf::GLOBAL_VRF_RD, $globalVrf->rd);
        $this->assertEquals(\App\Models\Vrf::GLOBAL_VRF_COMMENT, $globalVrf->comment);
        $globalVrf_2 = \App\Models\Vrf::findGlobalVrf();
        $this->assertEquals($globalVrf->getPk(), $globalVrf_2->getPk());
    }
}