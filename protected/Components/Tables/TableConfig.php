<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\IDriver;
use T4\Orm\Model;

class TableConfig extends Config implements TableConfigInterface
{
    const BASE_CONF_PATH = ROOT_PATH . DS . 'Configs' . DS;

    protected $tablePropertiesTemplate = [
        'className' => '',
        'sortOrderSets' => [],
        'sortBy' => [],
        'preFilter' => [],
        'pagination' => ['rowsPerPageList' => []]
    ];
    protected $columnPropertiesTemplate = [
        'id' => '',
        'title' => '',
        'width' => 0,
        'sortable' => false,
        'filterable' => false
    ];

    /**
     * TableConfig constructor.
     * @param string $tableName
     * @param string|null $class
     * @throws Exception
     *
     * if $class doesn't set - read config. If config isn't exists - Exception
     * if $class is set and valid - create new config but not save it.
     */
    public function __construct(string $tableName, string $class = null)
    {
        parent::__construct();
        if (empty($tableName)) {
            throw new Exception('Table name can not be empty');
        }
        $path = self::BASE_CONF_PATH . $tableName;
        /* if class is not set try to load existing config */
        if (empty($class)) {
            $conf = (new Config($path));
            $this->setPath($conf->getPath());
            $conf = $conf->toArray();
            foreach ($conf as $prop => $val) {
                $this->$prop = (is_array($val)) ? new Std($val) : $val;
            }
        } elseif (! class_exists($class)) {
            throw new Exception('Class ' . $class . ' is not exists');
        } elseif (get_parent_class($class) != Model::class) {
            throw new Exception('Class for table must extends Model class');
        } else {
            $this->setPath($path);
            foreach ($this->tablePropertiesTemplate as $prop => $value) {
                $value = (is_array($value)) ? new Std($value) : $value;
                $this->$prop = $value;
            }
            $this->className = $class;
        }
    }

    public function className()
    {
        return $this->className;
    }

    /**
     * @param array $columns only columns names
     * All columns names have to belong a class that specified in construct method
     * @return Config  return columns Config object
     *
     * if $columns is null - just return columns Config object for current table
     * if $columns is array - set columns from this array for current table
     * this method should be called first
     * @throws Exception
     */
    public function columns(array $columns = null)
    {
        if (! is_null($columns)) {
            $classColumns = array_keys($this->className::getColumns());
            $diff = array_diff($columns, $classColumns);
            if (count($diff) > 0) {
                throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table!');
            }
            $columns = array_fill_keys($classColumns, $this->columnPropertiesTemplate);
            $this->columns = new Config($columns);
        }
        return $this->columns;
    }

    /**
     * @return Config
     * return columns config
     */
    public function allColumnsConfig() :Config
    {
        return $this->columns;
    }

    /**
     * @param string $column
     * @param Std|null $config
     * @return Config if $config is null - return current config $column column
     * if $config is null - return current config $column column
     * if $config is Std - set config for $column column
     * @throws Exception
     */
    public function columnConfig(string $column, Std $config = null)
    {
        $this->isColumnSet($column);
        if (is_null($config)) {
            return $this->columns->$column;
        }
        $diff = array_diff(array_keys($config->toArray()), array_keys($this->columnPropertiesTemplate));
        if (count($diff) > 0) {
            throw new Exception('Some config parameters are not correct');
        }
        foreach ($config as $param => $value) {
            $this->validateConfigParam($param, $value);
            $config->$param = $this->sanitizeConfigParam($param, $value);
        }
        foreach ($config as $param => $value) {
            $this->columns->$column->$param = $value;
        }
        return $this->columns->$column;
    }
    /**
     * define sets of columns to sort table
     * ['template_name/column_name' => ['column_1 => 'direction', 'column_N' => 'direction']]
     * You can pass several templates in one array like this:
     * [
     *      'column_1 => 'direction', 'column_N' => 'direction'],
     *      'column_N => 'direction', 'column_N' => 'direction'],
     * ]
     * To set template as current sort order use method 'sortBy'
     * if template already exists, it'll be overwritten
     * if direction is set, it can't be overwritten with sortBy method
     *
     * @param array|null $template
     * @return Config
     */
    public function sortOrderSets(array $template = null)
    {
        foreach ($template as $name => $columns) {
            foreach ($columns as $col => $dir) {
                $this->validateColumnName($col);
                $this->validateSortDirection($dir);
            }
        }
        foreach ($template as $templName => $columns) {
            $this->sortOrderSets->$templName = new Config($columns);
        }
        return $this->sortOrderSets;
    }

