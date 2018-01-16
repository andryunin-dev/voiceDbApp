<?php

namespace App\Components\Tables;

use App\Components\Sql\SqlFilter;
use T4\Core\Config;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Connection;
use T4\Dbal\IDriver;
use T4\Mvc\Application;
use T4\Orm\Model;

/**
 * Class TableConfig
 * @package App\Components\Tables
 *
 * @property Connection $connection
 * @property string $className
 * @property Std $sizes has property 'width' and 'height'
 * @property Std $columns set of columns with properties
 * @property Std $calculated calculated columns
 * @property Std $sortOrderSets
 * @property Std $sortBy
 * @property Std $preFilter
 * @property Std $pagination
 * @property Std $rowsOnPageList
 * @property Std $cssStyles
 * @property Std $headerCssClasses
 * @property Std $bodyCssClasses
 * @property Std $footerCssClasses
 */
class TableConfig extends Config implements TableConfigInterface
{
    const BASE_CONF_PATH = ROOT_PATH . DS . 'Configs' . DS;

    protected $tablePropertiesTemplate = [
        'dataUrl' => '',
        'connection' => '',
        'className' => '',
        'columns' => [],
        'bodyFooterColumns' => [],
        'calculated' => [],
        'aliases' => [],
        'extraColumns' => [],
        'bodyFooterExtraColumns' => [],
        'sortOrderSets' => [],
        'sortBy' => [],
        'preFilter' => [],
        'pagination' => ['rowsOnPageList' => []],
        'cssStyles' => [
            'header' => ['table' => []],
            'body' => ['table' => []],
            'bodyFooter' => ['table' => []],
            'footer' => ['table' => []],
        ],
        'sizes' => [
            'width' => '',
            'height' => ''
        ]
    ];
    protected $columnPropertiesTemplate = [
        'id' => '',
        'name' => '',
        'width' => 0,
        'sortable' => false,
        'filterable' => false,
        'visible' => true
    ];
    protected $calculatedColumnProperties = [
        'column' => '',
        'method' => 'count'
    ];


    protected static $sqlOperators = ['eq', 'ne', 'lt', 'le', 'gt', 'ge', 'isnull', 'notnull'];
    protected static $unarySqlOperators = [
        'isnull',
        'notnull'
    ];
    protected static $sqlMethods = ['count', 'sum'];

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
        $path = static::BASE_CONF_PATH . $tableName . '.php';
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

    public function dataUrl($url = null)
    {
        //Todo validate URL without http
        if (is_null($url)) {
            return $this->dataUrl;
        }
        //$url = filter_var($url, FILTER_VALIDATE_URL);
        if (false === $url) {
            throw new Exception('Invalid URL');
        }
        $this->dataUrl = $url;
        return $this;
    }

    public function connection($connectionName = null)
    {
        if (is_null($connectionName)) {
            if (empty($this->connection)) {
                return $this->className()::getDbConnection();
            } else {
                $this->className()::setConnection($this->connection);
                $res = $this->className()::getDbConnection();
                return $res;
            }
        }
        $this->connectionNameValidate($connectionName);
        $this->connection = $connectionName;

        return $this;
    }

    public function tableWidth($width = null)
    {
        if (is_null($width)) {
            return $this->sizes->width;
        }
        $this->validateConfigParam('width', $width);
        $this->sizes->width = $this->sanitizeConfigParam('width', $width);
        return $this;
    }
    public function tableHeight($height = null)
    {
        if (is_null($height)) {
            return $this->sizes->height;
        }
        $this->validateConfigParam('height', $height);
        $this->sizes->height = $this->sanitizeConfigParam('height', $height);
        return $this;
    }

