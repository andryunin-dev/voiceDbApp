<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\IDriver;


class Table extends Std
    implements TableInterface
{
    /**
     * @var TableConfigInterface $config
     */
    protected $config;
    /**
     * @var SqlFilter $filter - operational filter that can be set by user
     */
    protected $filter;
    /**
     * @var SqlFilter $mergedFilter - calculated filter after merge preFilter and operational filter
     */
    protected $mergedFilter;

    protected $paginationSettings = [
        'currentPage' => 1,
        'rowsOnPage' => -1,
        'numberOfPages' => 0,

    ];
    /**
     * @var IDriver $driver
     */
    protected $driver;

    public function __construct(TableConfigInterface $tableConfig)
    {
        parent::__construct();

        if (empty($tableConfig->className)) {
            throw new Exception('Table configuration isn\'t valid');
        }
        $this->config = $tableConfig;
        $this->filter = new SqlFilter($this->config->className());
        $this->driver = $this->config->className()::getDbDriver();
        $this->paginationSettings = new Std($this->paginationSettings);
    }

    /**
     * @return TableConfigInterface return entire table config
     * return entire table config
     */
    public function tableConfig(): TableConfigInterface
    {
        return $this->config;
    }

    public function columnsConfig(): Std
    {
        $colConf = $this->config->allColumnsConfig()->toArray();
        return new Std($colConf);
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
     * @return mixed
     * get/set current page number
     */
    public function currentPageNumber(int $pageNumber = null)
    {
        if (! is_null($pageNumber) && $pageNumber > 0) {
            $this->paginationSettings->currentPage = ($pageNumber > $this->paginationSettings->numberOfPages) ?
                $this->paginationSettings->numberOfPages : $pageNumber;
        }
        return $this->paginationSettings->currentPage;
    }

    /**
     * @param int|null $rows
     * @return mixed
     * get/set rows per page
     */
    public function rowsOnPage(int $rows = null)
    {
        if (! is_null($rows) && $rows > 0) {
            $this->paginationSettings->rowsOnPage = $rows;
        }
        return $this->paginationSettings->rowsOnPage;
    }

    public function numberOfPages($calculateNow = false)
    {
        if (true === $calculateNow) {
            //Todo calculate number of pages.
        }
        return $this->paginationSettings->numberOfPages;
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
        $columns = array_keys($this->config->columns()->toArray());
        foreach ($columns as $key => $col) {
            $columns[$key] = $this->driver->quoteName($col);
        }
        $this->mergedFilter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');
        $filterStatement = $this->mergedFilter->filterStatement;
        $sql = 'SELECT ' . implode(', ', $columns) . "\n";
        $sql .= 'FROM ' . $table . "\n";
        $sql .= (empty($filterStatement)) ? '' : 'WHERE ' . $filterStatement . "\n";
        $sql .= 'ORDER BY ' . $this->config->getSortOrderAsQuotedString() . "\n";
        $sql .= is_numeric($offset) ? 'OFFSET ' . $offset : '';
        $sql .= is_numeric($limit) ? 'LIMIT ' . $limit : '';
        return $sql;
    }

    public function selectParams()
    {
        return $this->mergedFilter->filterParams;
    }





}