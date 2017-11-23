<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

use App\Components\Tables\TableConfig;
use UnitTest\UnitTestClasses\ModelClass_1;
use UnitTest\UnitTestClasses\StdClass_1;
use \App\Components\Sql\SqlFilter;
use T4\Core\Std;
use T4\Core\Config;

class TableConfigTest extends \PHPUnit\Framework\TestCase
{
    protected static $fileName;

    public static function tearDownAfterClass()
    {
        if (file_exists(TableConfig::BASE_CONF_PATH . self::$fileName)) {
            unlink(TableConfig::BASE_CONF_PATH . self::$fileName);
        }
    }

    public function testCreateConfig()
    {
        do {
            self::$fileName = '__unitTest_testTableConfig_' . rand() . 'php';
        } while (file_exists(TableConfig::BASE_CONF_PATH . self::$fileName));

        $conf = (new TableConfig(self::$fileName, ModelClass_1::class))->save();
        $this->assertFileIsWritable($conf->getPath());
        $this->assertInstanceOf(TableConfig::class, $conf);
        $conf->delete();
        $this->assertFileNotExists($conf->getPath());
        $conf->save();
    }

    public function testReadConfig()
    {
        $conf = new TableConfig(self::$fileName);
        $this->assertInstanceOf(TableConfig::class, $conf);
        $conf->delete();
    }

