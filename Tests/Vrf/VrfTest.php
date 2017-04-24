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

    }
    public static function tearDownAfterClass()
    {
        \App\Models\Vrf::findAll()->delete();
    }


    public function testSanitizeVrf()
    {
        $vrf = (new \App\Models\Vrf())
            ->fill([
                'name' => '   test   ',
                'rd' => '  1 : 1  '
            ]);
        $this->assertEquals('test', $vrf->name);
        $this->assertEquals('1:1', $vrf->rd);

        $vrf = (new \App\Models\Vrf())
            ->fill([
                'name' => '   test   ',
                'rd' => '  1.2.3.4 : 1  '
            ]);
        $this->assertEquals('test', $vrf->name);
        $this->assertEquals('1.2.3.4:1', $vrf->rd);
    }

    public function providerValidVrf()
    {
        return [
            ['test', '1:1', 'test'],
            ['test', '2:1', 'test'],
            ['test', '1.1.1.1:1', 'test'],
            ['test', '1.1.1.1:2', 'test'],
            ['test', '1.1.1.2:1', 'test'],
            ['test', '1.1.1.2:2', 'test'],
        ];
    }

    /**
     * @dataProvider providerValidVrf
     */
    public function testValidateVrf($name, $rd, $comment)
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
    public function testValidateVrfError($name, $rd, $comment)
    {
        $this->expectException(\T4\Core\Exception::class);
        (new \App\Models\Vrf())
            ->fill([
                'name' => $name,
                'rd' => $rd,
                'comment' => $comment
            ]);
    }

    /**
     * @dataProvider providerValidVrf
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
            'dubleRd_1' =>['test', '1:1', 'test'],
            'dubleRd_2' =>['test', '  2:1', 'test'],
            'dubleRd_3' =>['test', '1.1.1.1:1', 'test'],
            'dubleRd_4' =>['test', '  1.1.1.1:1', 'test'],
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