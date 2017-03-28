<?php

namespace App\Models;

use App\Components\Ip;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Network
 * @package App\Models
 *
 * @property string $address
 * @property DataPort $hosts
 */
class Network extends Model
{
    protected static $schema = [
        'table' => 'network.networks',
        'columns' => [
            'address' => ['type' => 'string'],
        ],
        'relations' => [
            'hosts' => ['type' => self::HAS_MANY, 'model' => DataPort::class, 'by' => '__network_id']
        ]
    ];

    protected static $extensions = ['tree'];

    public function findParentNetwork()
    {
        $query = 'WITH parents AS (SELECT DISTINCT * FROM network.networks WHERE address >> :subnet) SELECT * FROM parents WHERE address=(SELECT max(address) FROM parents)';
        return static::findByQuery($query, [':subnet' => $this->address]);
    }

    protected function validateAddress($val)
    {
        $ip = new Ip($val);

        if (false === $ip->network || false !== $ip->is_hostIp) {
            throw new Exception('Неверный адрес подсети');
        }
        return true;
    }

    protected function validate()
    {
        return true;
    }

    protected function beforeSave()
    {
        //Ищем родителя если новый объект
        if ($this->isNew()) {
            $this->parent = $this->findParentNetwork();
        }
        return parent::beforeSave();
    }

    protected function afterSave()
    {
        //ищем детей. Если новый объект является для них
        if ($this->wasNew()) {
            if (false !== $this->parent) {
                foreach ($this->parent->children as $child) {
                    if (true === (new Ip($this->address))->is_parent(new Ip($child->address))) {
                        $child->parent = $this;
                        $child->save();
                    }
                }
            }
        }
        return parent::afterSave();
    }

    protected function beforeDelete()
    {
        if (false !== $this->parent) {
//            echo $this->parent->address . '<br>';
            foreach ($this->children as $child) {
//                echo $this->parent->address . '=>' . $child->address . '-' . (new Ip($this->parent->address))->is_parent(new Ip($child->address)) . '<br>';
                if (true === (new Ip($this->parent->address))->is_parent(new Ip($child->address))) {
                    $child->parent = $this->parent;
                    $child->save();
                }
            }
        } else {
            foreach ($this->children as $child) {
                    $child->parent = false;
                    $child->save();
            }
        }
        return parent::beforeDelete();
    }
}