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
}