    /**
     * @param string $sortTemplate
     * @param string $direction
     *
     * This method define default sort order for table. This order will be saved with save() method
     * if $sortTemplate exists as set in sortOrderSets - apply this set
     * if not - tread $sortTemplate as column.
     * @return Config
     * @throws Exception
     */
    public function sortBy(string $sortTemplate, string $direction = '')
    {
        $this->validateSortDirection($direction);
        if (isset($this->sortOrderSets->$sortTemplate)) {
            $this->sortBy = new Config($this->sortOrderSets->$sortTemplate->toArray());
            foreach ($this->sortBy as $col => $dir) {
                $this->sortBy->$col = empty($dir) ? $direction : $dir;
            }
        } elseif ($this->isColumnSortable($sortTemplate)) {
            $this->sortBy->$sortTemplate = empty($this->sortBy->$sortTemplate) ? $direction : $this->sortBy->$sortTemplate;
        } else {
            throw new Exception('Column ' . $sortTemplate . ' can\' be used as sort column because it\'s not defined for this table or not set as sortable');
        }
        return $this->sortBy;
    }

    public function getSortOrder()
    {
        return $this->sortBy;
    }

    public function getSortOrderAsQuotedString()
    {
        /**
         * @var IDriver $drv
         */
        $drv = $this->className::getDbDriver();
        $res = [];
        foreach ($this->sortBy as $col => $dir) {
            $dir = empty($dir) ? '' : ' ' . strtoupper($dir);
            $res[] =  $drv->quoteName($col) . $dir;
        }
        return implode(', ', $res);
    }

    /**
     * @param SqlFilter $preFilter
     * get/set table preFilter
     * if preFilter already exists, it'll be overwritten
     * preFilter can not be overwritten any operations filter
     * @return SqlFilter
     */
    public function tablePreFilter(SqlFilter $preFilter = null)
    {
        if (! is_null($preFilter)) {
            $this->preFilter = new Config($preFilter->toArray());
        }
        return (new SqlFilter($this->className()))->setFilterFromArray($this->preFilter->toArray());
    }

    public function isColumnSet($column) :bool
    {
        return isset($this->columns->$column);
    }

    public function isColumnSortable($column) :bool
    {
        return isset($this->columns->$column) && (true === $this->columns->$column->sortable);
    }

    /**
     * @param array|null $variantsList
     * @return Std
     */
    public function rowsPerPageList(array $variantsList = null)
    {
        if (! is_null($variantsList)) {
            $this->pagination->rowsPerPageList = new Std($variantsList);
        }
        return $this->pagination->rowsPerPageList;
    }
    /*====================================
        PROTECTED METHODS
    ======================================*/
    protected function isAllColumnsSet(array $columns)
    {
        $classColumns = array_keys($this->className::getColumns());
        $diff = array_diff($columns, $classColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table!');
        }
        return true;
    }
    protected function validateColumnName(string $columns)
    {
        $classColumns = array_keys($this->className::getColumns());
        if (! in_array($columns, $classColumns)) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table!');
        }
        return true;
    }

    protected function validateSortDirection($direct)
    {
        $direct = strtolower($direct);
        if ('asc' == $direct || 'desc' == $direct || '' == $direct) {
            return true;
        } else {
            throw new Exception('Allowed sort direction is: \'asc\' or \'desc\'');
        }
    }

    protected function validateConfigParam($param, $val)
    {
        switch($param) {
            case 'id':
                if (! is_string($val)) {
                    throw new Exception('Not valid id');
                }
                break;
            case 'title':
                if (! is_string($val)) {
                    throw new Exception('Not valid title');
                }
                break;
            case 'width':
                if(is_numeric($val)) {
                    //width set in percents
                    return true;
                } elseif(is_string($val) && substr(trim(strtolower($val)), -2) == 'px') {
                    $val = substr($val, 0, strlen($val) - 2);
                    if (is_numeric($val)) {
                        return true;
                    }
                }
                throw new Exception('Invalid width value');
                break;
            case 'sortable':
                if (! is_bool($val)) {
                    throw new Exception('Invalid sortable value');
                }
                break;
            case 'filterable':
                if (! is_bool($val)) {
                    throw new Exception('Invalid filterable value');
                }
                break;
            default:
                throw new Exception('Unknown parameter \'' . $param . '\'');
        }
        return true;
    }

    protected function sanitizeConfigParam($param, $val)
    {
        switch($param) {
            case 'id':
                return trim($val);
                break;
            case 'title':
                return $val;
                break;
            case 'width':
                if(is_numeric($val)) {
                    //width set in percents
                    return intval($val);
                } elseif(is_string($val) && substr(trim(strtolower($val)), -2) == 'px') {
                    return trim(strtolower($val));
                } else {
                    return $val;
                }
                break;
            case 'sortable':
                return $val;
                break;
            case 'filterable':
                return $val;
                break;
            default:
                return $val;
        }
    }
}