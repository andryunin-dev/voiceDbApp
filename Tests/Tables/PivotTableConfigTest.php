<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

use App\Components\Tables\PivotTableConfig;
use UnitTest\UnitTestClasses\ModelClass_1;
use UnitTest\UnitTestClasses\StdClass_1;
use \App\Components\Sql\SqlFilter;
use T4\Core\Std;

class PivotTableConfigTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateConfig()
    {
        do {
            $fileName = '__unitTest_testTableConfig_' . rand() . 'php';
        } while (file_exists(PivotTableConfig::BASE_CONF_PATH . $fileName));

        $conf = (new PivotTableConfig($fileName, ModelClass_1::class))->save();
        $this->assertFileIsWritable($conf->getPath());
        $this->assertInstanceOf(PivotTableConfig::class, $conf);
        $conf->delete();
        $this->assertFileNotExists($conf->getPath());
    }

    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateConf_emptyTableName()
    {
        new PivotTableConfig('', ModelClass_1::class);
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateConf_readNotExistedConf()
    {
        do {
            $fileName = '__unitTest_testTableConfig_' . rand() . 'php';
        } while (file_exists(PivotTableConfig::BASE_CONF_PATH . $fileName));

        new PivotTableConfig($fileName);
    }
    /**
     * @expectedException \T4\Core\Exception
     */
    public function testCreateConf_NotModelClassExtends()
    {
        $fileName = '__unitTest_testTableConfig.php';
        new PivotTableConfig($fileName, StdClass_1::class);
    }

    public function testColumnsSetGet()
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        $refClass = new ReflectionClass(PivotTableConfig::class);
        $colTmpl = $refClass->getProperty('columnPropertiesTemplate');
        $colTmpl->setAccessible(true);

        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);
        $this->assertInstanceOf(\T4\Core\Config::class, $conf->columns);

        //check result
        $colTmpl = $colTmpl->getValue($conf);
        $expected = array_fill_keys($columnsArray, $colTmpl);
        $this->assertInstanceOf(Std::class, $conf->columns());
        $this->assertEquals($expected, $conf->columns()->toArray());
        return $conf;
    }

    /**
     * @param PivotTableConfig $conf
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
        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);
    }
    /**
     *
     */
    public function testSetPivotColumn()
    {
        $class = new ReflectionClass(PivotTableConfig::class);
        $template = $class->getProperty('pivotColumnPropertiesTemplate');
        $template->setAccessible(true);
        $methodIsPivot = $class->getMethod('isPivot');
        $methodIsPivot->setAccessible(true);

        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        $conf = new PivotTableConfig($fileName, ModelClass_1::class);
        $conf->columns($columnsArray);

        $res = $conf->setPivotColumn('columnTwo');
        $this->assertInstanceOf(Std::class, $res);
        $template = $template->getValue($conf);
        $this->assertEquals($template, $res->toArray());
        return $conf;
    }

    /**
     * @depends testSetPivotColumn
     * @param PivotTableConfig $conf
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
     * @depends testSetPivotColumn
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

    /**
     * @depends testSetPivotColumn
     * @expectedException \T4\Core\Exception
     *
     * @param PivotTableConfig $conf
     */
    public function testPivotSortBy_NotPivotColumn($conf)
    {
        $sortBy = ['columnOne', 'columnTwo'];
         $conf->pivotSortBy('columnOne', $sortBy);
    }
    /**
     * @depends testSetPivotColumn
     * @expectedException \T4\Core\Exception
     *
     * @param PivotTableConfig $conf
     */
    public function testPivotSortBy_NotDefinedSortColumn($conf)
    {
        $sortBy = ['columnOne', 'undefinedColumn'];
        $conf->pivotSortBy('columnTwo', $sortBy);
    }

    /**
     * @depends testSetPivotColumn
     * @param PivotTableConfig $conf
     */
    public function testWidthPivotItems($conf)
    {
        $this->assertEquals(10, $conf->widthPivotItems('columnTwo', 10));
        $this->assertEquals('10px', $conf->widthPivotItems('columnTwo', ' 10PX '));
    }
    /**
     * @depends testSetPivotColumn
     * @param PivotTableConfig $conf
     * @expectedException \T4\Core\Exception
     */
    public function testWidthPivotItems_NotValidType($conf)
    {
        $conf->widthPivotItems('columnTwo', [10]);
    }
    /**
     * @depends testSetPivotColumn
     * @param PivotTableConfig $conf
     * @expectedException \T4\Core\Exception
     */
    public function testWidthPivotItems_NotValidValue($conf)
    {
        $conf->widthPivotItems('columnTwo', '10pix');
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
         * @var PivotTableConfig $conf
         */
        $conf = (new PivotTableConfig($fileName, ModelClass_1::class))
            ->columns($columnsArray);
        $colConfig = (new Std([$param => $value]));
        $column = 'columnOne';
        $res = $conf->columnConfig($column,$colConfig);
        $this->assertEquals($expected, $res->$param);
        //test get
        $res = $conf->columnConfig($column);
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
         * @var PivotTableConfig $conf
         */
        $conf = (new PivotTableConfig($fileName, ModelClass_1::class))
            ->columns($columnsArray);
        $colConfig = (new Std($params));
        $column = 'columnOne';

        $res = $conf->columnConfig($column,$colConfig);
        $res = $res->toArray();
        $diff = array_diff_assoc($expected, $res);
        $this->assertCount(0, $diff);
        //test get
        $res = $conf->columnConfig($column)->toArray();
        $diff = array_diff_assoc($expected, $res);
        $this->assertCount(0, $diff);
    }

    public function providerColumnConfig_Exceptions()
    {
        return [
            'id' => ['id', ['test_id']],
            'title' => ['title', 213],
            'width_percent' => ['width', '50 px'],
            'width_px' => ['width', ['50px']],
            'sortable' => ['sortable', 'true'],
            'filterable' => ['filterable', 'false'],
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
         * @var PivotTableConfig $conf
         */
        $conf = (new PivotTableConfig($fileName, ModelClass_1::class))
            ->columns($columnsArray);
        $colConfig = (new Std([$param => $value]));
        $column = 'columnOne';
        $conf->columnConfig($column,$colConfig);
    }

    public function providerColumnConfig_Multi_Exceptions()
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
     * @dataProvider providerColumnConfig_Multi_Exceptions
     * @param $params
     * @expectedException \T4\Core\Exception
     */
    public function testColumnConfig_NotCorrectValue_Multi($params)
    {
        $fileName = '__unitTest_testTableConfig.php';
        $columnsArray = array_keys(ModelClass_1::getColumns());
        /**
         * @var PivotTableConfig $conf
         */
        $conf = (new PivotTableConfig($fileName, ModelClass_1::class))
            ->columns($columnsArray);
        $colConfig = new Std($params);
        $column = 'columnOne';
        $conf->columnConfig($column,$colConfig);
    }
}