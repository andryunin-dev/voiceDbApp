<?php

namespace App\Models;

use App\Components\Ip;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class Network
 * @package App\Models
 *
 * @property string $address
 * @property Collection|DataPort[] $hosts
 * @property Vlan $vlan
 * @property Vrf $vrf
 * @property Office $location
 */
class Network extends Model
{
    protected static $schema = [
        'table' => 'network.networks',
        'columns' => [
            'address' => ['type' => 'string'], //address in cidr notation i.e. 192.168.1.0/24
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'hosts' => ['type' => self::HAS_MANY, 'model' => DataPort::class, 'by' => '__network_id'],
            'vlan' => ['type' => self::BELONGS_TO, 'model' => Vlan::class, 'by' => '__vlan_id'],
            'vrf' => ['type' => self::BELONGS_TO, 'model' => Vrf::class, 'by' => '__vrf_id'],
            'location' => ['type' => self::BELONGS_TO, 'model' => Office::class, 'by' => '__location_id'],
            'parent' => ['type' =>self::BELONGS_TO, 'model' => Network::class, 'by' => '__prt'],
            'children' => ['type' =>self::HAS_MANY, 'model' => Network::class, 'by' => '__prt'],

        ]
    ];

    protected static $extensions = ['tree'];
    protected static $sortDefault = ['address' => 'asc', 'vrf' => 'asc'];

    protected function validateAddress($val)
    {
        if (!is_string($val)) {
            throw new Exception('Неверный тип свойства network->address');
        }
        $ip = new Ip($val);

        if (false === $ip->network || false === $ip->is_networkIp) {
            throw new Exception('Неверный адрес подсети');
        }
        return true;
    }

    protected function sanitizeAddress($val)
    {
        return (new Ip($val))->cidrAddress;
    }

    protected function validate()
    {
        if (! $this->vrf instanceof Vrf) {
            throw new Exception('VRF не найден');
        }
        if (true === $this->isNew && false !== self::findByAddressVrf($this->address, $this->vrf)) {
            throw new Exception('Сеть с адресом ' . $this->address . '(VRF: ' . $this->vrf . ') уже существует');
        }
        if (true === $this->isUpdated && false !== $existedNet = self::findByAddressVrf($this->address, $this->vrf)) {
            return ($existedNet->getPk() === $this->getPk());
        }
        return true;
    }

    protected function beforeSave()
    {
        if (true === $this->isNew) {
            $this->parent = $this->findParentNetwork();
            if (false === $this->parent) {
                return parent::beforeSave();
            }
            if ($this->parent->hosts->count() > 0) {
                throw new Exception('Родительская подсеть содержит IP хостов.<br>Разбиение на подсети невозможно');
            }
        } elseif (true === $this->isUpdated) {
            foreach ($this->children as $child) {
                $child->parent = $this->parent;
                $child->save();
            }
            $this->parent = $this->findParentNetwork();
        }

        return parent::beforeSave();
    }

    protected function afterSave()
    {
        if (true === $this->wasUpdated || true === $this->wasNew) {
            if (false !== $this->parent) {
                foreach ($this->parent->children as $child) {
                    if (true === (new Ip($this->address))->is_parent(new Ip($child->address)) && $this->vrf->getPk() == $child->vrf->getPk()) {
                        $child->parent = $this;
                        $child->save();
                    }
                }
            } else {
                foreach (self::__callStatic('findAllRoots', []) as $rootItem) {
                    if (true === (new Ip($this->address))->is_parent(new Ip($rootItem->address)) && $this->vrf->getPk() == $rootItem->vrf->getPk()) {
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
        if ($this->hosts->count() > 0) {
            throw new Exception('Подсеть содержит хостовые IP. Удаление невозможно');
        }
        if (false === $this->isNew) {
            foreach ($this->children as $child) {
                $child->parent = $this->parent;
                $child->save();
            }
        }
        return parent::beforeDelete();
    }

    public static function findAll($options = [])
    {
        $allowedSortFields = [
            'address',
            'vrf'
        ];
        $directions = [
            'asc',
            'desc'
        ];
        $sortOrder = [];
        if (is_array($options)) {
            foreach ($options as $field => $direction) {
                if (
                    ! in_array(strtolower($field), $allowedSortFields) ||
                    ! in_array(strtolower($direction), $directions)
                ) {
                    continue;
                }
                $sortOrder[strtolower($field)] = strtolower($direction);
                unset($options[$field]);
            }
        }

        $networks = parent::findAll($options);
        if (empty($sortOrder)) {
            return $networks;
        }

        $networks = $networks->uasort(function (Network $network1, Network $network2) use (&$sortOrder) {
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
                    case 'vrf':
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
        return $networks;
    }

    /**
     * @return Network|bool
     */
    public function findParentNetwork()
    {
        $query = 'WITH parents AS (SELECT DISTINCT * FROM network.networks WHERE address >> :subnet) SELECT * FROM parents WHERE address=(SELECT max(address) FROM parents) AND __vrf_id = :vrf';
        return static::findByQuery($query, [':subnet' => $this->address, ':vrf' => $this->vrf->getPk()]);
    }

    /**
     * @param $address
     * @param Vrf $vrf
     * @return Network|bool
     */
    public static function findByAddressVrf($address, Vrf $vrf)
    {
        $result = Network::findAllByColumn('address', $address)->filter(function (Network $network) use ($vrf) {
            return ($network->vrf->getPk() == $vrf->getPk());
        });
        $result = $result->first();
        return (null === $result) ? false : $result;
    }

    public static function findAllRootsByVrf($vrf = null, $options = [])
    {        $allowedSortFields = [
        'address',
        'vrf'
    ];
        $directions = [
            'asc',
            'desc'
        ];
        $sortOrder = [];
        if (is_array($options)) {
            foreach ($options as $field => $direction) {
                if (
                    ! in_array(strtolower($field), $allowedSortFields) ||
                    ! in_array(strtolower($direction), $directions)
                ) {
                    continue;
                }
                $sortOrder[strtolower($field)] = strtolower($direction);
                unset($options[$field]);
            }
        }
        if (null ===  $vrf) {
            $networks = self::findAllRoots();
        } elseif ($vrf instanceof Vrf) {
            /**
             * @var Collection|Network[] $roots
             */
            $roots = self::findAllRoots();
            $networks = $roots->filter(function (Network $network) use ($vrf) {
                return $network->vrf->getPk() == $vrf->getPk();
            });
        } else {
            $networks = false;
        }
        if (empty($sortOrder)) {
            return $networks;
        }

        $networks = $networks->uasort(function (Network $network1, Network $network2) use (&$sortOrder) {
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
                    case 'vrf':
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

        return $networks;
    }

    public function findAllChildren($options = [])
    {
        $allowedSortFields = [
            'address'
        ];
        $directions = [
            'asc',
            'desc'
        ];
        $sortOrder = [];
        if (is_array($options)) {
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
        }
        $networks = $this->children;
        if (empty($sortOrder)) {
            return $networks;
        }
        $networks = $networks->uasort(function (Network $network1, Network $network2) use (&$sortOrder) {
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
                }
                if (0 != $result) {
                    $result = ('asc' == $direction) ? $result : (-1) * $result;
                    break;
                }
            }
            return $result ?: 1;
        });
        return $networks;
    }

    public function hostIpNumbers()
    {
        $netObj = new Ip($this->address);
        if (false !== $netObj->is_networkIp) {
            return false;
        }
        $netSize = $netObj->networkSize;
        if ($netObj->masklen != 32) {
            $netSize -=2;
        }
        return $netSize;
    }

    public function test($arg)
    {
        return $arg;
    }
}