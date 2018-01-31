<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Connection;
use T4\Dbal\IDriver;


/**
 * Class Table
 * @package App\Components\Tables
 *
 * @property TableConfig $config
 * @property SqlFilter $filter
 * @property SqlFilter $mergedFilter
 * @property Std $pagination
 * @property Std calculatedColumnFilters
 * @property IDriver $driver
 */
class Table extends Std
    implements TableInterface
{
    protected $paginationTemplate = [
        'currentPage' => 1,
        'rowsOnPage' => -1,
        'numberOfPages' => 0,
        'totalRecords' => 0
    ];

    public function __construct(TableConfig $tableConfig)
    {
        parent::__construct();

        if (empty($tableConfig->className)) {
            throw new Exception('Table configuration isn\'t valid');
        }
        $this->config = $tableConfig;
        $this->filter = new SqlFilter($this->config->className());
        $this->driver = $this->config->className()::getDbDriver();
        $this->pagination = new Std($this->paginationTemplate);
        $this->calculatedColumnFilters = new Std();
    }

    public static function buildConfig(string $tableName)
    {
        if (TableConfig::isPivotTableConfig($tableName)) {
            $tbConf = (new PivotTable(new PivotTableConfig($tableName)))
                ->buildTableConfig();
        } else {
            $tbConf = (new Table(new TableConfig($tableName)))
                ->buildTableConfig();
        }
        $tbConf->tableName = $tableName;
        return $tbConf;
    }

    public static function getTableConfig($tableName)
    {
        if (TableConfig::isPivotTableConfig($tableName)) {
            return new PivotTableConfig($tableName);
        } else {
            return new TableConfig($tableName);
        }
    }
    public static function getTable(TableConfig $config)
    {
        if ($config instanceof PivotTableConfig) {
            return new PivotTable($config);
        } else {
            return new Table($config);
        }
    }

    public function getBodyFooterTable()
    {
        try {
            return self::getTable(self::getTableConfig($this->config->bodyFooterTableName()));
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return Std
     * return json config for jqTable script
     */
    public function buildTableConfig()
    {
        $tbConf = new Std();
        $tbConf->dataUrl = $this->config->dataUrl();
        $tbConf->width = $this->config->tableWidth();
        $tbConf->header = new Std();
        $tbConf->header->columns = new Std();
        $tbConf->header->lowerColumns = new Std();
        $tbConf->header->tableClasses = implode(', ', $this->config->headerCssClasses->toArray());
        foreach ($this->config->columns as $col => $colConf) {
            if ($this->config->isColumnVisible($col)) {
                $tbConf->header->columns->$col = $colConf;
            }
        }
        foreach ($this->config->lowerColumns as $col => $colConf) {
            if ($this->config->isColumnVisible($col)) {
                $tbConf->header->lowerColumns->$col = $colConf;
            }
        }
        $tbConf->pager = new Std(
            [
                'rowsOnPage' => $this->rowsOnPage(),
                'rowList' => $this->config->rowsOnPageList()->toArray()
            ]
        );
        $tbConf->styles = new Std(
            [
                'header' => [
                    'table' => [
                        'classes' => [],
                    ]
                ],
                'body' => [
                    'table' => [
                        'classes' => [],
                    ]
                ],
            ]
        );
        $tbConf->styles->header->table->classes = $this->config->headerCssClasses->table;
        $tbConf->styles->body->table->classes = $this->config->bodyCssClasses->table;


        return $tbConf;
    }

    public function buildJsonTableConfig()
    {
        return json_encode($this->buildTableConfig()->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function columnsConfig(): Std
    {
        return $this->config->columns();
    }

    public function columnsNames(): array
    {

    }

    public function columnsTitles(): array
    {
        // TODO: Implement columnsTitles() method.
    }

    /**
     * @param int|null $limit
     * @param int|null $offset
     * @param string|null $class
     * @param bool $distinct
     * @return mixed return set of records (like array or Collection?)
     * @throws Exception return set of records (like array or Collection?)
     */
    public function getRecords(int $limit = null, int $offset = null,  string $class = null, $distinct = false)
    {
        if (! is_null($class) && ! class_exists($class)) {
            throw new Exception('getRecords: class name isn\'t valid');
        }
        $sql = $this->selectStatement($offset, $limit, $distinct);
        $params = $this->selectParams();
        $queryRes = $this->config->connection()->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        if (! is_null($class)) {
            foreach ($queryRes as $key => $val) {
                $queryRes[$key] = new $class($queryRes[$key]);
            }
        }
        return $queryRes;
    }

    public function getRecordsByPage(int $pageNumber = null, string $class = null, $distinct = false)
    {
        $pageNumber = is_null($pageNumber) ? $this->currentPage() : $this->currentPageSanitize($pageNumber);
        return $this->getRecords($this->rowsOnPage(), ($pageNumber-1) * $this->rowsOnPage(), $class, $distinct);
    }

    /**
     * @param int|null $pageNumber
     * @return int|self
     * get/set current page number
     */
    public function currentPage(int $pageNumber = null)
    {
        if (is_null($pageNumber)) {
            return $this->pagination->currentPage;
        }
        if ($pageNumber > 0) {
            $this->pagination->currentPage =
                ($pageNumber > $this->pagination->numberOfPages) ?
                $this->pagination->numberOfPages : $pageNumber;
        }
        return $this;
    }

    /**
     * @param int|null $rows
     * @return int|self
     * get/set rows per page
     */
    public function rowsOnPage(int $rows = null)
    {
        if (is_null($rows)) {
            return $this->pagination->rowsOnPage;
        }
        $this->pagination->rowsOnPage = $this->rowsOnPageSanitize($rows);
        return $this;
    }

    /**
     * @return int number of pages that were calculated with paginationUpdate()
     */
    public function numberOfPages()
    {
        return $this->pagination->numberOfPages;
    }

    public function numberOfRecords()
    {
        return $this->pagination->totalRecords;
    }

    /**
     * @param null $currentPage
     * @param null $rowsOnPage
     * @return $this
     */
    public function paginationUpdate($currentPage = null, $rowsOnPage = null)
    {

        $pagination = $this->pagination;
        $pagination->totalRecords = $this->countAll();
        $pagination->rowsOnPage = is_null($rowsOnPage) ? $pagination->rowsOnPage : $this->rowsOnPageSanitize($rowsOnPage);
        $pagination->numberOfPages = $pagination->rowsOnPage < 0 ? 1 : (int)ceil($pagination->totalRecords / $pagination->rowsOnPage);

        $pagination->currentPage = is_null($currentPage) ? $pagination->currentPage : $this->currentPageSanitize($currentPage);
        $pagination->currentPage = $pagination->currentPage > $pagination->numberOfPages ? 1 : $pagination->currentPage;
        return $this;
    }

    /**
     * @return Std return current sort order
     */
    public function currentSortOrder()
    {
        return $this->config->sortBy();
    }

    public function setSortOrder(string $columnName, $direction)
    {
        $this->config->sortBy($columnName, $direction);
    }

    /**
     * @param SqlFilter $filter
     * @param $appendMode - 'replace', 'append' or 'ignore'
     * @return mixed
     * add operation filter. It doesn't  save in config. and can't rewrite table's preFilter
     *
     */
    public function addFilter(SqlFilter $filter, string $appendMode)
    {
        $this->filter->mergeWith($filter, $appendMode);
        return $this;
    }

    public function removeFilter(SqlFilter $filter)
    {
        $this->filter->subtractFilter($filter);
    }

    public function clearFilters()
    {
       $this->filter = new SqlFilter($this->config->className());
    }

    /**
     * @param string $column
     * @param string $method
     * @return mixed
     *
     * calculate by column using $method. Useful for gather stat info per column
     */
    public function calculateByColumn(string $column, string $method)
    {
        // TODO: Implement calculateByColumn() method.
    }


    public function selectStatement(int $offset = null, int $limit = null, $distinct = false)
    {
        $table = $this->driver->quoteName($this->config->className()::getTableName());
        $columns = $this->config->columns()->toArray();
        $columns = array_diff($columns, $this->config->extraColumns->toArray());
        foreach ($columns as $key => $col) {
            $columns[$key] = $this->driver->quoteName($col);
        }
        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');

        $columns = implode(', ', $columns);
        $whereStatement = $this->mergedFilter->filterStatement;
        $orderBy = $this->config->sortByQuotedString();

        $sql = 'SELECT ' . $columns . "\n";
        $sql .= 'FROM ' . $table . "\n";
        $sql .= (empty($whereStatement)) ? '' : 'WHERE ' . $whereStatement . "\n";
        $sql .= 'ORDER BY ' . $orderBy . "\n";
        $sql .= is_numeric($offset) ? 'OFFSET ' . $offset : '';
        $sql .= is_numeric($limit) ? 'LIMIT ' . $limit : '';
        return $sql;
    }


    public function countAll()
    {
        $query = $this->countStatement();
        $params = $this->countParams();
        $res = $this->countByQuery($query, $params);
        return $res;
    }

    public function countStatement()
    {
        $table = $this->driver->quoteName($this->config->className()::getTableName());

        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');

        $whereStatement = $this->mergedFilter->filterStatement;

        $sql = 'SELECT count(*)' . "\n";
        $sql .= 'FROM ' . $table;
        $sql .= (empty($whereStatement)) ? '' : "\n" . 'WHERE ' . $whereStatement;
        return $sql;
    }

    public function countParams()
    {
        return $this->mergedFilter->filterParams;
    }

    public function selectParams()
    {
        return $this->mergedFilter->filterParams;
    }

    protected function selectAllByQuery($sql, $param)
    {
        /**
         * @var Connection $conn
         */
        $conn = $this->config->connection();
        $res = $conn->query($sql, $param)->fetchAllObjects(Std::class);
    }
    protected function countByQuery($sql, $param)
    {
        /**
         * @var Connection $conn
         */
        $conn = $this->config->connection();
        $res = $conn->query($sql, $param)->fetchScalar();
        return $res;
    }
    protected function rowsOnPageSanitize($val)
    {
        if (is_int($val) && $val > 0) {
            return $val;
        } elseif (is_numeric($val) && (int)$val > 0) {
            return (int)$val;
        }  elseif (is_numeric($val) && (int)$val == -1) {
            return (int)$val;
        } elseif (mb_strtolower($val) == 'все') {
            return -1;
        }
        throw new Exception('Exception in rowsOnPageSanitize: Incorrect value of RowsOnPage variable');
    }
    protected function currentPageSanitize($val)
    {
        if (is_int($val) && $val > 0) {
            return $val;
        } elseif (is_numeric($val) && (int)$val > 0) {
            return (int)$val;
        } else {
            return 1;
        }
    }
}