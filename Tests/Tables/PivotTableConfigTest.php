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
            '_1' => [['col' => 'columnOne', 'alias' => null, 'disp' => true], ['col' => 'columnOne', 'alias' => 'columnOne', 'disp' => true]],
            '_2' => [['col' => 'columnOne', 'alias' => 'alias', 'disp' => false], ['col' => 'columnOne', 'alias' => 'alias', 'disp' => false]],
        ];
    }

    /**
     * @dataProvider providerSetPivotColumn
     * @param $input
     * @param $expected
     * @return PivotTableConfig
     */
    public function testDefinePivotColumn($input, $expected)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());

        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);

        $res = $conf->definePivotColumn($input['col'], $input['alias'], $input['disp']);

        $expectedAlias = $expected['alias'];
        $this->assertInstanceOf(PivotTableConfig::class, $res);
        $this->assertTrue(isset($res->pivot->$expectedAlias));
        $this->assertEquals($res->pivot->$expectedAlias->column,$expected['col']);
        $this->assertEquals($res->pivot->$expectedAlias->display, $expected['disp']);
        return $conf;
    }
    public function providerSetTwoPivotColumn()
    {
        return [
            '_1' => [
                [
                    'col_1' => ['col' => 'columnOne', 'alias' => null, 'disp' => true],
                    'col_2' => ['col' => 'columnTwo', 'alias' => null, 'disp' => false],
                ],
                [
                    'exp_col_1' => ['col' => 'columnOne', 'alias' => 'columnOne', 'disp' => true],
                    'exp_col_2' => ['col' => 'columnTwo', 'alias' => 'columnTwo', 'disp' => false],
                ],
            ]

        ];
    }

    /**
     * @dataProvider providerSetTwoPivotColumn
     * @param $input
     * @param $expected
     * @return PivotTableConfig
     */
    public function testDefineTwoPivotColumn($input, $expected)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());

        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);

        $res = $conf->definePivotColumn($input['col_1']['col'], $input['col_1']['alias'], $input['col_1']['disp']);
        $res = $conf->definePivotColumn($input['col_2']['col'], $input['col_2']['alias'], $input['col_2']['disp']);

        $expectedAlias_1 = $expected['exp_col_1']['alias'];
        $expectedAlias_2 = $expected['exp_col_2']['alias'];
        $this->assertInstanceOf(PivotTableConfig::class, $res);
        $this->assertInstanceOf(PivotTableConfig::class, $res);
        $this->assertTrue(isset($res->pivot->$expectedAlias_1));
        $this->assertTrue(isset($res->pivot->$expectedAlias_2));
        $this->assertEquals($res->pivot->$expectedAlias_1->column,$expected['exp_col_1']['col']);
        $this->assertEquals($res->pivot->$expectedAlias_2->column,$expected['exp_col_2']['col']);
        $this->assertEquals($res->pivot->$expectedAlias_1->display,$expected['exp_col_1']['disp']);
        $this->assertEquals($res->pivot->$expectedAlias_2->display, $expected['exp_col_2']['disp']);
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

            $pivots = $conf->pivots();
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
        $this->assertEquals('columnTwo', $conf->pivots()->columnTwo->column);
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

    public function providerPivotItemsSelectBy()
    {
        return [
            '_1' => [[]],
            '_2' => [['columnOne']],
            '_3' => [['columnOne', 'columnThree']],
        ];
    }

    /**
     * @dataProvider providerPivotItemsSelectBy
     * @depends      testCreateBasePivotConfig
     * @param array $selectColumns
     * @param PivotTableConfig $conf
     */
    public function testPivotItemsSelectBy($selectColumns, $conf)
    {
        $conf->pivotItemsSelectBy('columnTwo', $selectColumns);
        $this->assertEquals($selectColumns, $conf->pivotItemsSelectBy('columnTwo')->toArray());
    }


    public function providerPivotSortBy()
    {
        return [
            '_1' => ['columnTwo', ['columnOne', 'columnTwo'], '', ['columnOne' => '', 'columnTwo' => '']],
            '_2' => ['columnTwo', ['columnOne', 'columnTwo'], 'asc', ['columnOne' => 'asc', 'columnTwo' => 'asc']],
        ];
    }

    /**
     * @dataProvider providerPivotSortBy
     * @depends testCreateBasePivotConfig
     * @param $pivotAlias
     * @param $columnSortBy
     * @param $direction
     * @param $expected
     * @param PivotTableConfig $conf
     */
    public function testPivotSortBy($pivotAlias, $columnSortBy, $direction, $expected, $conf)
    {
        $conf->pivotSortBy($pivotAlias, $columnSortBy, $direction);
        $this->assertEquals($expected, $conf->pivotSortBy($pivotAlias)->toArray());
    }

    public function providerPivotSortByQuotedString()
    {
        return [
            '_1' => ['columnTwo', ['columnOne', 'columnTwo'], '', '"columnOne", "columnTwo"'],
            '_2' => ['columnTwo', ['columnOne', 'columnTwo'], 'asc', '"columnOne" ASC, "columnTwo" ASC'],
        ];
    }
    /**
     * @dataProvider providerPivotSortByQuotedString
     * @depends testCreateBasePivotConfig
     * @param $pivotAlias
     * @param $columnSortBy
     * @param $direction
     * @param $expected
     * @param PivotTableConfig $conf
     */
    public function testPivotSortByQuotedString($pivotAlias, $columnSortBy, $direction, $expected, $conf)
    {
        $conf->pivotSortBy($pivotAlias, $columnSortBy, $direction);
        $this->assertEquals($expected, $conf->pivotSortByQuotedString($pivotAlias));
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

    public function providerWidthPivotItems()
    {
        return [
            '_1' => [10, 10],
            '_2' => ['10px', '10px']
        ];
    }

    /**
     * @depends      testCreateBasePivotConfig
     * @dataProvider providerWidthPivotItems
     * @param $width
     * @param $expected
     * @param PivotTableConfig $conf
     */
    public function testWidthPivotItems($width, $expected, $conf)
    {
        $this->assertInstanceOf(PivotTableConfig::class, $conf->pivotWidthItems('columnTwo', $width));
        $this->assertEquals($expected, $conf->pivotWidthItems('columnTwo'));
    }
    /**
     * @depends testCreateBasePivotConfig
     * @param PivotTableConfig $conf
     * @expectedException \T4\Core\Exception
     */
    public function testWidthPivotItems_NotValidType($conf)
    {
        $conf->pivotWidthItems('columnTwo', [10]);
    }
    /**
     * @depends testCreateBasePivotConfig
     * @param PivotTableConfig $conf
     * @expectedException \T4\Core\Exception
     */
    public function testWidthPivotItems_NotValidValue($conf)
    {
        $conf->pivotWidthItems('columnTwo', '10pix');
    }
}