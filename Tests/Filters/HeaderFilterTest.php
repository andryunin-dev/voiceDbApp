<?php

require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

class HeaderFilterTest extends \PHPUnit\Framework\TestCase
{
    //test creating new HeaderFilter's object
    public function providerHeaderfilter_1()
    {
        return [
            '_1' => [
                ['region' => ['eq' => 'value_1']],
                ['region' => ['eq', ['value_1']]]
            ],
            '_2' => [
                ['region' => ['eq' => ['value_1']]],
                ['region' => ['eq', ['value_1']]]
            ],
            '_3' => [
                ['region' => ['eq' => 'value_1, value_2']],
                ['region' => ['eq', ['value_1', 'value_2']]]
            ],
            '_4' => [
                [
                    'region' => ['eq' => 'value_1, value_2'],
                    'city' => ['GT' => 'value_1, value_2']
                ],
                [
                    'region' => ['eq', ['value_1', 'value_2']],
                    'city' => ['gt', ['value_1', 'value_2']]
                ],
            ],
            '_5' => [
                [
                    'region' => ['eq' => 'value_1'],
                    'wrong_column' => ['GT' => 'value_1']
                ],
                [
                    'region' => ['eq', ['value_1']]
                ]
            ]
        ];
    }

    /**
     * @dataProvider providerHeaderfilter_1
     */
    public function testCreateObj($dataset, $expected)
    {
        $filteredClassName = \App\ViewModels\DevModulePortGeo::class;
        $hdFilter = new \App\Components\ContentFilters\HeaderFilter($dataset, $filteredClassName);
        $this->assertInstanceOf(\App\Components\ContentFilters\HeaderFilter::class, $hdFilter);
        foreach ($expected as $prop => $statement) {
            $this->assertInstanceOf(\App\Components\ContentFilters\HeaderFilter::class, $hdFilter->$prop);
            $this->assertTrue(is_array($hdFilter->$prop->{$statement[0]}));
            $this->assertEquals($statement[1], $hdFilter->$prop->{$statement[0]});
        }
    }
    //test appendStatement
    public function providerHeaderfilter_2()
    {
        return [
            '_1' => [
                ['region' => ['eq' => 'value_1']],
                ['region' => ['eq' => 'value_2']],
                ['region' => ['eq', ['value_1', 'value_2']]]
            ],
            '_2' => [
                ['region' => ['eq' => 'value_1']],
                ['city' => ['eq' => 'value_2']],
                [
                    'region' => ['eq', ['value_1']],
                    'city' => ['eq', ['value_2']]
                ]
            ],
        ];
    }
    /**
     * @dataProvider providerHeaderfilter_2
     */
    public function testAppendStatement($create, $newStatement, $expected)
    {
        $filteredClassName = \App\ViewModels\DevModulePortGeo::class;
        $hdFilter = new \App\Components\ContentFilters\HeaderFilter($create, $filteredClassName);
        $this->assertInstanceOf(\App\Components\ContentFilters\HeaderFilter::class, $hdFilter);
        $hdFilter->appendStatement($newStatement);
        foreach ($expected as $prop => $statement) {
            $this->assertInstanceOf(\App\Components\ContentFilters\HeaderFilter::class, $hdFilter->$prop);
            $this->assertTrue(is_array($hdFilter->$prop->{$statement[0]}));
            $this->assertEquals($statement[1], $hdFilter->$prop->{$statement[0]});
        }
    }

}