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
        $filter->addFilter('unknown', 'columnOne', ['val_1']);
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testAddFilter_UnknownProperty()
    {
        $filter = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $filter->addFilter('eq', 'column_unknown', ['val_1']);
    }

    public function testAddNewFilter()
    {
        $refClass = new ReflectionClass(\App\Components\Sql\SqlFilter::class);
        $prop = $refClass->getProperty('filter');
        $prop->setAccessible(true);

        $filter = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        //set new filter
        $filter->addFilter('eq', 'columnOne', ['val_1']);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['eq' => ['columnOne' => ['val_1']]], $prop->toArray());
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

        $filter->addFilter('eq', 'columnOne', ['val_2', 'val_3'], true);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['eq' => ['columnOne' => ['val_2', 'val_3']]], $prop->toArray());
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

        $filter->addFilter('eq', 'columnOne', ['val_1', 'val_3']);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['eq' => ['columnOne' => ['val_2', 'val_3', 'val_1']]], $prop->toArray());
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

        $filter->setFilter('eq', 'columnOne', ['val_4']);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['eq' => ['columnOne' => ['val_4']]], $prop->toArray());
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

        $filter->setFilter('eq', 'columnTwo', ['val_1']);
        $prop = $prop->getValue($filter);
        $this->assertEquals(['eq' => [
            'columnOne' => ['val_4'],
            'columnTwo' => ['val_1']
            ]
        ], $prop->toArray());
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

        $filter->removeFilter('eq', 'columnOne');
        $prop = $prop->getValue($filter);
        $this->assertEquals(['eq' => ['columnTwo' => ['val_1']]], $prop->toArray());
        return $filter;
    }

    public function testBuildFilterStatement_oneColumn_oneValue()
    {
        //create new Sql filter
        $filter = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $filter->addFilter('eq', 'columnOne', ['val_1']);
        //check
        $this->assertEquals('"columnOne" = :eq_columnOne_0', $filter->filterStatement);
        $expectedParams = [
            ':eq_columnOne_0' => 'val_1',
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
        $filter->addFilter('eq', 'columnOne', ['val_2']);
        $this->assertEquals('("columnOne" = :eq_columnOne_0 OR "columnOne" = :eq_columnOne_1)', $filter->filterStatement);
        $expectedParams = [
            ':eq_columnOne_0' => 'val_1',
            ':eq_columnOne_1' => 'val_2',
        ];
        $this->assertEquals($expectedParams, $filter->filterParams);
        return $filter;
    }
    /**
     * @depends testBuildFilterStatement_oneColumn_twoValues
     */
    public function testBuildFilterStatement_twoColumn_threeValues($filter)
    {
        $filter->addFilter('eq', 'columnTwo', ['val_1']);
        $expectedStatement = '("columnOne" = :eq_columnOne_0 OR "columnOne" = :eq_columnOne_1) AND "columnTwo" = :eq_columnTwo_0';
        $this->assertEquals($expectedStatement, $filter->filterStatement);
        $expectedParams = [
            ':eq_columnOne_0' => 'val_1',
            ':eq_columnOne_1' => 'val_2',
            ':eq_columnTwo_0' => 'val_1',
        ];
        $this->assertEquals($expectedParams, $filter->filterParams);
        return $filter;
    }
    /**
     * @depends testBuildFilterStatement_twoColumn_threeValues
     */
    public function testBuildFilterStatement_twoColumn_fourValues($filter)
    {
        $filter->addFilter('eq', 'columnTwo', ['val_2']);
        $expectedStatement = '("columnOne" = :eq_columnOne_0 OR "columnOne" = :eq_columnOne_1) AND ("columnTwo" = :eq_columnTwo_0 OR "columnTwo" = :eq_columnTwo_1)';
        $this->assertEquals($expectedStatement, $filter->filterStatement);
        $expectedParams = [
            ':eq_columnOne_0' => 'val_1',
            ':eq_columnOne_1' => 'val_2',
            ':eq_columnTwo_0' => 'val_1',
            ':eq_columnTwo_1' => 'val_2',
        ];
        $this->assertEquals($expectedParams, $filter->filterParams);
    }

    public function testToArray()
    {
        $f = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $f->setFilter('eq', 'columnOne', ['val_1']);
        $f->setFilter('eq', 'columnTwo', ['val_1', 'val_2']);
        $f->setFilter('lt', 'columnOne', ['val_1']);
        $expected = [
            'eq' => [
                'columnOne' => ['val_1'],
                'columnTwo' => ['val_1', 'val_2'],
            ],
            'lt' => [
                'columnOne' => ['val_1']
            ]
        ];
        $this->assertEquals($expected, $f->toArray());
    }

    public function providerFilterSets()
    {
        return [
            0 => [
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_1']],
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_2']],
                'replace',
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_2']],
            ],
            1 => [
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_1']],
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_2']],
                'append',
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_1', 'val_2']],
            ],
            2 => [
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_1']],
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_2']],
                'ignore',
                ['op' => 'eq', 'col' => 'columnOne', 'val' => ['val_1', 'val_2']],
            ],
        ];
    }

    /**
     * @dataProvider providerFilterSets
     */
    public function testMergingFilters_ReplaceMode($fs1, $fs2, $expected)
    {
        $f1 = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $f2 = new \App\Components\Sql\SqlFilter(\UnitTest\UnitTestClasses\ModelClass_1::class);
        $f1->setFilter('eq', 'columnOne', ['val_1']);
        $f2->setFilter('eq', 'columnOne', ['val_2']);
        $f1->mergeWith($f2);
    }
}