<?php
namespace App\Components;

use App\Models\Appliance;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Vrf;
use T4\Core\MultiException;
use T4\Core\Std;

class Prefixes
{
    private const SQL = [
        'dataPorts' => '
            SELECT
              dataport.__id AS pk,
              dataport."ipAddress" AS ip,
              vrf.name AS vrf_name,
              dataport.details->>\'portName\' AS interface
            FROM equipment."dataPorts" dataport
            JOIN network.networks network ON dataport.__network_id = network.__id
            JOIN network.vrfs vrf ON network.__vrf_id = vrf.__id
            WHERE dataport.__appliance_id = :appliance_id',
    ];
    private $data;
    private $logger;
    private $appliance;

    public function upgrade(): void
    {
        try {
            if (!$this->isDataValid()) { throw new \Exception('Not valid data'); }
            $this
                ->updateAppliance()
                ->upgradeMapped($this->map());
        } catch (\Throwable $e) {
            $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . $this->data->ip);
            throw new \Exception('Runtime error');
        }
    }

    /**
     * @return $this
     * @throws \Exception
     */
    private function updateAppliance()
    {
        if ($this->hasBgpDataChanged()) {
            if (is_null($this->appliance()->details)) {
                $this->appliance()->details = new Std();
            }
            $this->appliance()->details->bgp_as = $this->data->bgp_as;
            $this->appliance()->details->bgp_networks = $this->data->bgp_networks;
            $this->appliance()->save();
        }
        if ($this->isNoBgpData() && $this->hasDustBgpData()) {
            unset($this->appliance()->details->bgp_as);
            unset($this->appliance()->details->bgp_networks);
            $this->appliance()->save();
        }
        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function isNoBgpData(): bool
    {
        $empty = function ($val) {
            return '' === $val;
        };
        return $empty($this->data->bgp_as);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function hasDustBgpData(): bool
    {
        return !is_null($this->appliance()->details->bgp_as) || !is_null($this->appliance()->details->bgp_networks);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function hasBgpDataChanged(): bool
    {
        $empty = function ($val) {
            return '' === $val;
        };
        return  !$empty($this->data->bgp_as) && (
                is_null($this->appliance()->details->bgp_as) ||
                is_null($this->appliance()->details->bgp_networks) ||
                $this->data->bgp_as != $this->appliance()->details->bgp_as ||
                $this->data->bgp_networks != $this->appliance()->details->bgp_networks->toArray()
            );
    }

    /**
     * @param array $map
     * @return $this
     */
    private function upgradeMapped(array $map)
    {
        $this->updateMappedByPortNameIp($map['byPortNameIp']);
        $this->updateMappedByPortName($map['byPortName']);
        $this->create($map['new']);
        $this->delete($map['died']);
        return $this;
    }

    /**
     * @param array $map
     */
    private function updateMappedByPortNameIp(array $map): void
    {
        foreach ($map as $id => $ip_vrfName) {
            try {
                $this->update(DataPort::findByPK($id), $this->networks()[$ip_vrfName]);
            } catch (\Throwable $e) {
                $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . $ip_vrfName . ' [managementIp]=' . $this->data->ip);
            }
        }
    }

    /**
     * @param array $map
     */
    private function updateMappedByPortName(array $map): void
    {
        foreach ($map as $id => $ip_vrfName) {
            try {
                DataPort::getDbConnection()->beginTransaction();
                $this->deleteDuplicate($this->networks()[$ip_vrfName]);
                $this->update(DataPort::findByPK($id), $this->networks()[$ip_vrfName]);
                DataPort::getDbConnection()->commitTransaction();
            } catch (\Throwable $e) {
                DataPort::getDbConnection()->rollbackTransaction();
                $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . $ip_vrfName . ' [managementIp]=' . $this->data->ip);
            }
        }
    }

    /**
     * @param array $new
     */
    private function create(array $new): void
    {
        foreach ($new as $id => $ip_vrfName) {
            try {
                DataPort::getDbConnection()->beginTransaction();
                $this->deleteDuplicate($this->networks()[$ip_vrfName]);
                $this->update(new DataPort(), $this->networks()[$ip_vrfName]);
                DataPort::getDbConnection()->commitTransaction();
            } catch (\Throwable $e) {
                DataPort::getDbConnection()->rollbackTransaction();
                $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . $ip_vrfName . ' [managementIp]=' . $this->data->ip);
            }
        }
    }

    /**
     * @param array $died
     */
    private function delete(array $died): void
    {
        foreach ($died as $id) {
            try {
                if (false !== $port = DataPort::findByPK($id)) {
                    $port->delete();
                }
            } catch (\Throwable $e) {
                $this->logger->error('[message]=' . $e->getMessage() . ' [id]=' . $id . ' [managementIp]=' . $this->data->ip);
            }
        }
    }

    /**
     * @param \stdClass $data
     */
    private function deleteDuplicate(\stdClass $data): void
    {
        $port = DataPort::findByIp_VrfName((new IpTools($data->ip_address))->address, $data->vrf_name);
        if (false !== $port) {
            $port->delete();
        }
    }

    /**
     * @param DataPort $dataPort
     * @param \stdClass $data
     * @throws MultiException
     */
    private function update(DataPort $dataPort, \stdClass $data): void
    {
        $regs = [];
        $ipTool = new IpTools($data->ip_address);
        $dataPort->fill([
            'appliance' => $this->appliance(),
            'portType' => (false !== mb_ereg('^\D+', $data->interface, $regs)) ? DPortType::instanceWithType($regs[0]) : DPortType::getEmpty(),
            'macAddress' => implode(':', str_split(mb_strtolower(mb_ereg_replace(':|\-|\.', '', $data->mac)), 2)),
            'ipAddress' => $ipTool->address,
            'masklen' => $ipTool->masklen,
            'vrf' => $this->updateVrf($data->vrf_name, $data->vrf_rd),
            'isManagement' => $ipTool->address == (new IpTools($this->data->ip))->address,
            'lastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
        ]);
        if (is_null($dataPort->details)) {
            $dataPort->details = new Std();
        }
        $dataPort->details->portName = $data->interface . (!empty($data->vni) ? ' vni' . $data->vni : ''); // todo - "vni" сливается с "portname" до решения по "vni"
        $dataPort->details->description = $data->description;
        $dataPort->save();
        if (count($dataPort->errors) > 0) {
            throw new \Exception($dataPort->errors[0]);
        }
    }

    private function networks(): array
    {
        $networks = [];
        foreach ($this->data->networks as $network) {
            $networks[(new IpTools($network->ip_address))->address . $network->vrf_name] = $network;
        }
        return $networks;
    }

    /**
     * @param $name
     * @param $rd
     * @return Vrf
     * @throws MultiException
     */
    private function updateVrf($name, $rd): Vrf
    {
        $vrf = Vrf::instanceWithName($name);
        if ($vrf->rd !== $rd) {
            $vrf->rd = $rd;
            $vrf->save();
        }
        return $vrf;
    }

    /**
     * @return array
     * [
     *   'byPortNameIp' => [],
     *   'byPortName' => [],
     *   'new' => [],
     *   'died' => [],
     * ]
     * @throws \Exception
     */
    private function map(): array
    {
        $networks = array_map(
            function ($network) {
                return [
                    'ip' => (new IpTools($network->ip_address))->address,
                    'vrf_name' => $network->vrf_name,
                    'interface' => $network->interface . (!empty($network->vni) ? ' vni' . $network->vni : ''), // todo - "vni" сливается с "portname" до решения по "vni"
                ];
            }, $this->data->networks
        );
        $dbDataPorts = DataPort::getDbConnection()
            ->query(self::SQL['dataPorts'], ['appliance_id' => $this->appliance()->getPk()])
            ->fetchAll(\PDO::FETCH_ASSOC);
        $map = ['byPortNameIp' => [], 'byPortName' => [], 'new' => [], 'died' => [],];
        $mappedNets = [];
        $mappedPorts = [];
        foreach ($networks as $pk => $port) {
            foreach ($dbDataPorts as $k => $dbDataPort) {
                if (0 === strcasecmp($port['interface'], $dbDataPort['interface'])
                    && $port['ip'] == $dbDataPort['ip']
                    && 0 === strcasecmp($port['vrf_name'], $dbDataPort['vrf_name'])
                    && !in_array($k, $mappedPorts)
                ) {
                    $map['byPortNameIp'][$dbDataPort['pk']] = $port['ip'] . $port['vrf_name'];
                    $mappedNets[$pk] = $pk;
                    $mappedPorts[$k] = $k;
                    break;
                }
            }
        }
        foreach ($networks as $pk => $port) {
            foreach ($dbDataPorts as $k => $dbDataPort) {
                if (0 === strcasecmp($port['interface'], $dbDataPort['interface']) && !in_array($pk, $mappedNets) && !in_array($k, $mappedPorts)) {
                    $map['byPortName'][$dbDataPort['pk']] = $port['ip'] . $port['vrf_name'];
                    $mappedNets[$pk] = $pk;
                    $mappedPorts[$k] = $k;
                    break;
                }
            }
        }
        foreach ($networks as $pk => $port) {
            if (!in_array($pk, $mappedNets)) {
                $map['new'][$pk] = $port['ip'] . $port['vrf_name'];
            }
        }
        foreach ($dbDataPorts as $k => $dbDataPort) {
            if (!in_array($k, $mappedPorts)) {
                $map['died'][$k] = $dbDataPort['pk'];
            }
        }
        return $map;
    }

    /**
     * @return Appliance
     * @throws \Exception
     */
    private function appliance(): Appliance
    {
        if (is_null($this->appliance)) {
            if (is_null($this->appliance = DataPort::findByColumn('ipAddress', $this->data->ip)->appliance)) {
                throw new \Exception('Appliance is not found');
            }
        }
        return $this->appliance;
    }

    /**
     * Validate data structure
     * {
     *   "dataSetType",
     *   "bgp_as",
     *   "bgp_networks",
     *   "ip",
     *   "vrf_name",
     *   "networks": [
     *     {
     *        "interface",
     *        "ip_address",
     *        "vrf_name",
     *        "vrf_rd",
     *        "vni",
     *        "description",
     *        "mac",
     *     }
     *   ]
     * }
     * @return boolean
     */
    private function isDataValid(): bool
    {
        if (!isset(
            $this->data->dataSetType,
            $this->data->bgp_as,
            $this->data->bgp_networks,
            $this->data->ip,
            $this->data->vrf_name,
            $this->data->networks
        )) {
            return false;
        }
        foreach ($this->data->networks as $network) {
            if (!isset(
                $network->interface,
                $network->ip_address,
                $network->vrf_name,
                $network->vrf_rd,
                $network->vni,
                $network->description,
                $network->mac
            )) {
                return false;
            }
        }
        return true;
    }

    public function __construct(\stdClass $data)
    {
        $this->data = $data;
        $this->logger = StreamLogger::getInstance('DS-PREFIXES');
    }
}
