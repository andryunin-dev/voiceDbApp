<?php

namespace App\Models;

use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class DataPort
 * @package App\Models
 *
 * @property string $ipAddress
 * @property string $macAddress
 * @property string $details
 * @property string $comment
 *
 * @property Appliance $appliance
 * @property DPortType $portType
 */
class DataPort extends Model
{
    protected static $schema = [
        'table' => 'equipment.dataPorts',
        'columns' => [
            'ipAddress' => ['type' => 'string'],
            'macAddress' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'text']
        ],
        'relations' => [
            'appliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
            'portType' => ['type' => self::BELONGS_TO, 'model' => DPortType::class, 'by' => '__type_port_id']
        ]
    ];

    protected function validateIpAddress($val)
    {
        if (empty($val = trim($val))) {
            throw new Exception('IP адрес не задан');
        }
        $val = str_replace('\\', '/', $val); //меняем ошибочные слеши
        $val = explode('/', $val);
        if (1 == count($val)) {     //нет маски
            $ip = array_pop($val);
        } elseif (2 == count($val)) { //есть маска
            $mask = array_pop($val);
            $ip = array_pop($val);
        } else {
            throw new Exception('Неверный формат IP адреса');
        }
        // class of IP address
        $is_ipv4 = (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        $is_ipv6 = (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
        if (false === $is_ipv4 && false === $is_ipv6) {
            throw new Exception('Неверный формат IP адреса');
        }

        //check mask
        $maskLengthMax = ($is_ipv4) ? 32 : 128;
        if (!isset($mask)) {
            return true; //if empty mask and IP valid
        }
        if (!empty($mask) && !is_numeric($mask)) { //if mask is not empty and not numeric
            throw new Exception('Неверная маска');
        }

        if (false === ($mask > 0 && $mask <= $maskLengthMax)) {
            throw new Exception('Неверная маска');
        }
        return true;
    }

    protected function validateMacAddress($val)
    {
        if (empty(trim($val))) {
            return false;
        }
        if (!empty(trim($val)) && false === filter_var(trim($val), FILTER_VALIDATE_MAC)) {
            throw new Exception('Неверный формат MAC адреса');
        }
        return true;
    }

    protected function sanitizeIpAddress($val)
    {
        return str_replace('\\', '/', trim($val)); //меняем ошибочные слеши
    }

    protected function sanitizeMacAddress($val)
    {
        return filter_var(trim($val), FILTER_VALIDATE_MAC);
    }

    protected function sanitizeComment($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (false === $this->appliance) {
            throw new Exception('Устройство не найдено');
        }
        if (false === $this->portType) {
            throw new Exception('Данный тип порта не найден');
        }

        return true;
    }
}
