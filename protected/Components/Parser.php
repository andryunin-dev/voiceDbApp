<?php

namespace App\Components;

use T4\Core\Collection;
use T4\Core\Std;

class Parser extends Std
{
    /**
     * @param string $data
     * @return Collection|boolean
     *
     * @param string $data входные данные
     * @param array $fieldNames массив с названиями полей в объектах выходной коллекции
     * @val array $fieldNames = ['region', 'city', 'address', 'lotusId', 'office']
     */
    public static function lotusTerritory(string $data)
    {
        //Убираем "" (две двойных кавычки подряд)
        $pattern = '~"{2}~';
        $data = preg_replace($pattern, '', $data);
        //задаем имена полей
        $fieldNames = ['region', 'city', 'address', 'lotusId', 'office'];
        $pattern = '~"?([^,"]+)"?\s*,\s*"?([^,"]+)"?\s*,\s*"?([^"]+)"?\s*,\s*([^,]+)\s*,\s*"([^"]+)"~';
        $resultCount = preg_match_all($pattern, $data, $resultArray, PREG_SET_ORDER);
        if (empty($resultCount)) {
            return false;
        }
        array_unshift($fieldNames, 'originalData');
        $resultCollection = new Collection();
        foreach ($resultArray as $item) {
            if (count($item) != count($fieldNames)) {
                continue; // если кол-во полей не совпадает - неправильная запись
            }
            $assocArray = array_combine($fieldNames, $item);
            if (false === $assocArray) {
                continue;
            }
            $resultCollection->add(
                (new Std())
                    ->fromArray($assocArray)
            );
        }
        return $resultCollection;
    }
}