    /**
     * @param array $columns only columns names
     * @param array $extraColumns only columns names
     * All columns names have to belong a class that specified in construct method
     * @return self|Std  return columns Config object
     *
     * if $columns is null - return only list of columns as Std object (without config params)
     * if $columns is array - set columns from this array for current table
     * this method should be called first
     * @throws Exception
     */
    public function columns(array $columns = null, array $extraColumns = null)
    {
        /*if arg is null - return list of columns as Std*/
        if (is_null($columns)) {
            $res = array_keys($this->columns->toArray());
            return new Std($res);
        }
        $extraColumns = is_null($extraColumns) ? [] : $extraColumns;
        $classColumns = array_keys($this->className::getColumns());
        $unionColumns = array_merge($classColumns, $extraColumns);
        $diff = array_diff($columns, $unionColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table or is defined as extraColumns!');
        }
        $this->extraColumns = new Std($extraColumns);
        $columns = array_fill_keys($columns, $this->columnPropertiesTemplate);
        $this->columns = new Std($columns);
        return $this;
    }

    public function extraColumns()
    {
        $extraCols = array_unique(array_merge($this->extraColumns->toArray(), $this->bodyFooterExtraColumns->toArray()));
        return new Std($extraCols);
    }
    /**
     * @param array $columns only columns names
     * @param array $extraColumns only columns names
     * All columns names have to belong a class that specified in construct method
     * @return self|Std  return columns Config object
     *
     * if $columns is null - return only list of columns as Std object (without config params)
     * if $columns is array - set columns from this array for current table
     * this method should be called first
     * @throws Exception
     */
    public function bodyFooterColumns(array $columns = null, array $extraColumns = null)
    {
        /*if arg is null - return list of columns as Std*/
        if (is_null($columns)) {
            $res = array_keys($this->bodyFooterColumns->toArray());
            return new Std($res);
        }
        $extraColumns = is_null($extraColumns) ? [] : $extraColumns;
        $classColumns = array_keys($this->className::getColumns());
        $unionColumns = array_merge($classColumns, $extraColumns);
        $diff = array_diff($columns, $unionColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table or is defined as extraColumns!');
        }
        $this->bodyFooterExtraColumns = new Std($extraColumns);
        $columns = array_fill_keys($columns, $this->columnPropertiesTemplate);
        $this->bodyFooterColumns = new Std($columns);
        return $this;
    }

    /**
     * @param string $alias
     * @param string|null $column
     * @param string|null $method count or sum
     * @return self
     * @throws Exception
     */
    public function calculatedColumn(string $alias, string $column = null, string $method = null)
    {
        if(is_null($column) && is_null($method)) {
            if (! $this->isCalculated($alias)) {
                throw new Exception($alias . ' is not define as alias for calculated column');
            }
            return $this->calculated->$alias;
        }
        if (! $this->isColumnDefinedInClass($column)) {
            throw new Exception('Calculated column has to be one of class columns(properties)');
        }
        $this->sqlMethodValidate($method);
        $alias = is_null($alias) ? $column : $alias;
        $this->calculated->$alias = new Std($this->calculatedColumnProperties);
        $this->calculated->$alias->column = $column;

        return $this;
    }

    /**
     * @return Std
     */
    public function calculatedColumns()
    {
        return $this->calculated;
    }

    public function columnList()
    {
        return $this->columns();
    }

    /**
     * @param string $column
     * @param Std|null $config
     * @return self|Std if $config is null - return current config $column column
     * @throws Exception
     */
    public function columnConfig(string $column, Std $config = null)
    {
        $this->validateColumnIsDefined($column);
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
        return $this;
    }
    /**
     * @param string $column
     * @param Std|null $config
     * @return self|Std if $config is null - return current config $column column
     * @throws Exception
     */
    public function bodyFooterColumnConfig(string $column, Std $config = null)
    {
        $this->validateLowerColumnIsDefined($column);
        if (is_null($config)) {
            return $this->bodyFooterColumns->$column;
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
            $this->bodyFooterColumns->$column->$param = $value;
        }
        return $this;
    }

