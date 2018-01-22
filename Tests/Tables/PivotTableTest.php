<?php

require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/../../protected/autoload.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../protected/boot.php';

use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\TableConfig;
use App\Components\Tables\Table;
use App\Components\Tables\PivotTable;
use UnitTest\UnitTestClasses\ModelClass_1;
use App\Components\Sql\SqlFilter;
use T4\Core\Std;
use T4\Core\Config;

class PivotTableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PivotTableConfig $tableConf
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
        \T4\Console\Application::instance()->setConfig(
            new \T4\Core\Config(ROOT_PATH . '/Tests/dbTestsConfig.php')
        );
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
            self::$tableName = rand() . '__unitTest_testPivotTableConfig.php';
        } while (file_exists(self::$tableName));
        /*
         * Create config for test table and save one
         */
        $preFilterData = [];
        $sortTemplates = [
            'columnOne' => ['columnOne' => '', 'columnThree' => '']
        ];
        $sortBy = 'columnOne';
        $sortDirect = 'asc';
        $tableColumns = [
            'columnOne',
            'columnTwoPivot',
            'columnThree'
        ];
        $extraColumns = [
            'extra_1'
        ];
        $pivotColumns = [
            'columnTwoPivot' => 'columnTwo'
        ];
        $columnsConfig = [
            'columnOne' => [
                'id' => '',
                'name' => 'title columnOne',
                'width' => 10,
                'sortable' => true,
                'filterable' => true
            ],
            'columnTwoPivot' => [
                'id' => 'columnTwoPivot',
                'width' => 60
            ],
            'columnThree' => [
                'id' => '',
                'name' => 'title columnThree',
                'width' => 20,
                'sortable' => true,
                'filterable' => true
            ],
            'extra_1' => [
                'id' => '',
                'name' => 'title extraColumn_1',
                'width' => '100px',
            ],
        ];
        $pivotPreFilterData = [
            'columnOne' => ['eq' => ['c-1-v-0']]
        ];
        $pivotSortBy = ['columnTwo', 'columnThree'];
        $rowsPerPageList = [10, 20, 30];
        self::$tableConf = new PivotTableConfig(self::$tableName, $className);
        foreach ($pivotColumns as $alias => $col) {
            self::$tableConf->definePivotColumn($col, $alias);
        }
        self::$tableConf->columns(array_merge($tableColumns, $extraColumns), $extraColumns);
        foreach ($columnsConfig as $col => $conf) {
            self::$tableConf->columnConfig($col, new Std($conf));
        }

        $preFilter = (new SqlFilter($className))
            ->setFilterFromArray($preFilterData);

        $pivotPreFilter = (new SqlFilter($className))
            ->setFilterFromArray($pivotPreFilterData);


        self::$tableConf
            ->tablePreFilter($preFilter)
            ->pivotPreFilter('columnTwoPivot', $pivotPreFilter)
            ->pivotSortBy('columnTwoPivot', $pivotSortBy)
            ->pivotWidthItems('columnTwoPivot', '50px')
            ->sortOrderSets($sortTemplates)
            ->sortBy($sortBy, $sortDirect)
            ->rowsOnPageList($rowsPerPageList)
            ->save();
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

    /**
     * @return PivotTable
     */
    public function testReadTableConfig()
    {
        $conf = new PivotTableConfig(self::$tableName);
        $this->assertInstanceOf(PivotTableConfig::class, $conf);
        $this->assertNotEmpty($conf->toArray());

        $table = new PivotTable($conf);
        $this->assertInstanceOf(PivotTable::class, $table);
        $this->assertInstanceOf(PivotTableConfig::class, $table->config);
        $this->assertInstanceOf(SqlFilter::class, $table->filter);
        $this->assertInstanceOf(\T4\Dbal\IDriver::class, $table->driver);
        $this->assertInstanceOf(Std::class, $table->pagination);

        return $table;
    }

    public function providerSelectStatement()
    {
        return [
            'withoutPreFilter' => [
                [],
                'SELECT "columnOne", (SELECT jsonb_object_agg(t2."columnTwo", t2.numbers) FROM ( SELECT "columnTwo", count("columnTwo") AS numbers FROM "ModelClass_1"  AS t3 WHERE "columnOne" = :columnOne_eq_0 AND t3."columnOne" = t1."columnOne" AND t3."columnThree" = t1."columnThree" GROUP BY "columnTwo" ORDER BY "columnTwo", "columnThree" ) AS t2 ) AS "columnTwoPivot", "columnThree" FROM "ModelClass_1" AS t1 GROUP BY "columnOne", "columnThree" ORDER BY "columnOne" ASC, "columnThree" ASC'
            ],
            'columnOne 1 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0', 'c-1-v-1']]],
                'SELECT "columnOne", (SELECT jsonb_object_agg(t2."columnTwo", t2.numbers) FROM ( SELECT "columnTwo", count("columnTwo") AS numbers FROM "ModelClass_1"  AS t3 WHERE ("columnOne" = :columnOne_eq_0 OR "columnOne" = :columnOne_eq_1) AND t3."columnOne" = t1."columnOne" AND t3."columnThree" = t1."columnThree" GROUP BY "columnTwo" ORDER BY "columnTwo", "columnThree" ) AS t2 ) AS "columnTwoPivot", "columnThree" FROM "ModelClass_1" AS t1 WHERE ("columnOne" = :columnOne_eq_0 OR "columnOne" = :columnOne_eq_1) GROUP BY "columnOne", "columnThree" ORDER BY "columnOne" ASC, "columnThree" ASC'
            ],
            'columnOne 2 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0']]],
                'SELECT "columnOne", (SELECT jsonb_object_agg(t2."columnTwo", t2.numbers) FROM ( SELECT "columnTwo", count("columnTwo") AS numbers FROM "ModelClass_1"  AS t3 WHERE "columnOne" = :columnOne_eq_0 AND t3."columnOne" = t1."columnOne" AND t3."columnThree" = t1."columnThree" GROUP BY "columnTwo" ORDER BY "columnTwo", "columnThree" ) AS t2 ) AS "columnTwoPivot", "columnThree" FROM "ModelClass_1" AS t1 WHERE "columnOne" = :columnOne_eq_0 GROUP BY "columnOne", "columnThree" ORDER BY "columnOne" ASC, "columnThree" ASC'
            ],
        ];
    }

    /**
     * @dataProvider providerSelectStatement
     * @depends testReadTableConfig
     * @param $preFilterSet
     * @param $expectedQuery
     * @param PivotTable $table
     */
    public function testSelectStatement($preFilterSet, $expectedQuery, $table)
    {
        $preFilter = (new SqlFilter(ModelClass_1::class))->setFilterFromArray($preFilterSet);
        $table->config->tablePreFilter($preFilter);
        $select = $table->selectStatement();
        $select = str_replace("\n", ' ', $select);
        $this->assertEquals($expectedQuery, $select);
    }
}