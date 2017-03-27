<?php

namespace App\Models;

use App\Components\Ip;
use T4\Core\Exception;
use T4\Dbal\Query;
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
            'portType' => ['type' => self::BELONGS_TO, 'model' => DPortType::class, 'by' => '__type_port_id'],
            'network' => ['type' =>self::BELONGS_TO, 'model' => Network::class, 'by' => '__network_id']
        ]
    ];

    protected $ip;

    public static function countAllByIp($ip)
    {
        $query = (new Query())
            ->select()
            ->from(DataPort::getTableName())
            ->where('host("ipAddress") = host(:ip)')
            ->params([':ip' => $ip]);

        return DataPort::countAllByQuery($query);
    }

    public static function findAllByIp($ip)
    {
        $query = (new Query())
            ->select()
            ->from(DataPort::getTableName())
            ->where('host("ipAddress") = host(:ip)')
            ->params([':ip' => $ip]);

        return DataPort::findAllByQuery($query);
    }

    /**
     * надо бы избавиться от этого метода. Заменить на использование класса Components\Ip
     * @param $val
     * @return bool
     */
    public static function is_ipAddress($val)
    {
        if (empty($val = trim($val))) {
            return false; //IP адрес не задан
        }
        //$val = str_replace('\\', '/', $val); //меняем ошибочные слеши
        $val = explode('/', $val);
        if (1 == count($val)) {     //нет маски
            $ip = array_pop($val);
        } elseif (2 == count($val)) { //есть маска
            $mask = array_pop($val);
            $ip = array_pop($val);
        } else {
            return false; //Неверный формат IP адреса
        }
        // class of IP address
        $is_ipv4 = (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4));
        $is_ipv6 = (false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
        if (false === $is_ipv4 && false === $is_ipv6) {
            return false; //Неверный формат IP адреса
        }

        //check mask
        $masklenMax = ($is_ipv4) ? 32 : 128;
        if (!isset($mask)) {
            return true; //if empty mask and IP valid
        }
        if (!empty($mask) && !is_numeric($mask)) { //if mask is not empty and not numeric
            return false; //Неверная маска
        }

        if (false === ($mask > 0 && $mask <= $masklenMax)) {
            return false; //Неверная маска
        }
        return true;
    }

    public static function sanitizeIp($ip)
    {
        return str_replace('\\', '/', trim($ip)); //меняем ошибочные слеши
    }


    /**
     * не может быть пустым
     * адрес должен быть валидным
     * должна быть явно зада маска
     * адрес не должен быть адресом сети
     *
     * @param $val
     * @return bool
     * @throws Exception
     *
     */
    protected function validateIpAddress($val)
    {
        $this->ip = new Ip(str_replace('\\', '/', $val)); //меняем ошибочные слеши
        if (false === $this->ip->is_valid) {
            throw new Exception(implode('<br>', $this->ip->errors));
        }
        return true;
    }

    /**
     * не должен быть пустым
     * должен быть валидным
     *
     * @param $val
     * @return bool
     * @throws Exception
     */
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
        if (true !== $this->ip->is_hostIp) {
            throw new Exception($this->ip->address . ' является адресом подсети' );
        }

            //ищем записи с таким ip для новой записи
        if (true === $this->isNew && DataPort::countAllByIp($this->ip->address) > 0) {
            throw new Exception('IP адрес ' . $this->ip->address . ' уже используется.');
        }
        return true;
    }
}