    /**
     * @param $column
     * @param $alias
     * @param string $operator
     * set alias for a column and sql operator
     * @return $this
     */
    public function appendColumnAlias(string $column, string $alias, string $operator = '')
    {
        $this->validateColumnIsDefined($column);
        if (! empty($operator)) {
            $this->validateSqlOperator($operator);
        }
        $this->aliases->$alias = new Std(['column' => $column, 'operator' => $operator]);
        return $this;
    }
    public function removeColumnAlias(string $alias)
    {
        if (isset($this->aliases->$alias)) {
            unset($this->aliases->$alias);
        }
    }
    /**
     * define sets of columns to sort table
     * ['template_name/column_name' => ['column_1 => 'direction', 'column_N' => 'direction']]
     * You can pass several templates in one array like this:
     * [
     *      'template_name/column_name' => ['column_1 => 'direction', 'column_N' => 'direction'],
     *      'template_name/column_name' => ['column_N => 'direction', 'column_N' => 'direction'],
     * ]
     * To set template as current sort order use method 'sortBy'
     * if template already exists, it'll be overwritten
     * if direction is set, it can't be overwritten with sortBy method
     *
     * @param array $template
     * @return self
     */
    public function sortOrderSets(array $template)
    {
        foreach ($template as $name => $columns) {
            foreach ($columns as $col => $dir) {
                $this->validateColumnName($col);
                $this->validateSortDirection($dir);
            }
        }
        foreach ($template as $templName => $columns) {
            $this->sortOrderSets->$templName = new Std($columns);
        }
        return $this;
    }


    /**
     * @param string $sortTemplate
     * @param string $direction
     *
     * This method define default sort order for table. This order will be saved with save() method
     * if $sortTemplate exists as set in sortOrderSets - apply this set
     * if not - tread $sortTemplate as column.
     * @return self|Std
     * @throws Exception
     */
    public function sortBy(string $sortTemplate = null, string $direction = '')
    {
        if (is_null($sortTemplate)) {
            return $this->sortBy;
        }
        $this->validateSortDirection($direction);
        if (isset($this->sortOrderSets->$sortTemplate)) {
            $this->sortBy = new Std($this->sortOrderSets->$sortTemplate->toArray());
            foreach ($this->sortBy as $col => $dir) {
                $this->sortBy->$col = empty($dir) ? $direction : $dir;
            }
        } elseif ($this->isColumnSortable($sortTemplate)) {
            $this->sortBy->$sortTemplate = empty($this->sortBy->$sortTemplate) ? $direction : $this->sortBy->$sortTemplate;
        } else {
            throw new Exception('Column ' . $sortTemplate . ' can\' be used as sort column because it\'s not defined for this table or not set as sortable');
        }
        return $this;
    }

    public function sortByQuotedString($table = null)
    {
        return $this->sortByToQuotedString($this->sortBy(), $table);
    }

    protected function sortByToQuotedString(Std $sortOrder, string $table = null)
    {
        /**
         * @var IDriver $drv
         */
        $drv = $this->className::getDbDriver();
        $table = is_null($table) ? '' : $drv->quoteName($table) . '.';
        $res = [];
        foreach ($sortOrder as $col => $dir) {
            $dir = empty($dir) ? '' : ' ' . strtoupper($dir);
            $res[] =  $table . $drv->quoteName($col) . $dir;
        }
        return implode(', ', $res);
    }

    /**
     * @param SqlFilter $preFilter
     * get/set table preFilter
     * if preFilter already exists, it'll be overwritten
     * preFilter can not be overwritten any operations filter
     * @return static|SqlFilter
     */
    public function tablePreFilter(SqlFilter $preFilter = null)
    {
        if (is_null($preFilter)) {
            return (new SqlFilter($this->className()))
                ->setFilterFromArray($this->preFilter->toArray());
        }
        $this->preFilter = new Std($preFilter->toArray());
        return $this;
    }

