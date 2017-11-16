<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\TableConfig;
use UnitTest\UnitTestClasses\ModelClass_1;
use App\Components\Sql\SqlFilter;
use T4\Core\Std;
use T4\Core\Config;

class TableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TableConfig $tableConf
     */
    protected static $tableConf;
    protected static $tableName;
    public static function setUpBeforeClass()
    {
        /**
         * @var \T4\Tests\Orm\Models\Model $className
         */
        $className = ModelClass_1::class;
        do {
            self::$tableName = rand() . '__unitTest_testTableConfig.php';
        } while (file_exists(self::$tableName));
        $preFilterData = [
            'columnOne' => ['eq' => ['val_1']]
        ];
        $sortOrders = [
            'columnOne' => ['columnOne' => '', 'columnThree' => '']
        ];
        $sortBy = 'columnOne';
        $sortDirect = 'asc';
        $tableColumns = [
            'columnOne',
            'columnTwo',
            'columnThree'
        ];
        $columnsConfig = [
            'columnOne' => [
                'id' => '',
                'title' => 'title columnOne',
                'width' => 10,
                'sortable' => true,
                'filterable' => true
            ],
            'columnTwo' => [
                'id' => '',
                'title' => 'title columnTwo',
                'width' => '10px',
                'sortable' => true,
                'filterable' => true
            ],
            'columnThree' => [
                'id' => '',
                'title' => 'title columnThree',
                'width' => 20,
                'sortable' => true,
                'filterable' => true
            ],
        ];
        self::$tableConf = new TableConfig(self::$tableName, $className);
        self::$tableConf->columns($tableColumns);
        foreach ($columnsConfig as $col => $conf) {
            self::$tableConf->columnConfig($col, new Std($conf));
        }

        $preFilter = new SqlFilter($className);
        $preFilter->setFilterFromArray($preFilterData);
        self::$tableConf->tablePreFilter($preFilter);

        self::$tableConf->sortOrderSets($sortOrders);
        self::$tableConf->sortBy($sortBy, $sortDirect);
        self::$tableConf->save();
    }
    public static function tearDownAfterClass()
    {
        self::$tableConf->delete();
    }

    public function testReadTableConfig()
    {
        $conf = new TableConfig(self::$tableName);
        $this->assertInstanceOf(TableConfig::class, $conf);
        $this->assertNotEmpty($conf->toArray());
    }
}