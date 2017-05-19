<?php

namespace App\Models;
use App\Components\Ip;
use T4\Core\Collection;

/**
 * Class HelperTrait
 * @package App\Models
 *
 * вспомогательные методы
 */
trait HelperTrait
{
    /**
     * @param $path
     * @return mixed|null
     *
     * возвращает значение свойства текущего объекта по пути $path
     * варианты $path - 'prop.prop2' | 'prop1->prop2' | ['prop1', 'prop2']
     */
    public function getValueByPath($path)
    {
        $propValue = function ($obj, array $pathArray) use (&$propValue) {
            if (empty($pathArray)) {
                return $obj;
            }
            $propName = array_shift($pathArray);
            return $propValue($obj->{$propName}, $pathArray);
        };
        if (is_array($path)) {
            return $propValue($this, $path);
        } elseif (is_string($path)) {
            return $propValue($this, preg_split('~(->|\.)~', $path));
        } else {
            return null;
        }
    }

    public static function sortCollection(Collection $collection, array $options)
    {
        $checkSortOrder = function ($allowedSortFields) use (&$options) {
            $directions = [
                'asc',
                'desc'
            ];
            $sortOrder = [];

            foreach ($options as $field => $direction) {
                if (
                    !in_array(strtolower($field), $allowedSortFields) ||
                    !in_array(strtolower($direction), $directions)
                ) {
                    continue;
                }
                $sortOrder[strtolower($field)] = strtolower($direction);
                unset($options[$field]);
            }
            return $sortOrder;
        };

        //method for sorting Networks collection
        $networkSorter = function () use (&$collection, &$checkSortOrder) {
            $allowedSortFields = [
                'address',
                'vrf.name',
                'vrf.rd'
            ];
            $sortOrder = $checkSortOrder($allowedSortFields);
            $sortedCollection = $collection->uasort(function (Network $network1, Network $network2) use (&$sortOrder) {
                $result = 1;
                foreach ($sortOrder as $field => $direction) {
                    switch ($field) {
                        case 'address':
                            $net1 = new Ip($network1->address);
                            $net2 = new Ip($network2->address);
                            $result = ip2long($net1->address) <=> ip2long($net2->address);
                            //if addresses equal compare masklen
                            $result = $result ?: $net1->masklen <=> $net2->masklen;
                            break;
                        case 'vrf.name':
                            $vrf1 = $network1->vrf->name;
                            $vrf2 = $network2->vrf->name;
                            if (Vrf::GLOBAL_VRF_NAME == $vrf1 && Vrf::GLOBAL_VRF_NAME != $vrf2) {
                                $result = -1;
                            } elseif (Vrf::GLOBAL_VRF_NAME != $vrf1 && Vrf::GLOBAL_VRF_NAME == $vrf2) {
                                $result = 1;
                            } else {
                                $result = strnatcmp(strtolower($network1->vrf->name), strtolower($network2->vrf->name));
                            }
                            break;
                    }
                    if (0 != $result) {
                        $result = ('asc' == $direction) ? $result : (-1) * $result;
                        break;
                    }
                }
                return $result ?: 1;
            });
            return $sortedCollection;
        };


        //identify member's class name
        $className = get_class($collection->first());
        if (false === $className) {
            return false;
        }
        switch ($className) {
            case Network::class:
                $result = $networkSorter();
                break;

            default:
                $result = [];
        }
        return $result;
    }
}