    public function isColumnDefined($column) :bool
    {
        return isset($this->columns->$column);
    }
    public function isLowerColumnDefined($column) :bool
    {
        return isset($this->bodyFooterColumns->$column);
    }
    public function isColumnDefinedInClass(string $column)
    {
        return in_array($column, array_keys($this->className::getColumns()));
    }

    public function isColumnSortable($column) :bool
    {
        return isset($this->columns->$column) && (true === $this->columns->$column->sortable);
    }

    public function isColumnVisible($column) :bool
    {
        return isset($this->columns->$column) && (true === $this->columns->$column->visible);
    }
    public function isBodyFooterColumnVisible($column) :bool
    {
        return isset($this->bodyFooterColumns->$column) && (true === $this->bodyFooterColumns->$column->visible);
    }

    /**
     * @param array|null $variantList
     * @return self
     */
    public function rowsOnPageList(array $variantList = null)
    {
        if (is_null($variantList)) {
            return $this->pagination->rowsOnPageList;
        }
        $this->pagination->rowsOnPageList = new Std($variantList);
        return $this;
    }
    public function cssAddHeaderTableClasses($cssClass = null)
    {
        return $this->innerAddCssClass('header', 'table', $cssClass);
    }
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssAddBodyTableClasses($cssClass)
    {
        return $this->innerAddCssClass('body', 'table', $cssClass);
    }
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssAddBodyFooterTableClasses($cssClass)
    {
        return $this->innerAddCssClass('bodyFooter', 'table', $cssClass);
    }
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssAddFooterTableClasses($cssClass)
    {
        return $this->innerAddCssClass('footer', 'table', $cssClass);
    }
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssSetHeaderTableClasses($cssClass)
    {
        return $this->innerSetCssClass('header', 'table', $cssClass);
    }
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssSetBodyTableClasses($cssClass)
    {
        return $this->innerSetCssClass('body', 'table', $cssClass);
    }
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for bodyFooter table
     */
    public function cssSetBodyFooterTableClasses($cssClass)
    {
        return $this->innerSetCssClass('bodyFooter', 'table', $cssClass);
    }
    /**
     * @param string|array $cssClass
     * @return self
     * add css class for header table
     */
    public function cssSetFooterTableClasses($cssClass)
    {
        return $this->innerSetCssClass('footer', 'table', $cssClass);
    }

