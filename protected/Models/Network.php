<?php

namespace App\Models;

use App\Components\Ip;
use App\Components\IpTools;
use phpDocumentor\Reflection\Types\Array_;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class Network
 * @package App\Models
 *
 * @property string $address
 * @property string $comment
 * @property Collection|DataPort[] $hosts
 * @property Vlan $vlan
 * @property Vrf $vrf
 * @property Office $location
 * @property Collection $children
 */
class Network extends Model
{
    use HelperTrait;

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
//            'parent' => ['type' =>self::BELONGS_TO, 'model' => Network::class, 'by' => '__prt'],
//            'children' => ['type' =>self::HAS_MANY, 'model' => Network::class, 'by' => '__prt'],

        ]
    ];
    
    const SQL = [
        'netChildren' =>
            'WITH all_net_children AS (
                SELECT *
                FROM network.networks AS t0
                WHERE t0.address << :address AND t0.__vrf_id = :vrf_id
            )
            SELECT *
            FROM all_net_children AS t0
            WHERE
                NOT EXISTS(
                      SELECT t0.address
                      FROM all_net_children AS t1
                      WHERE t1.address >> t0.address
                    ) AND
                masklen(t0.address) != 32
            ORDER BY t0.address',
        'findNetworkBy_Ip_VrfId' =>
            'SELECT * FROM network.networks
          WHERE address = :address AND __vrf_id = :vrf_id',
        'findCloserParentNetworkForNetIp' =>
            'SELECT *
          FROM network.networks n
          JOIN (SELECT MAX(address) max_address FROM network.networks
                WHERE address >> :address AND __vrf_id = :vrf_id) t ON n.address = t.max_address AND n.__vrf_id = :vrf_id',
        'findNetworkForHostByIp' =>
            'SELECT * FROM network.networks
          WHERE address = network(:address) AND __vrf_id = :vrf_id'
    ];
    
    protected static $staticErrors = [];
    protected $errors;
    protected $ipCheckResult;
    protected $vrfCheckResult;
    
    /**
     * Is used in process of change existed network
     * @var Network
     */
    protected $existedNetwork;
    
    public function __construct($data = null)
    {
        $this->errors = [];
        parent::__construct($data);
    }
    
    
    public static function errors()
    {
        return (new Std())->fill(['errors' => self::$staticErrors]);
    }
    
    protected function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * @param array $data
     *  net_id bigint
     *  address string
     *  comment string
     *  vrf_id bigint
     *  vlan_id bigint
     * @return Network
     */
    protected function sanitizeNetworkData(array $data)
    {
        $this->address = trim($this->address);
        $this->comment = trim($this->comment);
        return $this;
        
//        $res = new Std($data);
//        $res->net_id = is_numeric($res->net_id) ? intval($res->net_id) : null;
//        $res->address = trim($res->address);
//        $res->comment = trim($res->comment);
//        $res->vrf_id = is_numeric($res->vrf_id) ? intval($res->vrf_id) : null;
//        $res->vlan_id = is_numeric($res->vlan_id) ? intval($res->vlan_id) : null;
//        return $res;
    }
    
    /**
     * @param Network $network
     * @return int
     */
    protected function getHostsCount(Network $network)
    {
        return $network->hosts->count();
    }
    
    /**
     * check ip data
     * @return bool
     */
    protected function checkIpAddress()
    {
        try {
            //check ip format
            if ($this->checkNetworkIpFormat($this->address) === false) {
                return false;
            }
            if ( ! is_numeric($this->getPk())) {
                // if id is null this data will be used for creation a new network
                return $this->checkAbilityCreateNetwork($this->address, $this->vrf->getPk());
            } else {
                //else edit existed network
                $this->existedNetwork = Network::findByPK($this->getPk());
                if (! $this->existedNetwork instanceof Network) {
                    $this->errors[] = 'Network not found';
                    return false;
                }
                // if we try to change address or VRF of existed network
                if ($this->address !== $this->existedNetwork->address || $this->vrf->getPk() !== $this->existedNetwork->vrf->getPk()) {
                    $delNet = $this->checkAbilityDeleteNetwork($this->existedNetwork);
                    $createNet = $this->checkAbilityCreateNetwork($this->address, $this->vrf->getPk());
                    return $delNet && $createNet;
                }
                return true;
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    protected function checkVrf()
    {
        if (! $this->vrf instanceof Vrf) {
            $this->errors[] = 'invalid VRF';
            return false;
        }
        return true;
    }
    protected function checkNetworkIpFormat(string $networkIp)
    {
        $ip = new IpTools($networkIp);
        if (!$ip->is_valid || !$ip->is_networkIp || $ip->is_maskNull) {
            $this->errors[] = 'Invalid network IP';
            return false;
        }
        return true;
    }
    
    /**
     * @param string $address
     * @param int $vrf_id
     * @return Network
     */
    protected function findNetworkBy_NetIp_Vrf_id($address, $vrf_id)
    {
        $query = new Query(self::SQL['findNetworkBy_Ip_VrfId']);
        $result = Network::findByQuery($query, [':address' => $address, ':vrf_id' => $vrf_id]);
        return $result;
    }
    
    /**
     * @param string $address
     * @param int $vrfId
     * @return Network
     */
    protected function findCloserParentNetworkForNetIp($address, $vrfId)
    {
        $query = new Query(self::SQL['findCloserParentNetworkForNetIp']);
        $result = Network::findByQuery($query, [':address' => $address, ':vrf_id' => $vrfId]);
        return $result;
    }
    
    protected function getChildren()
    {
        try {
            $query = new Query(self::SQL['netChildren']);
            $res = self::findAllByQuery($query, [':address' => $this->address, ':vrf_id' => $this->vrf->getPk()]);
            return $res;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            throw new Exception(implode($this->errors, ', '));
        }
        
    }
    
    /**
     * get parent network, check if parent network doesn't consists hosts (all children in one should be only networks)
     * if hosts exist - write to errors
     * @param string $address
     * @param int $vrf_id
     * @return bool
     */
    public function checkAbilityCreateNetwork($address, $vrf_id)
    {
        if (!$this->checkNetworkIpFormat($address)) {
            return false;
        }
        try {
            $existedNet = $this->findNetworkBy_NetIp_Vrf_id($address, $vrf_id);
            if ($existedNet !== false) {
                $this->errors[] = "Network $address already exists";
                return false;
            }
            $parentNet = $this->findCloserParentNetworkForNetIp($address, $vrf_id);
            if ($parentNet === false) {
                return true;
            } else {
                $hostsCount = $this->getHostsCount($parentNet);
                if ($hostsCount == 0) {
                    return true;
                }
                $this->errors[] = "Parent network {$parentNet->address} for {$address} consists $hostsCount hosts IP";
                return false;
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    /**
     * @param Network $network
     * @return bool
     */
    public function checkAbilityDeleteNetwork($network)
    {
        try {
            if (! $network instanceof Network) {
                return false;
            }
            $network->refresh();
            $hostsCount = $this->getHostsCount($network);
            if ($hostsCount == 0) {
                return true;
            }
            $this->errors[] = "Network {$network->address} consists {$hostsCount} hosts IP";
            return false;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    public function checkAbilityDeleteNetworkByIp($networkIp, $vrf_id)
    {
        if (!$this->checkNetworkIpFormat($networkIp)) {
            return false;
        }
        try {
            $network = $this->findNetworkBy_NetIp_Vrf_id($networkIp, $vrf_id);
            if ($network === false) {
                $this->errors[] = "Network not found";
                return false;
            }
            $hostsCount = $this->getHostsCount($network);
            if ($hostsCount == 0) {
                return true;
            }
            $this->errors[] = "Network {$network->address} consists $hostsCount hosts IP";
            return false;
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    protected function beforeSave()
    {
        $this->ipCheckResult = $this->checkIpAddress();
        $this->vrfCheckResult = $this->checkVrf();
        if (! $this->ipCheckResult || ! $this->vrfCheckResult) {
            throw new Exception(implode($this->errors, ', '));
            
        }
        return parent::beforeSave();
    }
    
    protected function beforeDelete()
    {
        if (! $this->checkAbilityDeleteNetwork($this))
        {
            throw new Exception(implode($this->errors, ', '));
        }
        return parent::beforeDelete();
    }
    
    
//    ===============LEGACY CODE
//    protected function validateAddress($val)
//    {
//        if (!is_string($val)) {
//            throw new Exception('Неверный тип свойства network->address');
//        }
//        $ip = new IpTools($val);
//
//        if (false === $ip->network || false === $ip->is_networkIp) {
//            throw new Exception('Неверный адрес подсети');
//        }
//        return true;
//    }

//    protected function sanitizeAddress($val)
//    {
//        return (new Ip($val))->cidrAddress;
//    }

//    public function validate()
//    {
//        if (null === $this->vrf) {
//            throw new Exception('VRF не задан');
//        }
//        if (! $this->vrf instanceof Vrf) {
//            throw new Exception('VRF не найден');
//        }
//        if (true === $this->isNew && false !== self::findByAddressVrf($this->address, $this->vrf)) {
//            throw new Exception('Сеть с адресом ' . $this->address . '(VRF: ' . $this->vrf . ') уже существует');
//        }
//        if (true === $this->isUpdated && false !== $existedNet = self::findByAddressVrf($this->address, $this->vrf)) {
//            return ($existedNet->getPk() === $this->getPk());
//        }
//        return true;
//    }

//    protected function beforeSave()
//    {
//        if (true === $this->isNew) {
//            $this->parent = $this->findParentNetwork();
//            if (false === $this->parent) {
//                return parent::beforeSave();
//            }
//            if ($this->parent->hosts->count() > 0) {
//                throw new Exception('Родительская подсеть ' . $this->parent->address . ' содержит IP хостов.Разбиение на подсети невозможно. Ошибка вставки дочерней подсети ' . $this->address);
//            }
//        }
//        return parent::beforeSave();
//    }

//    protected function afterSave()
//    {
//        if (true === $this->wasUpdated || true === $this->wasNew) {
//            if (false !== $this->parent) {
//                foreach ($this->parent->children as $child) {
//                    if (true === (new Ip($this->address))->is_parent(new Ip($child->address)) && $this->vrf->getPk() == $child->vrf->getPk()) {
//                        $child->parent = $this;
//                        $child->save();
//                    }
//                }
//            } else {
//                foreach (self::__callStatic('findAllRoots', []) as $rootItem) {
//                    if (true === (new Ip($this->address))->is_parent(new Ip($rootItem->address)) && $this->vrf->getPk() == $rootItem->vrf->getPk()) {
//                        $rootItem->parent = $this;
//                        $rootItem->save();
//                    }
//                }
//            }
//        }
//        return parent::afterSave();
//    }

//    protected function beforeDelete()
//    {
//        if ($this->hosts->count() > 0) {
//            throw new Exception('Подсеть ' . $this->address . ' содержит хостовые IP. Удаление невозможно');
//        }
//        if (false === $this->isNew) {
//            foreach ($this->children as $child) {
//                $child->parent = $this->parent;
//                $child->save();
//            }
//        }
//        return parent::beforeDelete();
//    }

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

//    /**
//     * @return Network|bool
//     */
//    public function findParentNetwork()
//    {
//        $query = 'WITH parents AS (SELECT DISTINCT * FROM network.networks WHERE address >> :subnet) SELECT * FROM parents WHERE address=(SELECT max(address) FROM parents) AND __vrf_id = :vrf';
//        return static::findByQuery($query, [':subnet' => $this->address, ':vrf' => $this->vrf->getPk()]);
//    }

    /**
     * @param $address
     * @param Vrf $vrf
     * @return Network|bool
     */
    public static function findByAddressVrf($address, $vrf)
    {
        $query = new Query(self::SQL['findNetworkBy_Ip_VrfId']);
        $result = Network::findByQuery($query, [':address' => $address, ':vrf_id' => $vrf->getPk()]);
        return $result;
        
//        $result = Network::findAllByColumn('address', $address)->filter(function (Network $network) use ($vrf) {
//            return ($network->vrf->getPk() == $vrf->getPk());
//        });
//        $result = $result->first();
//        return (null === $result) ? false : $result;
    }

//    public static function findAllRootsByVrf($vrf = null, $options = [])
//    {        $allowedSortFields = [
//        'address',
//        'vrf'
//    ];
//        $directions = [
//            'asc',
//            'desc'
//        ];
//        $sortOrder = [];
//        if (is_array($options)) {
//            foreach ($options as $field => $direction) {
//                if (
//                    ! in_array(strtolower($field), $allowedSortFields) ||
//                    ! in_array(strtolower($direction), $directions)
//                ) {
//                    continue;
//                }
//                $sortOrder[strtolower($field)] = strtolower($direction);
//                unset($options[$field]);
//            }
//        }
//        if (null ===  $vrf) {
//            $networks = self::findAllRoots();
//        } elseif ($vrf instanceof Vrf) {
//            /**
//             * @var Collection|Network[] $roots
//             */
//            $roots = self::findAllRoots();
//            $networks = $roots->filter(function (Network $network) use ($vrf) {
//                return $network->vrf->getPk() == $vrf->getPk();
//            });
//        } else {
//            $networks = false;
//        }
//        if (empty($sortOrder)) {
//            return $networks;
//        }
//
//        $networks = $networks->uasort(function (Model $network1, Model $network2) use (&$sortOrder) {
//            $result = 1;
//            foreach ($sortOrder as $field => $direction) {
//                switch ($field) {
//                    case 'address':
//                        $net1 = new Ip($network1->address);
//                        $net2 = new Ip($network2->address);
//                        $result = ip2long($net1->address) <=> ip2long($net2->address);
//                        //if addresses equal compare masklen
//                        $result = $result ?: $net1->masklen <=> $net2->masklen;
//                        break;
//                    case 'vrf':
//                        $vrf1 = $network1->vrf->name;
//                        $vrf2 = $network2->vrf->name;
//                        if (Vrf::GLOBAL_VRF_NAME == $vrf1 && Vrf::GLOBAL_VRF_NAME != $vrf2) {
//                            $result = -1;
//                        } elseif (Vrf::GLOBAL_VRF_NAME != $vrf1 && Vrf::GLOBAL_VRF_NAME == $vrf2) {
//                            $result = 1;
//                        } else {
//                            $result = strnatcmp(strtolower($network1->vrf->name), strtolower($network2->vrf->name));
//                        }
//                        break;
//                }
//                if (0 != $result) {
//                    $result = ('asc' == $direction) ? $result : (-1) * $result;
//                    break;
//                }
//            }
//            return $result ?: 1;
//        });
//
//        return $networks;
//    }

//    public function findAllChildren($options = [])
//    {
//        $allowedSortFields = [
//            'address'
//        ];
//        $directions = [
//            'asc',
//            'desc'
//        ];
//        $sortOrder = [];
//        if (is_array($options)) {
//            foreach ($options as $field => $direction) {
//                if (
//                    !in_array(strtolower($field), $allowedSortFields) ||
//                    !in_array(strtolower($direction), $directions)
//                ) {
//                    continue;
//                }
//                $sortOrder[strtolower($field)] = strtolower($direction);
//                unset($options[$field]);
//            }
//        }
//        $networks = $this->children;
//        if (empty($sortOrder)) {
//            return $networks;
//        }
//        $networks = $networks->uasort(function (Network $network1, Network $network2) use (&$sortOrder) {
//            $result = 1;
//            foreach ($sortOrder as $field => $direction) {
//                switch ($field) {
//                    case 'address':
//                        $net1 = new Ip($network1->address);
//                        $net2 = new Ip($network2->address);
//                        $result = ip2long($net1->address) <=> ip2long($net2->address);
//                        //if addresses equal compare masklen
//                        $result = $result ?: $net1->masklen <=> $net2->masklen;
//                        break;
//                }
//                if (0 != $result) {
//                    $result = ('asc' == $direction) ? $result : (-1) * $result;
//                    break;
//                }
//            }
//            return $result ?: 1;
//        });
//        return $networks;
//    }

//    public function findAllHosts($options = [])
//    {
//        $allowedSortFields = [
//            'address'
//        ];
//        $directions = [
//            'asc',
//            'desc'
//        ];
//        $sortOrder = [];
//        if (is_array($options)) {
//            foreach ($options as $field => $direction) {
//                if (
//                    !in_array(strtolower($field), $allowedSortFields) ||
//                    !in_array(strtolower($direction), $directions)
//                ) {
//                    continue;
//                }
//                $sortOrder[strtolower($field)] = strtolower($direction);
//                unset($options[$field]);
//            }
//        }
//        $hosts = $this->hosts;
//        if (empty($sortOrder)) {
//            return $hosts;
//        }
//        $hosts = $hosts->uasort(function (DataPort $host1, DataPort $host2) use (&$sortOrder) {
//            $result = 1;
//            foreach ($sortOrder as $field => $direction) {
//                switch ($field) {
//                    case 'address':
//                        $ipObj1 = new Ip($host1->ipAddress);
//                        $ipObj2 = new Ip($host2->ipAddress);
//                        $result = ip2long($ipObj1->address) <=> ip2long($ipObj2->address);
//                        //if addresses equal compare masklen
//                        $result = $result ?: $ipObj1->masklen <=> $ipObj2->masklen;
//                        break;
//                }
//                if (0 != $result) {
//                    $result = ('asc' == $direction) ? $result : (-1) * $result;
//                    break;
//                }
//            }
//            return $result ?: 1;
//        });
//        return $hosts;
//    }

//    public function hostIpNumbers()
//    {
//        $netObj = new Ip($this->address);
//        if (false !== $netObj->is_networkIp) {
//            return false;
//        }
//        $netSize = $netObj->networkSize;
//        if ($netObj->masklen != 32) {
//            $netSize -=2;
//        }
//        return $netSize;
//    }


}