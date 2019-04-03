<?php

namespace App\ApiHelpers;

use App\Components\IpTools;
use App\Models\Network;
use App\Models\Vrf;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class NetData
 * @package App\Models
 *
 * @property Std $rawData
 * @property bool $ipCheckResult
 * @property bool $vrfCheckResult
 * @property Std $errors
 * @property Vrf $vrf
 * @property Network $netObject
 */
class NetData extends Std
{
    const SQL_QUERIES = [
        'findNetworkBy_Ip_VrfId' =>
        'SELECT __id, address FROM network.networks
          WHERE address = :address AND __vrf_id = :vrfId',
        'findCloserParentNetworkForNetIp' =>
        'SELECT n.__id , n.address
          FROM network.networks n
          JOIN (SELECT MAX(address) max_address FROM network.networks
                WHERE address >> :address AND __vrf_id = :vrfId) t ON n.address = t.max_address AND n.__vrf_id = :vrfId',
        'findNetworkForHostByIp' =>
        'SELECT __id network_id, address network_address FROM network.networks
          WHERE address = network(:address) AND __vrf_id = :vrfId'
    ];
    
  
//    protected static $schema = [
//        'table' => 'network.networks',
//        'columns' => [
//            'address' => ['type' => 'string'], //address in cidr notation i.e. 192.168.1.0/24
//            'comment' => ['type' => 'string']
//        ],
//        'relations' => [
//            'hosts' => ['type' => self::HAS_MANY, 'model' => DataPort::class, 'by' => '__network_id'],
//            'vlan' => ['type' => self::BELONGS_TO, 'model' => Vlan::class, 'by' => '__vlan_id'],
//            'vrf' => ['type' => self::BELONGS_TO, 'model' => Vrf::class, 'by' => '__vrf_id'],
//            'location' => ['type' => self::BELONGS_TO, 'model' => Office::class, 'by' => '__location_id'],
//        ]
//    ];
    
    public function __construct(Std $data)
    {
        parent::__construct();
        $this->errors = new Std();
        $this->rawData = $this->sanitizeRawData($data);
        $this->ipCheckResult = $this->checkIpAddress();
        $this->vrfCheckResult = $this->checkVrf();
        if ($this->ipCheckResult && $this->vrfCheckResult) {
            
            $this->netObject->fill([
                'address' => $this->rawData->netIp,
                'comment' => $this->rawData->netComment,
                'vrf' => $this->vrf
            ]);
        }
    }
    
    public function saveNetData()
    {
        if ($this->beforeSave() === true) {
            $res = $this->netObject->save();
        } else {
            $res = false;
        }
        return $res;
    }
    protected function beforeSave()
    {
        return $this->errors->count() == 0 && $this->ipCheckResult && $this->vrfCheckResult;
    }
    
    protected function sanitizeRawData(Std $data)
    {
        $res = new Std($data->toArrayRecursive());
        $res->netIp = trim($res->netIp);
        $res->netComment = trim($res->netComment);
        $res->vrfId = is_numeric($res->vrfId) ? intval($res->vrfId) : null;
        return $res;
    }
    protected function checkIpAddress()
    {
        try {
            //check ip format
            if ($this->checkNetworkIpFormat($this->rawData->netIp) === false) {
                return false;
            }
            if ($this->rawData->newNet === true) {
                // creation a new network
                $this->netObject = new Network();
                return $this->checkAbilityCreateNetwork($this->rawData->netIp, $this->rawData->vrfId);
            } else {
                //edit existed network
                $this->netObject = Network::findByPK($this->rawData->netId);
                if (! $this->netObject instanceof Network) {
                    $this->errors[] = 'Network not found';
                    return false;
                }
                if ($this->netObject->address !== $this->rawData->netIp || $this->netObject->vrf->getPk() !== $this->rawData->vrfId) {
                    $delNet = $this->checkAbilityDeleteNetwork($this->netObject);
                    $createNet = $this->checkAbilityCreateNetwork($this->rawData->netIp, $this->rawData->vrfId);
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
        try {
            if (!is_numeric($this->rawData->vrfId)) {
                $this->errors[] = 'VRF is not selected';
                return false;
            }
            $this->vrf = Vrf::findByPK($this->rawData->vrfId);
            if (! $this->vrf instanceof Vrf) {
                $this->errors[] = 'Selected VRF is not exists';
                return false;
            }
            return true;
        } catch(\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    protected function checkNetworkIpFormat($networkIp)
    {
        $ip = new IpTools($networkIp);
        if (!$ip->is_valid || !$ip->is_networkIp) {
            $this->errors[] = 'Invalid network IP';
            return false;
        }
        return true;
    }
    protected function findNetworkBy_NetIp_Vrf_id(string $netIp, int $vrfId)
    {
        $query = new Query(self::SQL_QUERIES['findNetworkBy_Ip_VrfId']);
        $result = Network::findByQuery($query, [':address' => $netIp, ':vrfId' => $vrfId]);
        return $result;
    }
    
    
    /**
     * @param Network $network
     * @return int
     */
    protected function getHostsCount(Network $network)
    {
        return $network->hosts->count();
    }
    protected function findCloserParentNetworkForNetIp(string $netIp, int $vrfId)
    {
        $query = new Query(self::SQL_QUERIES['findCloserParentNetworkForNetIp']);
        $result = Network::findByQuery($query, [':address' => $netIp, ':vrfId' => $vrfId]);
        return $result;
    }
//    ====================================================
    /**
     * get parent network, check if parent network doesn't consists hosts (all children in one should be only networks)
     * if hosts exist - write to errors
     * @param string $networkIp
     * @param int $vrf_id
     * @return bool
     */
    public function checkAbilityCreateNetwork($networkIp, $vrf_id)
    {
        if (!$this->checkNetworkIpFormat($networkIp)) {
            return false;
        }
        try {
            $existedNet = $this->findNetworkBy_NetIp_Vrf_id($networkIp, $vrf_id);
            if ($existedNet !== false) {
                $this->errors[] = "Network $networkIp already exists";
                return false;
            }
            $parentNet = $this->findCloserParentNetworkForNetIp($networkIp, $vrf_id);
            if ($parentNet === false) {
                return true;
            } else {
                $hostsCount = $this->getHostsCount($parentNet);
                if ($hostsCount == 0) {
                    return true;
                }
                $this->errors[] = "Parent network {$parentNet->address} for {$networkIp} consists $hostsCount hosts IP";
                return false;
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    public function checkAbilityDeleteNetwork($network)
    {
        try {
            if (! $network instanceof Network) {
                return false;
            }
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
}