    /*====================================
        PROTECTED METHODS
    ======================================*/
    protected function innerGetCssClass($tablePart, $tag)
    {
        return isset($this->cssStyles->$tablePart->$tag) ? $this->cssStyles->$tablePart->$tag : false;
    }
    protected function innerSetCssClass($tablePart, $tag, $classes)
    {
        if (is_string($classes)) {
            $classes = [$classes];
        } elseif (! is_array($classes)) {
            throw new Exception('cssAddTableClass: Invalid type of argument');
        }

        if (! isset($this->cssStyles->$tablePart->$tag))
        {
            throw new Exception('Incorrect target for CSS classes');
        }

        $this->cssStyles->$tablePart->$tag = new Std($classes);
        return $this;
    }
    protected function innerAddCssClass($tablePart, $tag, $classes)
    {
        if (is_string($classes)) {
            $classes = [$classes];
        } elseif (! is_array($classes)) {
            throw new Exception('cssAddTableClass: Invalid type of argument');
        }

        if (! isset($this->cssStyles->$tablePart->$tag))
        {
            throw new Exception('Incorrect target for CSS classes');
        }
        $tar = $this->cssStyles->$tablePart->$tag->toArray();
        $diff = array_diff($classes, $tar);
        $tar = array_merge($tar, $diff);
        $this->cssStyles->$tablePart->$tag = new Std($tar);
        return $this;
    }
    protected function areAllColumnsDefined(array $columns, $checkExtraColumns = false, $checkCalculatedColumns = false)
    {
        $classColumns = array_keys($this->className::getColumns());
        $extraColumns = true === $checkExtraColumns ? $this->extraColumns()->toArray() : [];
        $calculatedColumn = true === $checkCalculatedColumns ? array_keys($this->calculated->toArray()) : [];
        $unionColumns = array_merge($classColumns, $extraColumns, $calculatedColumn);
        $diff = array_diff($columns, $unionColumns);
        if (count($diff) > 0) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table!');
        }
        return true;
    }

    protected function validateColumnName(string $columns, $checkExtraColumns = false, $checkCalculatedColumns = false)
    {
        $classColumns = array_keys($this->className::getColumns());
        $extraColumns = true === $checkExtraColumns ? $this->extraColumns()->toArray() : [];
        $calculatedColumn = true === $checkCalculatedColumns ? array_keys($this->calculated->toArray()) : [];
        $unionColumns = array_merge($classColumns, $extraColumns, $calculatedColumn);
        if (! in_array($columns, $unionColumns)) {
            throw new Exception('columns have to belong ' . $this->className::getTableName() . ' table or have been defined as ExtraColumns!');
        }
        return true;
    }
    protected function validateColumnIsDefined($column)
    {
        if ($this->isColumnDefined($column)) {
            return true;
        } else {
            throw new Exception('Column ' . $column . ' doesn\'t set as table column');
        }
    }
    protected function validateLowerColumnIsDefined($column)
    {
        if ($this->isLowerColumnDefined($column)) {
            return true;
        } else {
            throw new Exception('Column ' . $column . ' doesn\'t set as table column');
        }
    }

    protected function validateSortDirection($direct)
    {
        $direct = strtolower($direct);
        if ('asc' == $direct || 'desc' == $direct || '' == $direct) {
            return true;
        } else {
            throw new Exception('Allowed sort direction is: \'asc\', \'desc\' or empty');
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
            case 'name':
                if (! is_string($val)) {
                    throw new Exception('Not valid title');
                }
                break;
            case 'width':
            case 'height':
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
            case 'filterable':
            case 'visible':
                if (! is_bool($val)) {
                    throw new Exception('Invalid sortable value');
                }
                break;
            default:
                throw new Exception('Unknown parameter \'' . $param . '\'');
        }
        return true;
    }

    protected function isSqlOperatorValid($op)
    {
        return in_array($op, self::$sqlOperators);
    }
    protected function validateSqlOperator($op)
    {
        if (! $this->isSqlOperatorValid($op)) {
            throw new Exception($op . ' doesn\'t valid sql operator');
        }
        return true;
    }

    protected function isSqlMethodValid($val)
    {
        return in_array(strtolower($val), self::$sqlMethods);
    }
    protected function sqlMethodValidate($methodName)
    {
        if (! $this->isSqlMethodValid($methodName)) {
            throw new Exception($methodName . ' doesn\'t valid sql aggregated method');
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
            case 'height':
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
            case 'filterable':
                return $val;
                break;
            default:
                return $val;
        }
    }

    protected function connectionNameValidate($connectionName)
    {
            if ('cli' == PHP_SAPI) {
                $app = \T4\Console\Application::instance();
            } else {
                $app = \T4\Mvc\Application::instance();
            }
            $res = $app->db->$connectionName;

            if ($res instanceof Connection) {
                return true;
            }
            throw new Exception($connectionName . ' is not valid connection name');
    }

    public function isCalculated(string $columnAlias)
    {
        return isset($this->calculated->$columnAlias);
    }
    /* ============= GETTERS =================*/
    protected function getHeaderCssClasses()
    {
        return isset($this->cssStyles->header) ? $this->cssStyles->header : false;
    }
    protected function getBodyCssClasses()
    {
        return isset($this->cssStyles->body) ? $this->cssStyles->body : false;
    }
    protected function getBodyFooterCssClasses()
    {
        return isset($this->cssStyles->bodyFooter) ? $this->cssStyles->bodyFooter : false;
    }
    protected function getFooterCssClasses()
    {
        return isset($this->cssStyles->footer) ? $this->cssStyles->footer : false;
    }
}