<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

use App\Components\Tables\PivotTableConfig;
use UnitTest\UnitTestClasses\ModelClass_1;
use \App\Components\Sql\SqlFilter;
use T4\Core\Std;
use T4\Core\Config;

class PivotTableConfigTest extends \PHPUnit\Framework\TestCase
{
    public function providerSetPivotColumn()
    {
        return [
            '_1' => ['columnOne', null, 'columnOne', 'columnOne'],
            '_2' => ['columnOne', 'alias', 'alias', 'columnOne'],
        ];
    }

    /**
     * @dataProvider providerSetPivotColumn
     * @param $column
     * @param $alias
     * @param $expectedAlias
     * @param $expectedValue
     * @return PivotTableConfig
     * @internal param $expected
     */
    public function testSetPivotColumn($column, $alias, $expectedAlias, $expectedValue)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());

        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);

        $res = $conf->setPivotColumn($column, $alias);
        $this->assertInstanceOf(Config::class, $res);
        $this->assertEquals($res->$expectedAlias->column, $expectedValue);
        return $conf;
    }

    /**
     * using for next tests as base config object
     */
    public function testCreateBasePivotConfig()
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);
        $conf->setPivotColumn('columnTwo');
        $this->assertInstanceOf(PivotTableConfig::class, $conf);
        $this->assertEquals('columnTwo', $conf->pivots->columnTwo->column);
        return $conf;
    }

    /**
     * @depends testCreateBasePivotConfig
     * @param PivotTableConfig $conf
     * test setting preFilter for pivot column
     */
    public function testPivotPreFilter($conf)
    {
        $this->assertEquals([], $conf->pivotPreFilter('columnTwo')->toArray());
        $preFilter = new SqlFilter(ModelClass_1::class);
        $preFilter->addFilter('columnOne', 'eq', ['test']);
        $res = $conf->pivotPreFilter('columnTwo',$preFilter);
        $expected = $preFilter->toArray();
        $this->assertEquals($expected, $res->toArray());
        //test getter
        $this->assertEquals($expected, $conf->pivotPreFilter('columnTwo')->toArray());
    }

    /**
     * @depends testCreateBasePivotConfig
     * @param PivotTableConfig $conf
     */
    public function testPivotSortBy($conf)
    {
        $this->assertEquals([], $conf->pivotSortBy('columnTwo')->toArray());
        $sortBy = ['columnOne', 'columnTwo'];
        $expected = array_fill_keys($sortBy, '');
        $res = $conf->pivotSortBy('columnTwo', $sortBy);
        $this->assertEquals($expected, $res->toArray());
        $expected = array_fill_keys($sortBy, 'asc');
        $this->assertEquals($expected, $conf->pivotSortBy('columnTwo', $sortBy, 'asc')->toArray());
    }

    public function providerPivotSortBy_exceptions()
    {
        return [
            'not pivot column/alias' => ['columnOne', ['columnOne', 'columnTwo']],
            'not defined sort column' => ['columnTwo', ['columnOne', 'undefinedColumn']],
        ];
    }

    /**
     * @depends      testCreateBasePivotConfig
     * @dataProvider providerPivotSortBy_exceptions
     * @expectedException \T4\Core\Exception
     *
     * @param $pivotAlias
     * @param $sortBy
     * @param PivotTableConfig $conf
     */
    public function testPivotSortBy_Exceptions($pivotAlias, $sortBy, $conf)
    {
         $conf->pivotSortBy($pivotAlias, $sortBy);
    }

    /**
     * @depends testCreateBasePivotConfig
     * @param PivotTableConfig $conf
     */
    public function testWidthPivotItems($conf)
    {
        $this->assertEquals(10, $conf->widthPivotItems('columnTwo', 10));
        $this->assertEquals('10px', $conf->widthPivotItems('columnTwo', ' 10PX '));
    }
    /**
     * @depends testCreateBasePivotConfig
     * @param PivotTableConfig $conf
     * @expectedException \T4\Core\Exception
     */
    public function testWidthPivotItems_NotValidType($conf)
    {
        $conf->widthPivotItems('columnTwo', [10]);
    }
    /**
     * @depends testCreateBasePivotConfig
     * @param PivotTableConfig $conf
     * @expectedException \T4\Core\Exception
     */
    public function testWidthPivotItems_NotValidValue($conf)
    {
        $conf->widthPivotItems('columnTwo', '10pix');
    }
}