    public function testCreateBaseConfig()
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());

        $conf = new TableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);
        $this->assertInstanceOf(TableConfig::class, $conf);
        return $conf;
    }


    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateConf_emptyTableName()
    {
        new TableConfig('', ModelClass_1::class);
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateConf_readNotExistedConf()
    {
        do {
            $fileName = '__unitTest_testTableConfig_' . rand() . 'php';
        } while (file_exists(TableConfig::BASE_CONF_PATH . $fileName));

        new TableConfig($fileName);
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateConf_NotModelClassExtends()
    {
        $fileName = '__unitTest_testTableConfig.php';
        new TableConfig($fileName, StdClass_1::class);
    }

    public function testColumnsSetGet()
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());

        $conf = new TableConfig($fileName, ModelClass_1::class);
        $res = $conf->columns($columnsArray);

        $this->assertInstanceOf(Std::class, $res);
        $this->assertInstanceOf(Std::class, $conf->columns);

        //check result
        $this->assertEquals($columnsArray, array_keys($conf->columns->toArray()));
        return $conf;
    }

    /**
     * @param TableConfig $conf
     * @depends testColumnsSetGet
     */
    public function testIsColumnSet($conf)
    {
        $this->assertTrue($conf->isColumnSet('columnTwo'));
    }

    /**
     * @expectedException \T4\Core\Exception
     */
    public function testColumnSet_incorrectColumnsSet()
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        $columnsArray[] = 'incorrect column';
        $conf = new TableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);
    }
    public function providerColumnConfig()
    {
        return [
            'id' => ['id', 'test_id', 'test_id'],
            'title' => ['title', 'test_title', 'test_title'],
            'width_percent' => ['width', '50', 50],
            'width_percent2' => ['width', 50, 50],
            'width_px' => ['width', '50px', '50px'],
            'width_Px' => ['width', '50Px', '50px'],
            'sortable' => ['sortable', true, true],
            'filterable' => ['filterable', false, false],
        ];
    }

    /**
     * @dataProvider providerColumnConfig
     * @param $param
     * @param $value
     * @param $expected
     */
    public function testColumnConfig($param, $value, $expected)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));

        $conf ->columns($columnsArray);
        $colConfig = new Std([$param => $value]);
        $column = 'columnOne';
        $res = $conf->columnConfig($column, $colConfig);

        $this->assertInstanceOf(TableConfig::class, $res);
        $this->assertEquals($expected, $res->columns->$column->$param);
        //test get
        $res = $conf->getColumnConfig($column);
        $this->assertEquals($expected, $res->$param);
    }

    public function providerColumnConfig_Multi()
    {
        return [
            '_1' => [
                ['id' => 'test_id', 'title' => 'test', 'width' => '50'],
                ['id' => 'test_id', 'title' => 'test', 'width' => 50],
            ],
            '_2' => [
                ['id' => 'test_id', 'title' => 'test', 'width' => '50PX', 'filterable' => true],
                ['id' => 'test_id', 'title' => 'test', 'width' => '50px', 'filterable' => true],
            ],
        ];
    }

    /**
     * @dataProvider providerColumnConfig_Multi
     * @param array $params
     * @param $expected
     */
    public function testColumnConfig_Multi($params, $expected)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        $colConfig = (new Std($params));
        $column = 'columnOne';

        $conf->columnConfig($column,$colConfig);

        $res = $conf->getColumnConfig($column)->toArray();
        $diff = array_diff_assoc($expected, $res);
        $this->assertCount(0, $diff);
    }

    public function providerColumnConfig_Exceptions()
    {
        return [
            'id_asArray' => ['id', ['test_id']],
            'title_asNumber' => ['title', 213],
            'width_spaceInside' => ['width', '50 px'],
            'width_asArray' => ['width', ['50px']],
            'sortable_asString' => ['sortable', 'true'],
            'filterable__asString' => ['filterable', 'false'],
        ];
    }

    /**
     * @dataProvider providerColumnConfig_Exceptions
     * @param $param
     * @param $value
     * @expectedException \T4\Core\Exception
     */
    public function testColumnConfig_NotCorrectValue($param, $value)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        $colConfig = (new Std([$param => $value]));
        $column = 'columnOne';
        $conf->columnConfig($column,$colConfig);
    }

    public function providerColumnConfig_MultiParams_Exceptions()
    {
        return [
            '_1' => [
                ['id' => 'test_id', 'title' => 'test', 'width' => '50', 'unknown' => true]
            ],
            '_2' => [
                ['id' => 'test_id', 'title' => 'test', 'width' => '50 px', 'filterable' => true]
            ],
        ];
    }

    /**
     * @dataProvider providerColumnConfig_MultiParams_Exceptions
     * @param $params
     * @expectedException \T4\Core\Exception
     */
    public function testColumnConfig_NotCorrectValue_Multi($params)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        $colConfig = new Std($params);
        $column = 'columnOne';
        $conf->columnConfig($column,$colConfig);
    }

    public function providerSortOrderSets()
    {
        return [
            '_1' => [
                ['template_1' => ['columnOne' => '', 'columnTwo' => '']]
            ],
            '_2' => [
                [
                    'template_2' => ['columnThree' => '', 'columnTwo' => ''],
                    'template_3' => ['columnThree' => 'desc', 'columnTwo' => 'asc']
                ],
            ]
        ];
    }

    /**
     * @dataProvider providerSortOrderSets
     * @param $template
     */
    public function testSortOrderSets($template)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        $conf->sortOrderSets($template);
        $this->assertInstanceOf(Std::class, $conf->sortOrderSets);
        $this->assertEquals($template, $conf->sortOrderSets->toArray());
    }

    public function providerSortOrderSets_Exceptions()
    {
        return [
            '_wrong_direction' => [
                [
                    'template_2' => ['columnThree' => '', 'columnTwo' => ''],
                    'template_3' => ['columnThree' => '', 'columnTwo' => 'wrong']
                ]
            ],
            '_wrong_column' => [
                [
                    'template_2' => ['Unknown' => '', 'columnTwo' => ''],
                    'template_3' => ['columnThree' => '', 'columnTwo' => '']
                ]
            ],
        ];
    }
    /**
     * @dataProvider providerSortOrderSets_Exceptions
     * @param $template
     * @expectedException \T4\Core\Exception
     */
    public function testSortOrderSets_Exceptions($template)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        $conf->sortOrderSets($template);
    }

    public function providerSortBy()
    {
        return [
            '_1' => [
                [
                    'template_1' => ['columnThree' => '', 'columnTwo' => ''],
                ],
                'template_1',
                'asc',
                ['columnThree' => 'asc', 'columnTwo' => 'asc']
            ],
            '_2' => [
                [
                    'template_1' => ['columnThree' => '', 'columnTwo' => ''],
                    'template_2' => ['columnTwo' => 'desc', 'columnOne' => '']
                ],
                'template_2',
                'asc',
                ['columnTwo' => 'desc', 'columnOne' => 'asc']
            ],
            '_3' => [
                [
                    'template_1' => ['columnThree' => '', 'columnTwo' => ''],
                ],
                'columnOne',
                'asc',
                ['columnOne' => 'asc']
            ],
        ];
    }

    /**
     * @param $orderSets
     * @param $template
     * @param $direction
     * @param $expected
     * @dataProvider providerSortBy
     */
    public function testSortBy($orderSets, $template, $direction, $expected)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        foreach ($columnsArray as $col) {
            $conf->columnConfig($col, new Std(['sortable' => true]));
        }
        $conf->sortOrderSets($orderSets);
        $res = $conf->sortBy($template, $direction);
        $this->assertEquals($expected, $res->toArray());
    }
    public function providerSortOrderToQuotedString()
    {
        return [
            '_1' => [
                [
                    'template_1' => ['columnThree' => '', 'columnTwo' => ''],
                ],
                'template_1',
                'asc',
                '"columnThree" ASC, "columnTwo" ASC'
            ],
        ];
    }

    /**
     * @param $orderSets
     * @param $template
     * @param $direction
     * @param $expected
     * @dataProvider providerSortOrderToQuotedString
     */
    public function testGetSortOrderAsQuotedString($orderSets, $template, $direction, $expected)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        $conf->sortOrderSets($orderSets);
        $conf->sortBy($template, $direction);
        $res = $conf->getSortOrderAsQuotedString();
        $this->assertEquals($expected, $res);
    }

    public function providerSortBy_Exceptions()
    {
        return [
            'invalid direction' => [
                [
                    'template_1' => ['columnThree' => '', 'columnTwo' => ''],
                ],
                'template_1',
                'invalid'
            ],
            'not defined template' => [
                [
                    'template_1' => ['columnThree' => '', 'columnTwo' => ''],
                    'template_2' => ['columnTwo' => 'desc', 'columnOne' => '']
                ],
                'template_3',
                'asc'
            ],
            'not defined column' => [
                [
                    'template_1' => ['columnThree' => '', 'columnTwo' => ''],
                    'template_2' => ['columnTwo' => 'desc', 'columnOne' => '']
                ],
                'columnUndefined',
                'asc'
            ],
        ];
    }

    /**
     * @param $orderSets
     * @param $template
     * @param $direction
     * @dataProvider providerSortBy_Exceptions
     * @expectedException \T4\Core\Exception
     */
    public function testSortBy_Exceptions($orderSets, $template, $direction)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        $conf->sortOrderSets($orderSets);
        $conf->sortBy($template, $direction);
    }

    public function providerTablePreFilterSetter()
    {
        return [
            '_1' => [
                [
                    'columnOne' => [
                        'lt' => ['val_1']
                    ],
                    'columnTwo' => [
                        'eq' => ['val_1', 'val_2']
                    ],
                ]
            ]
        ];
    }

    /**
     * @param $preFilter
     * @dataProvider providerTablePreFilterSetter
     */
    public function testTablePreFilter_SetterGetter($preFilter)
    {
        $pf = (new SqlFilter(ModelClass_1::class))->setFilterFromArray($preFilter);

        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $conf->columns($columnsArray);
        //test setter and its returned value
        $res = $conf->tablePreFilter($pf);
        $this->assertInstanceOf(SqlFilter::class, $res);
        $this->assertEquals($res->toArray(), $preFilter);
        //test getter and its returned value
        $res = $conf->tablePreFilter();
        $this->assertInstanceOf(SqlFilter::class, $res);
        $this->assertEquals($res->toArray(), $preFilter);
    }

    public function testRowsOnPageList()
    {
        $list = [10, 20, 30, 'все'];
        $fileName = '__unitTest_testTableConfig.php';
        /**
         * @var TableConfig $conf
         */
        $conf = (new TableConfig($fileName, ModelClass_1::class));
        $res = $conf->rowsOnPageList($list);
        $this->assertInstanceOf(Std::class, $res);
        $this->assertEquals($list, $res->pagination->rowsOnPageList->toArray());

        $res = $conf->rowsOnPageList();
        $this->assertInstanceOf(Config::class, $res);
        $this->assertEquals($list, $res->pagination->rowsOnPageList->toArray());
        //test get method
        $this->assertEquals($list, $res->getRowsOnPageList()->toArray());
    }
    /*TEST methods that set css classes for the table*/
    /**
     * @depends testCreateBaseConfig
     * @param TableConfig $conf
     */
    public function testEmptyCssClasses($conf)
    {
        $this->assertInstanceOf(Std::class, $conf->cssStyles);
    }

    public function providerSetCssClasses()
    {
        return [
            '_1' => ['cssClass_1', ['cssClass_1']],
            '_2' => [['cssClass_1', 'cssClass_2'], ['cssClass_1', 'cssClass_2']],
        ];
    }

    /**
     * @depends testCreateBaseConfig
     * @dataProvider providerSetCssClasses
     *
     * @param string|array $cssClasses
     * @param $expected
     * @param TableConfig $conf
     */
    public function testCssSetHeaderTableClass($cssClasses, $expected, $conf)
    {
        $conf->cssSetHeaderTableClasses($cssClasses);
        $this->assertInstanceOf(Std::class, $conf->headerCssClasses);
        $this->assertEquals($expected, $conf->headerCssClasses->table->toArray());
    }

    /**
     * @depends testCreateBaseConfig
     * @dataProvider providerSetCssClasses
     *
     * @param string|array $cssClasses
     * @param $expected
     * @param TableConfig $conf
     */
    public function testCssSetBodyTableClass($cssClasses, $expected, $conf)
    {
        $conf->cssSetBodyTableClasses($cssClasses);
        $this->assertInstanceOf(Std::class, $conf->bodyCssClasses);
        $this->assertEquals($expected, $conf->bodyCssClasses->table->toArray());
    }

    /**
     * @depends testCreateBaseConfig
     * @dataProvider providerSetCssClasses
     *
     * @param string|array $cssClasses
     * @param $expected
     * @param TableConfig $conf
     */
    public function testCssSetFooterTableClass($cssClasses, $expected, $conf)
    {
        $conf->cssSetFooterTableClasses($cssClasses);
        $this->assertInstanceOf(Std::class, $conf->footerCssClasses);
        $this->assertEquals($expected, $conf->footerCssClasses->table->toArray());
    }

    public function providerAddCssClasses()
    {
        return [
            '_1' => ['cssClass_1', 'cssClass_1', ['cssClass_1']],
            '_2' => ['cssClass_1', 'cssClass_2', ['cssClass_1', 'cssClass_2']],
            '_3' => [['cssClass_1', 'cssClass_2'], 'cssClass_1', ['cssClass_1', 'cssClass_2']],
            '_4' => [['cssClass_1', 'cssClass_2'], 'cssClass_3', ['cssClass_1', 'cssClass_2', 'cssClass_3']],
        ];
    }

    /**
     * @depends testCreateBaseConfig
     * @dataProvider providerAddCssClasses
     *
     * @param string|array $cssClasses_1
     * @param string|array $cssClasses_2
     * @param $expected
     * @param TableConfig $conf
     */
    public function testCssAddHeaderTableClass($cssClasses_1, $cssClasses_2, $expected, $conf)
    {
        $conf->cssSetHeaderTableClasses($cssClasses_1);
        $conf->cssAddHeaderTableClasses($cssClasses_2);
        $this->assertInstanceOf(Std::class, $conf->headerCssClasses);
        $this->assertEquals($expected, $conf->headerCssClasses->table->toArray());
    }

    /**
     * @depends testCreateBaseConfig
     * @dataProvider providerAddCssClasses
     *
     * @param string|array $cssClasses_1
     * @param string|array $cssClasses_2
     * @param $expected
     * @param TableConfig $conf
     */
    public function testCssAddBodyTableClass($cssClasses_1, $cssClasses_2, $expected, $conf)
    {
        $conf->cssSetBodyTableClasses($cssClasses_1);
        $conf->cssAddBodyTableClasses($cssClasses_2);
        $this->assertInstanceOf(Std::class, $conf->bodyCssClasses);
        $this->assertEquals($expected, $conf->bodyCssClasses->table->toArray());
    }

    /**
     * @depends testCreateBaseConfig
     * @dataProvider providerAddCssClasses
     *
     * @param string|array $cssClasses_1
     * @param string|array $cssClasses_2
     * @param $expected
     * @param TableConfig $conf
     */
    public function testCssAddFooterTableClass($cssClasses_1, $cssClasses_2, $expected, $conf)
    {
        $conf->cssSetFooterTableClasses($cssClasses_1);
        $conf->cssAddFooterTableClasses($cssClasses_2);
        $this->assertInstanceOf(Std::class, $conf->footerCssClasses);
        $this->assertEquals($expected, $conf->footerCssClasses->table->toArray());
    }

}