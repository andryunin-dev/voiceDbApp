<?php

namespace App\Components\Tables;

use T4\Core\Config;
use T4\Core\Exception;

class TableConfig extends Config
{
    const BASE_CONF_PATH = ROOT_PATH . DS . 'TablesConfigs' . DS;

    protected $driver;
    protected $table;

    /**
     * TableConfig constructor.
     * @param string $table
     * @throws Exception
     */
    public function __construct(string $table)
    {
        if (empty($table)) {
            throw new Exception('Table name can not be empty' );
        }
        parent::__construct(self::BASE_CONF_PATH . $table . '.php');
        $this->table = $table;
    }

    public function validateWidth($val)
    {
        $val = strtolower(trim($val));
        if(filter_var($val, FILTER_VALIDATE_INT)) {
            return true;
        } elseif ('px' == substr($val, -2)) {
            return true;
        } else {
            return false;
        }
    }

}