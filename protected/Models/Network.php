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
 * @property string[] $errors
 * @property string $address
 * @property string $comment
 * @property Collection|DataPort[] $hosts
 * @property Vlan $vlan
 * @property Vrf $vrf
 * @property Office $location
 * @property Collection $children
 * @property Network|false $parentNetwork
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
        'findCloserParentNetworkByNetIpVrfId' =>
            'SELECT *
          FROM network.networks n
          JOIN (SELECT MAX(address) max_address FROM network.networks
                WHERE address >> :address AND __vrf_id = :vrf_id) t ON n.address = t.max_address AND n.__vrf_id = :vrf_id',
        'findCloserParentNetworkByNetIp' =>
            'SELECT *
          FROM network.networks n
          JOIN (SELECT MAX(address) max_address FROM network.networks
                WHERE address >> :address) t ON n.address = t.max_address',
        'findNetworkForHostByIp' =>
            'SELECT * FROM network.networks
          WHERE address = network(:address) AND __vrf_id = :vrf_id',
        'findAllNetworks' => '
        SELECT net_id __id, net_ip address, net_comment "comment", vrf_id __vrf_id FROM api_view.networks
        WHERE net_id NOTNULL AND vrf_id NOTNULL',
        'networks_location' => '
            WITH
                locs AS (
                    SELECT
                        offices.__id AS location_id,
                        offices.title AS office,
                        addresses.address AS office_address,
                        cities.title AS city
                    FROM ((company.offices
                        JOIN geolocation.addresses ON ((offices.__address_id = addresses.__id)))
                        JOIN geolocation.cities ON ((addresses.__city_id = cities.__id)))
                ),
                devs AS (
                    SELECT
                        dv.__type_id AS dev_type_id,
                        dv.__location_id AS location_id,
                        net.__id AS net_id,
                        (net.address)::INET AS network,
                        regexp_replace((dv.details ->> \'hostname\'::TEXT), \'([a-z_0-9]+)-([a-z0-9]+)-.*\'::TEXT, \'\1-\2\'::TEXT) AS short_hostname,
                        dp.masklen,
                        apt.type AS dev_type,
                        ((date_part(\'epoch\'::TEXT, age(now(), dp."lastUpdate")) / (3600)::DOUBLE PRECISION))::INTEGER AS port_age
                    FROM (((equipment.appliances dv
                        JOIN equipment."applianceTypes" apt ON ((dv.__type_id = apt.__id)))
                        JOIN equipment."dataPorts" dp ON ((dv.__id = dp.__appliance_id)))
                        JOIN network.networks net ON ((dp.__network_id = net.__id)))
                )
            SELECT
                (host(devs.network))::INET AS network,
                devs.masklen AS range,
                host(broadcast(inet (host(network) || \'/\' || devs.masklen))) AS broadcast,
                locs.office
            FROM devs
                JOIN locs USING (location_id)
            WHERE (devs.dev_type_id = 6) AND (devs.masklen < 30) AND (devs.port_age < 72)
            GROUP BY devs.network, devs.short_hostname, devs.masklen, locs.office, locs.city, locs.office_address
            ORDER BY (host(devs.network))::INET'
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
    protected function getVrfId()
    {
        return $this->vrf->getPk();
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
    protected function sanitizeNetworkData()
    {
        $this->address = trim($this->address);
        $this->comment = trim($this->comment);
        return $this;
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
    protected function findCloserParentNetworkByNetIpVrfId($address, $vrfId)
    {
        $query = new Query(self::SQL['findCloserParentNetworkByNetIpVrfId']);
        $result = Network::findByQuery($query, [':address' => $address, ':vrf_id' => $vrfId]);
        return $result;
    }
    /**
     * @param string $address
     * @param int $vrfId
     * @return Network
     */
    protected function findCloserParentNetworkByNetIp($address)
    {
        $query = new Query(self::SQL['findCloserParentNetworkByNetIp']);
        $result = Network::findByQuery($query, [':address' => $address]);
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
            throw new \Exception(implode($this->errors, ', '));
        }
        
    }
    
    protected function getParentNetwork()
    {
        $query = new Query(self::SQL['findCloserParentNetworkByNetIp']);
        $result = Network::findByQuery($query, [':address' => $this->address]);
        return $result;
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
            $parentNet = $this->findCloserParentNetworkByNetIpVrfId($address, $vrf_id);
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
            throw new \Exception(implode($this->errors, ', '));
            
        }
        return parent::beforeSave();
    }
    
    protected function beforeDelete()
    {
        if (! $this->checkAbilityDeleteNetwork($this))
        {
            throw new \Exception(implode($this->errors, ', '));
        }
        return parent::beforeDelete();
    }
    
    public static function findAllSortedByVrfIp ($vrfDirect = 'asc', $ipDirect = 'asc')
    {
        $query = self::SQL['findAllNetworks'];
        $order = "ORDER BY vrf_name ${vrfDirect}, net_ip ${ipDirect}";
        $query .= "\n" . $order;
        $query = new Query($query);
        $networks = Network::findAllByQuery($query);
        return $networks;
    }
    public static function findAllSortedByIpVrf ($ipDirect = 'asc',$vrfDirect = 'asc')
    {
        $query = self::SQL['findAllNetworks'];
        $order = "ORDER BY net_ip ${ipDirect}, vrf_name ${vrfDirect}";
        $query .= "\n" . $order;
        $query = new Query($query);
        $networks = Network::findAllByQuery($query);
        return $networks;
    }
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
    }

    public static function allLocations()
    {
        return Network::findAllByQuery(new Query(self::SQL['networks_location']), []);
    }

//    public static function findAll($options = [])
//    {
//        $allowedSortFields = [
//            'address',
//            'vrf'
//        ];
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
//
//        $networks = parent::findAll($options);
//        if (empty($sortOrder)) {
//            return $networks;
//        }
//
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
//        return $networks;
//    }

//    /**
//     * @return Network|bool
//     */
//    public function findParentNetwork()
//    {
//        $query = 'WITH parents AS (SELECT DISTINCT * FROM network.networks WHERE address >> :subnet) SELECT * FROM parents WHERE address=(SELECT max(address) FROM parents) AND __vrf_id = :vrf';
//        return static::findByQuery($query, [':subnet' => $this->address, ':vrf' => $this->vrf->getPk()]);
//    }



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