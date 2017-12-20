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
                (1::INT, 'c-1-v-0', 'c-2-v-0', 'c-3-v-1', 'c-4-v-1'),
                (2::INT, 'c-1-v-0', 'c-2-v-1', 'c-3-v-2', 'c-4-v-2'),
                (3::INT, 'c-1-v-0', 'c-2-v-1', 'c-3-v-3', 'c-4-v-3'),
                (4::INT, 'c-1-v-1', 'c-2-v-2', 'c-3-v-4', 'c-4-v-4'),
                (5::INT, 'c-1-v-1', 'c-2-v-2', 'c-3-v-5', 'c-4-v-5'),
                (6::INT, 'c-1-v-1', 'c-2-v-3', 'c-3-v-6', 'c-4-v-6'),
                (7::INT, 'c-1-v-1', 'c-2-v-3', 'c-3-v-7', 'c-4-v-7'),
                (8::INT, 'c-1-v-2', 'c-2-v-4', 'c-3-v-8', 'c-4-v-8'),
                (9::INT, 'c-1-v-2', 'c-2-v-4', 'c-3-v-9', 'c-4-v-9'),
                (10::INT, 'c-1-v-2', 'c-2-v-5', 'c-3-v-10', 'c-4-v-10'),
                (11::INT, 'c-1-v-2', 'c-2-v-5', 'c-3-v-11', 'c-4-v-11')
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
            self::$tableName = rand() . '__unitTest_testTableConfig.php';
        } while (file_exists(self::$tableName));
        /*
         * Create config for test table and save one
         */
        $preFilterData = [
            'columnOne' => ['eq' => ['c-1-v-0', 'c-1-v-1']]
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
        $extraColumns = [
            'extra_1'
        ];
        $columnsConfig = [
            'columnOne' => [
                'id' => '',
                'name' => 'title columnOne',
                'width' => 10,
                'sortable' => true,
                'filterable' => true
            ],
            'columnTwo' => [
                'id' => '',
                'name' => 'title columnTwo',
                'width' => '10px',
                'sortable' => true,
                'filterable' => true
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
        $rowsPerPageList = [10, 20, 30];
        self::$tableConf = new TableConfig(self::$tableName, $className);
        self::$tableConf->columns(array_merge($tableColumns, $extraColumns), $extraColumns);
        foreach ($columnsConfig as $col => $conf) {
            self::$tableConf->columnConfig($col, new Std($conf));
        }

        $preFilter = new SqlFilter($className);
        $preFilter->setFilterFromArray($preFilterData);
        //self::$tableConf->tablePreFilter($preFilter);

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
    public function testRowsOnPage($table)
    {
        $res = $table->rowsOnPage(42);
        $this->assertInstanceOf(Table::class, $res);

        $this->assertEquals(42, $table->rowsOnPage());
    }

    public function providerSelectStatement()
    {
        return [
            'withoutPreFilter' => [
                [],
                'SELECT "columnOne", "columnTwo", "columnThree" FROM "ModelClass_1" ORDER BY "columnOne" ASC, "columnThree" ASC '
            ],
            'columnOne 1 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0', 'c-1-v-1']]],
                'SELECT "columnOne", "columnTwo", "columnThree" FROM "ModelClass_1" WHERE ("columnOne" = :columnOne_eq_0 OR "columnOne" = :columnOne_eq_1) ORDER BY "columnOne" ASC, "columnThree" ASC '
            ],
            'columnOne 2 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0']]],
                'SELECT "columnOne", "columnTwo", "columnThree" FROM "ModelClass_1" WHERE "columnOne" = :columnOne_eq_0 ORDER BY "columnOne" ASC, "columnThree" ASC '
            ],
        ];
    }

    /**
     * @dataProvider providerSelectStatement
     * @depends testReadTableConfig
     * @param $preFilterSet
     * @param $expectedQuery
     * @param Table $table
     */
    public function testSelectStatement($preFilterSet, $expectedQuery, $table)
    {
        $preFilter = (new SqlFilter(ModelClass_1::class))->setFilterFromArray($preFilterSet);
        $table->config->tablePreFilter($preFilter);
        $select = $table->selectStatement();
        $select = str_replace("\n", ' ', $select);
        $this->assertEquals($expectedQuery, $select);
    }

    public function providerCountStatement()
    {
        return [
            'withoutPreFilter' => [
                [],
                'SELECT count(*) FROM "ModelClass_1"'
            ],
            'columnOne 1 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0']]],
                'SELECT count(*) FROM "ModelClass_1" WHERE "columnOne" = :columnOne_eq_0'
            ],
            'columnOne 2 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0', 'c-1-v-1']]],
                'SELECT count(*) FROM "ModelClass_1" WHERE ("columnOne" = :columnOne_eq_0 OR "columnOne" = :columnOne_eq_1)'
            ],
        ];
    }


    /**
     * @dataProvider providerCountStatement
     * @depends testReadTableConfig
     * @param $preFilterSet
     * @param $expectedQuery
     * @param Table $table
     */
    public function testCountStatement($preFilterSet, $expectedQuery, $table)
    {
        $preFilter = (new SqlFilter(ModelClass_1::class))->setFilterFromArray($preFilterSet);
        $table->config->tablePreFilter($preFilter);
        $count = $table->countStatement();
        $count = str_replace("\n", ' ', $count);
        $this->assertEquals($expectedQuery, $count);
    }
    public function providerCountParams()
    {
        return [
            'withoutPreFilter' => [
                [],
                []
            ],
            'columnOne 1 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0']]],
                [':columnOne_eq_0' => 'c-1-v-0']
            ],
            'columnOne 2 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0', 'c-1-v-1']]],
                [':columnOne_eq_0' => 'c-1-v-0', ':columnOne_eq_1' => 'c-1-v-1']
            ],
        ];
    }


    /**
     * @dataProvider providerCountParams
     * @depends testReadTableConfig
     * @param $preFilterSet
     * @param $expectedParams
     * @param Table $table
     */
    public function testCountParams($preFilterSet, $expectedParams, $table)
    {
        $preFilter = (new SqlFilter(ModelClass_1::class))->setFilterFromArray($preFilterSet);
        $table->config->tablePreFilter($preFilter);
        $countStatement = $table->countStatement();
        $countParams = $table->countParams();
        $this->assertEquals($expectedParams, $countParams);
    }

    public function providerCountAll()
    {
        return [
            'withoutPreFilter' => [
                [],
                12
            ],
            'columnOne 1 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0']]],
                4
            ],
            'columnOne 2 val' => [
                ['columnOne' => ['eq' => ['c-1-v-0', 'c-1-v-1']]],
                8
            ],
        ];

    }

    /**
     * @dataProvider providerCountAll
     * @depends testReadTableConfig
     * @param $preFilterSet
     * @param $expectedCount
     * @param Table $table
     * @internal param $expected
     */
    public function testCountAll($preFilterSet, $expectedCount, $table)
    {
        $preFilter = (new SqlFilter(ModelClass_1::class))->setFilterFromArray($preFilterSet);
        $table->config->tablePreFilter($preFilter);
        $this->assertEquals($expectedCount, $table->countAll());
    }

    public function providerPaginationUpdate()
    {
        return [
            '_1' => [[], ['curPage' => 1, 'rowsOnPage' => 'все'], ['curPage' => 1, 'rowsOnPage' => -1, 'numOfPages' => 1, 'numOfRecords' => 12]],
            '_2' => [[], ['curPage' => 12, 'rowsOnPage' => 'все'], ['curPage' => 1, 'rowsOnPage' => -1, 'numOfPages' => 1, 'numOfRecords' => 12]],
            '_3' => [[], ['curPage' => 1, 'rowsOnPage' => 1], ['curPage' => 1, 'rowsOnPage' => 1, 'numOfPages' => 12, 'numOfRecords' => 12]],
            '_4' => [[], ['curPage' => 12, 'rowsOnPage' => 1], ['curPage' => 12, 'rowsOnPage' => 1, 'numOfPages' => 12, 'numOfRecords' => 12]],
            '_5' => [[], ['curPage' => 13, 'rowsOnPage' => 1], ['curPage' => 1, 'rowsOnPage' => 1, 'numOfPages' => 12, 'numOfRecords' => 12]],
        ];
    }

    /**
     * @dataProvider providerPaginationUpdate
     * @depends testReadTableConfig
     * @param $preFilterSet
     * @param $input
     * @param $expected
     * @param Table $tb
     */
    public function testPaginationUpdate($preFilterSet, $input, $expected,  $tb)
    {
        $preFilter = (new SqlFilter(ModelClass_1::class))->setFilterFromArray($preFilterSet);
        $tb->config->tablePreFilter($preFilter);
        $tb->paginationUpdate($input['curPage'], $input['rowsOnPage']);

        $this->assertEquals($expected['curPage'], $tb->currentPage());
        $this->assertEquals($expected['rowsOnPage'], $tb->rowsOnPage());
        $this->assertEquals($expected['numOfPages'], $tb->numberOfPages());
        $this->assertEquals($expected['numOfRecords'], $tb->numberOfRecords());
    }
}