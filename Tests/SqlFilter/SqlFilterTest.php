<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';


class SqlFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateFilter()
    {
        $this->assertInstanceOf(\App\Components\Sql\SqlFilter::class, new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class));
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testUnExistedClass()
    {
        new \App\Components\Sql\SqlFilter('notExistedClass');
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testNotModelClass()
    {
        new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\StdClass_1::class);
    }

    /**
     * @expectedException \T4\Core\Exception
     */
    public function testAddFilter_UnknownOperator()
    {
        $filter = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $filter->addFilter('columnOne', 'unknown', ['val_1']);
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testAddFilter_UnknownProperty()
    {
        $filter = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $filter->addFilter('column_unknown', 'eq', ['val_1']);
    }

    public function testAddNewFilter()
    {
        $refClass = new ReflectionClass(\App\Components\Sql\SqlFilter::class);
        $prop = $refClass->getProperty('filter');
        $prop->setAccessible(true);

        $filter = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        //set new filter
        $filter->addFilter('columnOne', 'eq', ['val_1']);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['columnOne' => ['eq' => ['val_1']]], $prop->toArray());
        return $filter;
    }

    /**
     * @depends testAddNewFilter
     * @param \App\Components\Sql\SqlFilter $filter
     * @return \App\Components\Sql\SqlFilter
     */
    public function testRewriteValue(\App\Components\Sql\SqlFilter $filter)
    {
        $refClass = new ReflectionClass(\App\Components\Sql\SqlFilter::class);
        $prop = $refClass->getProperty('filter');
        $prop->setAccessible(true);

        $filter->addFilter('columnOne', 'eq', ['val_2', 'val_3'], true);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['columnOne' => ['eq' => ['val_2', 'val_3']]], $prop->toArray());
        return $filter;
    }

    /**
     * @depends testRewriteValue
     * @param \App\Components\Sql\SqlFilter $filter
     * @return \App\Components\Sql\SqlFilter
     */
    public function testAppendValue(\App\Components\Sql\SqlFilter $filter)
    {
        $refClass = new ReflectionClass(\App\Components\Sql\SqlFilter::class);
        $prop = $refClass->getProperty('filter');
        $prop->setAccessible(true);

        $filter->addFilter('columnOne', 'eq', ['val_1', 'val_3']);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['columnOne' => ['eq' => ['val_2', 'val_3', 'val_1']]], $prop->toArray());
        return $filter;
    }
    /**
     * @depends testAppendValue
     * @param \App\Components\Sql\SqlFilter $filter
     * @return \App\Components\Sql\SqlFilter
     */
    public function testSetFilter(\App\Components\Sql\SqlFilter $filter)
    {
        $refClass = new ReflectionClass(\App\Components\Sql\SqlFilter::class);
        $prop = $refClass->getProperty('filter');
        $prop->setAccessible(true);

        $filter->setFilter('columnOne', 'eq', ['val_4']);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['columnOne' => ['eq' => ['val_4']]], $prop->toArray());
        return $filter;
    }
    /**
     * @depends testSetFilter
     * @param \App\Components\Sql\SqlFilter $filter
     * @return \App\Components\Sql\SqlFilter
     */
    public function testAddSecondColumn(\App\Components\Sql\SqlFilter $filter)
    {
        $refClass = new ReflectionClass(\App\Components\Sql\SqlFilter::class);
        $prop = $refClass->getProperty('filter');
        $prop->setAccessible(true);

        $filter->setFilter('columnTwo', 'eq', ['val_1']);
        $prop = $prop->getValue($filter);
        $expected = [
            'columnOne' => ['eq' => ['val_4']],
            'columnTwo' => ['eq' => ['val_1']],
        ];
        $this->assertEquals($expected, $prop->toArray());
        return $filter;
    }

    /**
     * @depends testAddSecondColumn
     * @param \App\Components\Sql\SqlFilter $filter
     * @return \App\Components\Sql\SqlFilter
     */
    public function testRemoveFilter(\App\Components\Sql\SqlFilter $filter)
    {
        $refClass = new ReflectionClass(\App\Components\Sql\SqlFilter::class);
        $prop = $refClass->getProperty('filter');
        $prop->setAccessible(true);

        $filter->removeFilter('columnOne', 'eq');
        $prop = $prop->getValue($filter);
        $expected = ['columnTwo' => ['eq' => ['val_1']]];
        $this->assertEquals($expected, $prop->toArray());
        return $filter;
    }

    public function testBuildFilterStatement_oneColumn_oneValue()
    {
        //create new Sql filter
        $filter = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
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

    public function testToArray()
    {
        $f = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $f->setFilter('columnOne', 'eq', ['val_1']);
        $f->setFilter('columnTwo', 'eq', ['val_1', 'val_2']);
        $f->setFilter('columnOne', 'lt', ['val_1']);
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
        $f1 = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $f2 = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $f1->setFilter($fs1['col'], $fs1['op'], $fs1['val']);
        $f2->setFilter($fs2['col'], $fs2['op'], $fs2['val']);
        $f1->mergeWith($f2, $mode);
        $this->assertEquals($expected, $f1->toArray());
    }
}