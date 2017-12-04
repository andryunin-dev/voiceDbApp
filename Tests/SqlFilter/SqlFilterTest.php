<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

use App\Components\Sql\SqlFilter;
use UnitTest\UnitTestClasses\ModelClass_1;
use UnitTest\UnitTestClasses\StdClass_1;

class SqlFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateFilterObj()
    {
        $this->assertInstanceOf(SqlFilter::class, new SqlFilter(ModelClass_1::class));
    }

    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateFilter_UnExistedClass()
    {
        new SqlFilter('notExistedClass');
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateFilter_NotModelClass()
    {
        new SqlFilter(StdClass_1::class);
    }

    /**
     * @expectedException \T4\Core\Exception
     */
    public function testAddFilter_UnknownOperator()
    {
        $filter = new SqlFilter(ModelClass_1::class);
        $filter->addFilter('columnOne', 'unknown', ['val_1']);
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testAddFilter_UnknownProperty()
    {
        $filter = new SqlFilter(ModelClass_1::class);
        $filter->addFilter('column_unknown', 'eq', ['val_1']);
    }

    public function providerAddFilter()
    {
        return [
            '_1' => [
                'columnOne', 'eq', ['val_1'], ['columnOne' => ['eq' => ['val_1']]]
            ],
            '_2' => [
                'columnOne', 'isnull', ['val_1'], ['columnOne' => ['isnull' => []]]
            ],
        ];
    }

    /**
     * @param $col
     * @param $op
     * @param $val
     * @param $expected
     * @return SqlFilter
     * @dataProvider providerAddFilter
     */
    public function testAddNewFilter($col, $op, $val, $expected)
    {
        $filter = new SqlFilter(ModelClass_1::class);
        //set new filter
        $res = $filter->addFilter($col, $op, $val);

        $this->assertEquals($expected, $res->toArray());
        return $filter;
    }

    public function testToArray()
    {
        $f = new SqlFilter(ModelClass_1::class);
        $f->addFilter('columnOne', 'eq', ['val_1']);
        $f->addFilter('columnTwo', 'eq', ['val_1', 'val_2']);
        $f->addFilter('columnOne', 'lt', ['val_1']);
        $expected = [
            'columnOne' => [
                'eq' => ['val_1'],
                'lt' => ['val_1']
            ],
            'columnTwo' => [
                'eq' => ['val_1', 'val_2']
            ],
        ];
        $this->assertEquals($expected, $f->toArray());
    }
    public function testSetFilterFromArray()
    {
        $f = new SqlFilter(ModelClass_1::class);
        $inputArray = [
            'columnOne' => [
                'eq' => ['val_1'],
                'lt' => ['val_1']
            ],
            'columnTwo' => [
                'eq' => ['val_1', 'val_2']
            ],
        ];
        $f->setFilterFromArray($inputArray);
        $expected = [
            'columnOne' => [
                'eq' => ['val_1'],
                'lt' => ['val_1']
            ],
            'columnTwo' => [
                'eq' => ['val_1', 'val_2']
            ],
        ];
        $this->assertEquals($expected, $f->toArray());
        return $f;
    }

    /**
     * @param SqlFilter $f
     * @return mixed
     * @depends testSetFilterFromArray
     */
    public function testAddFilterFromArray($f)
    {
        $inputArray = [
            'columnOne' => [
                'eq' => ['val_2'],
            ],
        ];
        $expected = [
            'columnOne' => [
                'eq' => ['val_1', 'val_2'],
                'lt' => ['val_1']
            ],
            'columnTwo' => [
                'eq' => ['val_1', 'val_2']
            ],
        ];
        $this->assertEquals($expected, $f->addFilterFromArray($inputArray, false)->toArray());
        return $f;
    }

    /**
     * @return SqlFilter
     */
    public function testAddFilter_RewriteValue()
    {
        $f = new SqlFilter(ModelClass_1::class);
        $f->addFilter('columnOne', 'eq', ['val_1'], true);
        $res = $f->addFilter('columnOne', 'eq', ['val_2', 'val_3'], true);
        $expected = ['columnOne' => ['eq' => ['val_2', 'val_3']]];
        $this->assertEquals($expected, $res->toArray());
        return $f;
    }

    /**
     * @depends testAddFilter_RewriteValue
     * @param SqlFilter $filter
     * @return SqlFilter
     */
    public function testAddFilter_AppendValue(SqlFilter $filter)
    {
        $filter->addFilter('columnOne', 'eq', ['val_1', 'val_3']);
        $expected = ['columnOne' => ['eq' => ['val_2', 'val_3', 'val_1']]];
        $this->assertEquals($expected, $filter->toArray());
        return $filter;
    }
    /**
     * @depends testAddFilter_AppendValue
     * @param SqlFilter $filter
     * @return SqlFilter
     */
    public function testSetFilter(SqlFilter $filter)
    {
        $filter->setFilter('columnOne', 'eq', ['val_4']);
        $expected = ['columnOne' => ['eq' => ['val_4']]];
            $this->assertEquals($expected, $filter->toArray());
        return $filter;
    }
    /**
     * @depends testSetFilter
     * @param SqlFilter $filter
     * @return SqlFilter
     */
    public function testAddSecondColumn(SqlFilter $filter)
    {
        $filter->setFilter('columnTwo', 'eq', ['val_1']);
        $expected = [
            'columnOne' => ['eq' => ['val_4']],
            'columnTwo' => ['eq' => ['val_1']],
        ];
        $this->assertEquals($expected, $filter->toArray());
        return $filter;
    }

    /**
     * @depends testAddSecondColumn
     * @param SqlFilter $filter
     * @return SqlFilter
     */
    public function testRemoveFilter(SqlFilter $filter)
    {
        $filter->removeFilter('columnOne', 'eq');
        $expected = ['columnTwo' => ['eq' => ['val_1']]];
        $this->assertEquals($expected, $filter->toArray());
        return $filter;
    }

    public function testBuildEmptyFilter()
    {
        //create new Sql filter
        $filter = new SqlFilter(ModelClass_1::class);
        $expectedStatement = '';
        $expectedParams = [];
        $this->assertEquals($expectedStatement, $filter->filterStatement);
        $this->assertEquals($expectedParams, $filter->filterParams);
        return $filter;
    }

    /**
     * @depends testBuildEmptyFilter
     * @param SqlFilter $filter
     * @return SqlFilter
     */
    public function testBuildFilterStatement_oneColumn_oneValue($filter)
    {
        $filter->addFilter('columnOne', 'eq', ['val_1']);
        //check
        $expectedStatement = '"columnOne" = :columnOne_eq_0';
        $this->assertEquals($expectedStatement, $filter->filterStatement);
        $expectedParams = [
            ':columnOne_eq_0' => 'val_1',
        ];
        $this->assertEquals($expectedParams, $filter->filterParams);
        //check clearing params array
        $this->assertCount(0, $filter->filterParams);
        return $filter;
    }

    /**
     * @depends testBuildFilterStatement_oneColumn_oneValue
     */
    public function testBuildFilterStatement_oneColumn_twoValues($filter)
    {
        $filter->addFilter('columnOne', 'eq', ['val_2']);
        $expectedStatement = '("columnOne" = :columnOne_eq_0 OR "columnOne" = :columnOne_eq_1)';
        $this->assertEquals($expectedStatement, $filter->filterStatement);
        $expectedParams = [
            ':columnOne_eq_0' => 'val_1',
            ':columnOne_eq_1' => 'val_2',
        ];
        $this->assertEquals($expectedParams, $filter->filterParams);
        return $filter;
    }
    /**
     * @depends testBuildFilterStatement_oneColumn_twoValues
     */
    public function testBuildFilterStatement_twoColumn_threeValues($filter)
    {
        $filter->addFilter('columnTwo', 'eq', ['val_1']);
        $expectedStatement = '("columnOne" = :columnOne_eq_0 OR "columnOne" = :columnOne_eq_1) AND "columnTwo" = :columnTwo_eq_0';
        $this->assertEquals($expectedStatement, $filter->filterStatement);
        $expectedParams = [
            ':columnOne_eq_0' => 'val_1',
            ':columnOne_eq_1' => 'val_2',
            ':columnTwo_eq_0' => 'val_1',
        ];
        $this->assertEquals($expectedParams, $filter->filterParams);
        return $filter;
    }
    /**
     * @depends testBuildFilterStatement_twoColumn_threeValues
     */
    public function testBuildFilterStatement_twoColumn_fourValues($filter)
    {
        $filter->addFilter('columnTwo', 'eq', ['val_2']);
        $expectedStatement = '("columnOne" = :columnOne_eq_0 OR "columnOne" = :columnOne_eq_1) AND ("columnTwo" = :columnTwo_eq_0 OR "columnTwo" = :columnTwo_eq_1)';
        $this->assertEquals($expectedStatement, $filter->filterStatement);
        $expectedParams = [
            ':columnOne_eq_0' => 'val_1',
            ':columnOne_eq_1' => 'val_2',
            ':columnTwo_eq_0' => 'val_1',
            ':columnTwo_eq_1' => 'val_2',
        ];
        $this->assertEquals($expectedParams, $filter->filterParams);
    }

    public function providerFilterSets()
    {
        return [
            0 => [
                ['col' => 'columnOne', 'op' => 'eq', 'val' => ['val_1']],
                ['col' => 'columnOne', 'op' => 'eq', 'val' => ['val_2']],
                'replace',
                ['columnOne' => ['eq' => ['val_2']]],
            ],
            1 => [
                ['col' => 'columnOne', 'op' => 'eq', 'val' => ['val_1']],
                ['col' => 'columnOne', 'op' => 'eq', 'val' => ['val_2']],
                'append',
                ['columnOne' => ['eq' => ['val_1', 'val_2']]],
            ],
            2 => [
                ['col' => 'columnOne', 'op' => 'eq', 'val' => ['val_1']],
                ['col' => 'columnOne', 'op' => 'eq', 'val' => ['val_2']],
                'ignore',
                ['columnOne' => ['eq' => ['val_1']]],
            ],
        ];
    }

    /**
     * @dataProvider providerFilterSets
     */
    public function testMergingFilters_ReplaceMode($fs1, $fs2, $mode, $expected)
    {
        $f1 = new SqlFilter(ModelClass_1::class);
        $f2 = new SqlFilter(ModelClass_1::class);
        $f1->setFilter($fs1['col'], $fs1['op'], $fs1['val']);
        $f2->setFilter($fs2['col'], $fs2['op'], $fs2['val']);
        $f1->mergeWith($f2, $mode);
        $this->assertEquals($expected, $f1->toArray());
    }

    public function providerAddUnaryFilter()
    {
        return [
            '_1' => ['columnOne', 'isnull', ['val_1'], 'columnOne ISNULL']
        ];
    }

    /**
     * @param $col
     * @param $op
     * @param $val
     * @param $expected
     * @dataProvider providerAddUnaryFilter
     */
    public function testAddUnaryFilter($col, $op, $val, $expected)
    {
        //create new Sql filter
        $filter = new SqlFilter(ModelClass_1::class);
        $filter->setFilter($col, $op, $val);
        $this->assertEquals($expected, $filter->filterStatement);
    }
}