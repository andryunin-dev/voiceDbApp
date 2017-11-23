<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\TableConfig;
use App\Components\Tables\Table;
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
    /**
     * @var string $tableName name of table
     */
    protected static $tableName;

    protected static $sqlDropTemporaryTable = 'DROP TABLE IF EXISTS "ModelClass_1"';
    protected static $sqlCreateTempTable = <<<'TAG'
        CREATE TABLE "ModelClass_1"
          AS
            WITH t (id, "columnOne", "columnTwo", "columnThree", "columnFour") AS (
              VALUES
                (0::INT, 'c-1-v-0', 'c-2-v-0', 'c-3-v-0', 'c-4-v-0'),
                (0::INT, 'c-1-v-0', 'c-2-v-0', 'c-3-v-1', 'c-4-v-1'),
                (0::INT, 'c-1-v-0', 'c-2-v-1', 'c-3-v-2', 'c-4-v-2'),
                (0::INT, 'c-1-v-0', 'c-2-v-1', 'c-3-v-3', 'c-4-v-3'),
                (0::INT, 'c-1-v-1', 'c-2-v-2', 'c-3-v-4', 'c-4-v-4'),
                (0::INT, 'c-1-v-1', 'c-2-v-2', 'c-3-v-5', 'c-4-v-5'),
                (0::INT, 'c-1-v-1', 'c-2-v-3', 'c-3-v-6', 'c-4-v-6'),
                (0::INT, 'c-1-v-1', 'c-2-v-3', 'c-3-v-7', 'c-4-v-7'),
                (0::INT, 'c-1-v-2', 'c-2-v-4', 'c-3-v-8', 'c-4-v-8'),
                (0::INT, 'c-1-v-2', 'c-2-v-4', 'c-3-v-9', 'c-4-v-9'),
                (0::INT, 'c-1-v-2', 'c-2-v-5', 'c-3-v-10', 'c-4-v-10'),
                (0::INT, 'c-1-v-2', 'c-2-v-5', 'c-3-v-11', 'c-4-v-11')
            )
        
          SELECT * FROM t    
TAG;


    public static function setUpBeforeClass()
    {
        /*
         * create table for class ModelClass_1
         */
        $conn = ModelClass_1::getDbConnection();
        $conn->execute(self::$sqlDropTemporaryTable);
        $conn->execute(self::$sqlCreateTempTable);
        /*
         * create unique class name for tests
         */
        $className = ModelClass_1::class;
        do {
            self::$tableName = rand() . '__unitTest_testTableConfig.php';
        } while (file_exists(self::$tableName));
        /*
         * Create config for test table and save one
         */
        $preFilterData = [
            'columnOne' => ['eq' => ['val_1']]
        ];
        $sortTemplates = [
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
        $rowsPerPageList = [10, 20, 30];
        self::$tableConf = new TableConfig(self::$tableName, $className);
        self::$tableConf->columns($tableColumns);
        foreach ($columnsConfig as $col => $conf) {
            self::$tableConf->columnConfig($col, new Std($conf));
        }

        $preFilter = new SqlFilter($className);
        $preFilter->setFilterFromArray($preFilterData);
        self::$tableConf->tablePreFilter($preFilter);

        self::$tableConf->sortOrderSets($sortTemplates);
        self::$tableConf->sortBy($sortBy, $sortDirect);
        self::$tableConf->rowsOnPageList($rowsPerPageList);
        self::$tableConf->save();
    }
    public static function tearDownAfterClass()
    {
        self::$tableConf->delete();
        $conn = ModelClass_1::getDbConnection();
        $conn->execute(self::$sqlDropTemporaryTable);

    }

    public function testCreateTempTable()
    {

        $res = ModelClass_1::findAll();
        $this->assertEquals(12, $res->count());
    }

    public function testReadTableConfig()
    {
        $conf = new TableConfig(self::$tableName);
        $this->assertInstanceOf(TableConfig::class, $conf);
        $this->assertNotEmpty($conf->toArray());

        $table = new Table($conf);
        $this->assertInstanceOf(Table::class, $table);
        $this->assertInstanceOf(TableConfig::class, $table->config);
        $this->assertInstanceOf(SqlFilter::class, $table->filter);
        $this->assertInstanceOf(\T4\Dbal\IDriver::class, $table->driver);
        $this->assertInstanceOf(Std::class, $table->pagination);

        return $table;
    }

    /**
     * @depends testReadTableConfig
     * @param Table $table
     */
    public function testSelectStatement($table)
    {
        $expected = 'SELECT "columnOne", "columnTwo", "columnThree", "columnFour" FROM "ModelClass_1" WHERE "columnOne" = :columnOne_eq_0 ORDER BY "columnOne" ASC, "columnThree" ASC ';
        $select = $table->selectStatement();
        $select = str_replace("\n", ' ', $select);
        $this->assertEquals($expected, $select);
    }
    /**
     * @depends testReadTableConfig
     * @param Table $table
     */
    public function testCountStatement($table)
    {
        $expected = 'SELECT count(*) FROM "ModelClass_1" WHERE "columnOne" = :columnOne_eq_0';
        $count = $table->countStatement();
        $count = str_replace("\n", ' ', $count);
        $this->assertEquals($expected, $count);
    }
}