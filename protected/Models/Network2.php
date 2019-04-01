<?php

namespace App\Models;

use App\Components\IpTools;
use App\ViewModels\ApiView_Networks;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class Network2
 * @package App\Models
 *
 * @property string $address
 * @property string $comment
 * @property Std $errors
 * @property DataPort $hosts
 * @property Vlan $vlan
 * @property Vrf $vrf
 */
class Network2 extends Model
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
        ]
    ];
    
    public function __construct($data = null)
    {
        $this->errors = new Std();
        if (empty($data)) {
            parent::__construct();
            return;
        }
        $this->rawData = $this->sanitizeRawData($data);
        $this->checkIpAddress();
        parent::__construct($data);
    }
    protected function checkIpAddress()
    {
        try {
            //check ip format
            if (! $this->checkNetworkIpFormat($this->rawData->netIp)) {
                return;
            }
            if ($this->rawData->newNet === true) {
                // creation a new network
                if ($this->checkAbilityCreateNetwork($this->rawData->netIp, $this->rawData->vrfId) === false) {
                    return;
                }
            } else {
                //edit existed network
                $oldNetwork = self::findByPK($this->rawData->netId);
                if ($oldNetwork->address !== $this->rawData->netIp || $oldNetwork->vrf->getPk() !== $this->rawData->vrfId) {
                    $delNet = $this->checkAbilityDeleteNetworkById($oldNetwork->getPk());
                    $createNet = $this->checkAbilityCreateNetwork($this->rawData->netIp, $this->rawData->vrfId);
                    if (!$delNet || !$createNet) {
                        return;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
        }
    }
    protected function sanitizeRawData(Std $data)
    {
        $res = new Std($data->toArrayRecursive());
        $res->netIp = trim($res->netIp);
        $res->netComment = trim($res->netComment);
        $res->vrfId = is_numeric($res->vrfId) ? intval($res->vrfId) : null;
        return $res;
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
        $result = Network2::findByQuery($query, [':address' => $netIp, ':vrfId' => $vrfId]);
        return $result;
    }
    
    protected function getHostsCount(Network2 $network)
    {
        return $network->hosts->count();
    }
    protected function findCloserParentNetworkForNetIp(string $netIp, int $vrfId)
    {
        $query = new Query(self::SQL_QUERIES['findCloserParentNetworkForNetIp']);
        $result = Network2::findByQuery($query, [':address' => $netIp, ':vrfId' => $vrfId]);
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
                $this->errors[] = "Parent network {$parentNet->address} for {$$networkIp} consists $hostsCount hosts IP";
                return false;
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }
    
    public function checkAbilityDeleteNetworkById($net_id)
    {
        try {
            $network = self::findByPK($net_id);
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