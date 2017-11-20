<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Exception;
use T4\Core\Std;

class Table extends Std
    implements TableInterface
{
    /**
     * @var TableConfigInterface $config
     */
    protected $config;
    protected $filter;

    public function __construct(TableConfigInterface $tableConfig)
    {
        parent::__construct();

        if (empty($tableConfig->className)) {
            throw new Exception('Table configuration isn\'t valid');
        }
        $this->config = $tableConfig;
        $this->filter = new SqlFilter($this->config->className());
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
        // TODO: Implement getRecords() method.
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
        // TODO: Implement currentPageNumber() method.
    }

    /**
     * @param int|null $rows
     * @return mixed
     * get/set rows per page
     */
    public function rowsOnPage(int $rows = null)
    {
        // TODO: Implement rowsOnPage() method.
    }

    /**
     * @return array return current sort order
     */
    public function currentSortOrder(): array
    {
        // TODO: Implement currentSortOrder() method.
    }

    public function setSortOrder(string $columnName, $direction)
    {
        // TODO: Implement setSortOrder() method.
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
        // TODO: Implement addFilter() method.
    }

    public function removeFilter(SqlFilter $filter)
    {
        // TODO: Implement removeFilter() method.
    }

    public function clearFilters()
    {
        // TODO: Implement clearFilters() method.
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


    protected function selectStatement($offset = null, $limit = null)
    {
        $columns = array_keys($this->config->columns()->toArray());
        $filter = $this->config->tablePreFilter()->mergeWith($this->filter, 'ignore');
        $filterStatement = $filter->filterStatement;
        $filterParams = $filter->filterParams;
        $sortOrder = $this->config->getSortOrder()->toArray();

    }

    protected function selectParams()
    {

    }
}