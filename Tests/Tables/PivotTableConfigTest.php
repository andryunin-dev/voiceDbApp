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
    public function testDefinePivotColumn($column, $alias, $expectedAlias, $expectedValue)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());

        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);

        $res = $conf->definePivotColumn($column, $alias);
        $this->assertInstanceOf(PivotTableConfig::class, $res);
        $this->assertTrue(isset($res->pivot->$expectedAlias));
        $this->assertEquals($res->pivot->$expectedAlias->column, $expectedValue);
        return $conf;
    }

    public function providerSetPivotColumn_Exception()
    {
        return [
            '_1' => ['unknownColumn', null],
            '_2' => ['extra_1', null],
        ];
    }

    /**
     * @dataProvider providerSetPivotColumn_Exception
     * @expectedException \T4\Core\Exception
     * @param $column
     * @param $alias
     * @return PivotTableConfig
     */
    public function testDefinePivotColumn_Exception($column, $alias)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());

        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray, ['extra_1']);

        $conf->definePivotColumn($column, $alias);
    }

    public function providerColumns()
    {
        return [
            '_1' => [['columnOne', 'columnTwo', 'pivotAlias', 'extra_1'], ['extra_1'], ['pivotAlias' => 'columnThree']]
        ];
    }

    /**
     * @dataProvider providerColumns
     * @param $columns
     * @param $extra
     * @param $pivots
     */
    public function testColumns($columns, $extra, $pivots)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        foreach ($pivots as $alias => $col) {
            $conf->definePivotColumn($col, $alias);

            $pivots = $conf->pivots;
            $this->assertInstanceOf(Std::class, $pivots);
            $this->assertTrue(isset($pivots->$alias));
            $this->assertEquals($col, $pivots->$alias->column);
        }
        $res = $conf->columns($columns, $extra);
        $this->assertInstanceOf(PivotTableConfig::class, $res);
        $resColumns = $conf->columns();
        $this->assertInstanceOf(Std::class, $resColumns);
        $this->assertEquals($columns, $resColumns->toArray());
    }

    public function providerColumns_Exception()
    {
        return [
            '_Unknown column' => [['unknown', 'columnTwo', 'pivotAlias', 'extra_1'], ['extra_1'], ['pivotAlias' => 'columnThree']],
            '_not correct alias' => [['unknown', 'columnTwo', 'pivotAlias_err', 'extra_1'], ['extra_1'], ['pivotAlias' => 'columnThree']],
            '_not_correct_extra' => [['unknown', 'columnTwo', 'pivotAlias_err', 'extra_2'], ['extra_1'], ['pivotAlias' => 'columnThree']],
            '_not_correct_pivot_column' => [['unknown', 'columnTwo', 'pivotAlias_err', 'extra_2'], ['extra_1'], ['pivotAlias' => 'columnN']],
        ];
    }

    /**
     * @dataProvider providerColumns_Exception
     * @expectedException \T4\Core\Exception
     * @param $columns
     * @param $extra
     * @param $pivots
     */
    public function testColumns_Exception($columns, $extra, $pivots)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        foreach ($pivots as $alias => $col) {
            $conf->definePivotColumn($col, $alias);
        }
        $conf->columns($columns, $extra);
    }

    /**
     * using for next tests as base config object
     */
    public function testCreateBasePivotConfig()
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        $extraCols = ['extra_1', 'extra_2'];
        unset($columnsArray[$count - 1]);
        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray, $extraCols);
        $conf->definePivotColumn('columnTwo');
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
        $this->assertInstanceOf(PivotTableConfig::class, $res);
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
        $this->assertInstanceOf(PivotTableConfig::class, $res);
        $this->assertEquals($expected, $conf->pivotSortBy('columnTwo')->toArray());

        $expected = array_fill_keys($sortBy, 'asc');
        $conf->pivotSortBy('columnTwo', $sortBy, 'asc');
        $this->assertEquals($expected, $conf->pivotSortBy('columnTwo')->toArray());
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