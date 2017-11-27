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
 * @property IDriver $driver
 */
class Table extends Std
    implements TableInterface
{
    protected $paginationTemplate = [
        'currentPage' => 1,
        'rowsOnPage' => -1,
        'numberOfPages' => 0,
    ];

    public function __construct(TableConfigInterface $tableConfig)
    {
        parent::__construct();

        if (empty($tableConfig->className)) {
            throw new Exception('Table configuration isn\'t valid');
        }
        $this->config = $tableConfig;
        $this->filter = new SqlFilter($this->config->className());
        $this->driver = $this->config->className()::getDbDriver();
        $this->pagination = new Std($this->paginationTemplate);
    }

    /**
     * @return TableConfigInterface return entire table config
     * return entire table config
     */
    public function tableConfig(): TableConfigInterface
    {
        return $this->config;
    }

    /**
     * @return Std
     * return json config for jqTable script
     */
    public function buildTableConfig()
    {
        $jsConf = new Std();
        $jsConf->dataUrl = $this->config->dataUrl();
        $jsConf->width = $this->config->tableWidth();
        $jsConf->header = new Std();
        $jsConf->header->tableClasses = implode(', ', $this->config->headerCssClasses->toArray());
        $jsConf->header->columns = $this->config->getAllColumnsConfig();
        $jsConf->pager = new Std(
            [
                'rowsOnPage' => $this->rowsOnPage(),
                'rowList' => $this->config->rowsOnPageList->toArray()
            ]
        );
        $jsConf->styles = new Std(
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
        $jsConf->styles->header->table->classes = $this->config->headerCssClasses->table;
        $jsConf->styles->body->table->classes = $this->config->bodyCssClasses->table;

        return $jsConf;
    }

    public function buildJsonTableConfig()
    {
        return json_encode($this->buildTableConfig()->toArray(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function columnsConfig(): Std
    {
        return $this->config->getAllColumnsConfig();
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
     * @return mixed
     *
     * return set of records (like array or Collection?)
     */
    public function getRecords(int $limit = null, int $offset = null)
    {
        $sql = $this->selectStatement($offset, $limit);
        $params = $this->selectParams();
        $queryRes = $this->config->className()::getDbConnection()->query($sql, $params)->fetchAll(\PDO::FETCH_COLUMN, 0);
        return$queryRes;
    }

    public function getRecordsByPage(int $pageNumber)
    {
        // TODO: Implement getRecordsByPage() method.
    }

    /**
     * @param int|null $pageNumber
     * @return int|self
     * get/set current page number
     */
    public function currentPageNumber(int $pageNumber = null)
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
        if ($rows > 0) {
            $this->pagination->rowsOnPage = $rows;
            $this->updatePagination();
        }
        return $this;
    }

    public function numberOfPages($updateNow = true)
    {
        if (true === $updateNow) {
            $this->updatePagination();
        }
        return $this->paginationSettings->numberOfPages;
    }

    public function updatePagination()
    {
        //Todo calculate pagination
        return $this;
    }

    /**
     * @return array return current sort order
     */
    public function currentSortOrder(): array
    {
        return $this->config->getSortOrder();
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


    public function selectStatement($offset = null, $limit = null)
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
        $orderBy = $this->config->getSortOrderAsQuotedString();

        $sql = 'SELECT ' . $columns . "\n";
        $sql .= 'FROM ' . $table . "\n";
        $sql .= (empty($whereStatement)) ? '' : 'WHERE ' . $whereStatement . "\n";
        $sql .= 'ORDER BY ' . $orderBy . "\n";
        $sql .= is_numeric($offset) ? 'OFFSET ' . $offset : '';
        $sql .= is_numeric($limit) ? 'LIMIT ' . $limit : '';
        return $sql;
    }

    public function countStatement()
    {
        $table = $this->driver->quoteName($this->config->className()::getTableName());

        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');

        $whereStatement = $this->mergedFilter->filterStatement;

        $sql = 'SELECT count(*)' . "\n";
        $sql .= 'FROM ' . $table . "\n";
        $sql .= (empty($whereStatement)) ? '' : 'WHERE ' . $whereStatement;
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
        $conn = $this->config->className::getDbConnection();
        $res = $conn->query($sql, $param)->fetchAllObjects(Std::class);
    }
    protected function countByQuery($sql, $param)
    {
        /**
         * @var Connection $conn
         */
        $conn = $this->config->className::getDbConnection();
        $res = $conn->query($sql, $param)->fetchScalar();
        return $res;
    }
}