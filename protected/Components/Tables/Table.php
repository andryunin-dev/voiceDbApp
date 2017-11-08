<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Std;

class Table extends Std
{
    public function __construct(string $tableName)
    {
        parent::__construct();
    }



    public function addFilter(SqlFilter $filter)
    {}

    public function clearFilters()
    {}

    /**
     * set sort order for table
     * @param string $columnName
     * @param $direction
     */
    protected function setSortOrder(string $columnName, $direction)
    {}

    /**
     * get records
     *
     * @param int $limit
     * @param int $offset
     */
    public function getRecords(int $limit, int $offset)
    {}

    /**
     * return set of records according pagination settings
     * before calling this method set currentPageNumber
     */
    protected function getCurrentPage()
    {}

    /**
     * @param int $number
     */
    protected function setCurrentPageNumber(int $number)
    {}

    /**
     * return current page number
     */
    protected function getCurrentPageNumber()
    {}

    /**
     * set rows per page. Current page number reset to 1
     * @param int $rows
     */
    protected function setRowsOnPage(int $rows)
    {}
}