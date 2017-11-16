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
    protected $tableConf;
    protected function setUp()
    {
        /**
         * @var \T4\Tests\Orm\Models\Model $class
         */
        $className = ModelClass_1::class;
        $sortOrder = [
            'columnOne' => ['columnOne' => '', 'columnThree' => '']
        ];
        $sortBy = 'columnOne';
        $sortDirect = 'asc';
        $classColumns = $className::getColumns();
        $tableColumns = [
            'columnOne',
            'columnTwo',
            'columnThree'
        ];
        $columnConfig = [
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
        $this->tableConf = new TableConfig('test', $className);
        $this->tableConf->columns($tableColumns);
        $preFilter = new SqlFilter($className);
        $preFilter->setFilterFromArray([
            'columnOne' => ['eq' => ['val_1']]
        ]);
        $this->tableConf->tablePreFilter($preFilter);
        $this->tableConf->sortBy($sortBy, $sortDirect);
    }
    public function testReadTableConfig()
    {

        $this->assertInstanceOf(TableConfig::class, $this->tableConf);
    }
}