<?php

namespace App\Components\Tables;

use T4\Core\Config;

class TableConfig extends Config
{
    const CONF_PATH = ROOT_PATH . DS . 'Configs' . DS . 'tables.php';

    protected $driver;
    protected $table;

    /**
     * TableConfig constructor.
     * @param string $table
     */
    public function __construct(string $table)
    {
        parent::__construct();
        $this->$table = (new Config(self::CONF_PATH))->$table;
    }
}