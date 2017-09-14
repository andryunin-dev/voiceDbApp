<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 12.09.2017
 * Time: 15:10
 */

namespace App\Components;


use T4\Core\Std;

/**
 * Class Sorter
 * @package App\Components
 *
 * @property array $sortBy
 * @property string $direction ASC or DESC
 */
class Sorter extends Std
{
    public function __construct($columns = null, string $direction = null)
    {
        if (empty($columns)) {
            $data = [];
        } elseif (is_string($columns)) {
            $columns = preg_split("/\s*,\s*/", $columns, -1, PREG_SPLIT_NO_EMPTY);
            $data['sortBy'] = $columns;
        } elseif (is_array($columns)) {
            $data['sortBy'] = $columns;
        } else {
            $data = [];
        }
        if (key_exists('sortBy', $data)) {
            $direction = ('asc' == strtolower($direction) || 'desc' == strtolower($direction)) ? strtoupper($direction): '';
            $data['direction'] = $direction;
        }
        parent::__construct($data);
    }

    public function __toString()
    {
        $direction = $this->direction;
        $tmpArray = array_map(function ($item) use ($direction) {
            return $item . ' ' . $direction;
        }, $this->sortBy->toArrayRecursive());
        return implode(', ', $tmpArray);
    }
}