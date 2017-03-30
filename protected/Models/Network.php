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
    protected $isUpdated = false;

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

    /**
     * @param string $address network address in CIDR notation
     * @return bool|Network
     */
    public static function create(string $address)
    {
        $newNetwork = new Ip($address);
        if (!$newNetwork->is_valid || $newNetwork->is_hostIp) {
            return false;
        }

        $newNetwork = (new self())
            ->fill([
                'address' => $newNetwork->network . '/' . $newNetwork->masklen
            ]);
        $newNetwork->parent = $newNetwork->findParentNetwork();
        $newNetwork->save();
        if (false !== $newNetwork && false !== $newNetwork->parent) {
            foreach ($newNetwork->parent->children as $child) {
                if (true === (new Ip($newNetwork->address))->is_parent(new Ip($child->address))) {
                    $child->parent = $newNetwork;
                    $child->save();
                }
            }
        }
        return $newNetwork;
    }

    public function update()
    {
        foreach ($this->children as $child) {
            $child->parent = $this->parent;
            $child->save();
        }
        $this->parent = $this->findParentNetwork();
        $this->save();
        if (false !== $this->parent) {
            foreach ($this->parent->children as $child) {
                if (true === (new Ip($this->address))->is_parent(new Ip($child->address))) {
                    $child->parent = $this;
                    $child->save();
                }
            }
        }
    }

    public function deleteFromTree()
    {
        foreach ($this->children as $child) {
            $child->parent = $this->parent;
            $child->save();
        }
        $this->delete();
    }

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
        /**
         * invoke validateAddress on update existent object
         */
        if (false === $this->isNew) {
            $this->validateAddress($this->address);
            $this->isUpdated = true;
        }
        return true;
    }

    protected function beforeSave()
    {
        if (true === $this->isUpdated || true === $this->isNew) {
            foreach ($this->children as $child) {
                $child->parent = $this->parent;
                $child->save();
            }
            $this->parent = $this->findParentNetwork();die;
        }
        return parent::beforeSave();
    }

    protected function afterSave()
    {
        if (true === $this->isUpdated || true === $this->wasNew) {
            $this->isUpdated = false;
            if (false !== $this->parent) {
                foreach ($this->parent->children as $child) {
                    if (true === (new Ip($this->address))->is_parent(new Ip($child->address))) {
                        $child->parent = $this;
                        $child->save();
                    }
                }
            } else {
                foreach (self::__callStatic('findAllRoots') as $rootItem) {
                    if (true === (new Ip($this->address))->is_parent(new Ip($rootItem->address))) {
                        $rootItem->parent = $this;
                        $rootItem->save();
                    }
                }
            }
        }
        return parent::afterSave();
    }

    protected function beforeDelete()
    {
        if (false === $this->isNew) {
            foreach ($this->children as $child) {
                $child->parent = $this->parent;
                $child->save();
            }
        }
        return parent::beforeDelete();
    }
}