<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Vlan
 * @package App\Models
 *
 * @property integer $id
 * @property string $name
 * @property string $comment
 *
 * @property Collection|Network[] $networks
 */
class Vlan extends Model
{
    protected static $schema = [
        'table' => 'network.vlans',
        'columns' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'networks' => ['type' => self::HAS_MANY, 'model' => Network::class, 'by' => '__vlan_id']
        ]
    ];

    protected function validateId($val)
    {
        if (!(is_string($val) || is_integer($val))) {
            throw new Exception('Неверный тип свойства Vlan->id');
        }
        $val = $this->sanitizeId($val);
        if ($val < 1 || $val > 4094) {
            throw new Exception('Допустимый диапазон Vlan ID: 1-4094 включительно');
        }
        return true;
    }

    protected function sanitizeId($val)
    {
        return (int)trim($val);
    }

    protected function validateName($val)
    {
        if (!is_string($val)) {
            throw new Exception('Неверный тип свойства Vlan->name');
        }
        return true;
    }

    protected function validate()
    {
        if (false !== Vlan::findByColumn('id', $this->id)) {
            throw new Exception('VLAN с данным ID уже существует');
        }
        return true;
    }
}