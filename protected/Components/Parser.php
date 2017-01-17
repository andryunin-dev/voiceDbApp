<?php

namespace App\Components;

use T4\Core\Collection;
use T4\Core\Std;

class Parser extends Std
{
    /**
     * @param string $data
     * @return Collection
     *
     * @param string $data входные данные
     * @param array $fieldNames массив с названиями полей в объектах выходной коллекции
     * @val array $fieldNames = ['region', 'city', 'address', 'lotusId', 'office', 'status']
     */
    public static function lotusTerritory(string $data) : Collection
    {
        $fieldNames = ['region', 'city', 'address', 'lotusId', 'office', 'status'];
        $pattern = '~([^,]+)\s*,\s*([^,]+)\s*,\s*("[^"]+")\s*,\s*([^,]+)\s*,\s*("[^"]+")~';
        $resultCount = preg_match_all($pattern, $data, $resultArray, PREG_SET_ORDER);
        if (false === $resultCount) {
            return false;
        }
        array_unshift($fieldNames, 'originalData');
        $resultCollection = new Collection();
        foreach ($resultArray as $item) {
            //если кол-во полей заголовокв с добавленным полем 'originalData' == кол-ву полей в текущей записи + 1
            //значит убрать поле статуса
            if (count($item) + 1 == count($fieldNames)) {
                array_pop($fieldNames); //убираем поле статуса
            } elseif (count($item) != count($fieldNames)) {
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