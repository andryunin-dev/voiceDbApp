<?php
/**
 * Created by PhpStorm.
 * User: Dmitry
 * Date: 25.09.2017
 * Time: 16:52
 */

namespace App\Components\ContentFilters;


use T4\Core\Url;

class HrefFilter extends BaseContentFilter
{
    /**
     * HrefFilter constructor.
     * конструктор принимает на вход Url и создает из его GET параметров фильтр на основе BaseContentFilter
     *
     * @param string $url
     * @param string $className
     * @param array $mappingArray
     */
    public function __construct(string $url = null, $className = '', array $mappingArray = [])
    {
        if (! is_string($url)) {
            $url = '';
        }
        $data = [];
        $query = (new Url($url))->query;
        foreach ($query as $column => $value) {
            $columnSet = self::findColumn($column, $className, $mappingArray);
            if (false === $columnSet) {
                continue;
            } else {
                $column = array_keys($columnSet)[0];
                $columnValue = $columnSet[0];
            }
        }

        parent::__construct($data, $className, $mappingArray);
    }

    /**
     * @param string $column
     * @param string $className
     * @param array $mappingArray
     * @return array|bool
     *
     * В отличие от базового метода возвращает пару ['statement' => 'value']
     */
    protected static function findColumn(string $column, string $className = '', array $mappingArray = [])
    {
        return parent::findColumn($column, $className, $mappingArray);
    }
}