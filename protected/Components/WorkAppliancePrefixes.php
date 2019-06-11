<?php
namespace App\Components;

use App\Models\Appliance;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Vrf;

class WorkAppliancePrefixes
{
    private $appliance;
    private $actualDataPortsData;
    private $logger;

    /**
     * WorkPprefixes constructor.
     * @param \stdClass $actualData
     * @throws \Exception
     */
    public function __construct(\stdClass $actualData)
    {
        $this->logger = StreamLogger::getInstance('DS-PREFIXES');
        if (!$this->validateDataStructure($actualData)) {
            $this->logger->error('message]=Not valid input data structure; [dataset]='.json_encode($actualData));
            throw new \Exception("Not valid input data structure");
        }
        $this->appliance = Appliance::findByManagementIpVrf($actualData->ip, $actualData->vrf_name);
        if (false === $this->appliance) {
            $this->logger->error('message]=Appliance not found; [dataset]='.json_encode($actualData));
            throw new \Exception("Appliance not found");
        }
        foreach ($actualData->networks as $actualDataPortData) {
            $this->actualDataPortsData[$actualDataPortData->ip_address] = $actualDataPortData;
        }
    }

    /**
     * @throws \Exception
     */
    public function update(): void
    {
        try {
            $updatedDataPortsMap = $this->mapUpdatedDataPorts();
            $this->updateDataPortsMappedByInterfaceIp($updatedDataPortsMap['mappedByInterfaceIp']);
            $this->updateDataPortsMappedByInterface($updatedDataPortsMap['mappedByInterface']);
            $this->createUnMappedDataPorts($updatedDataPortsMap['unmapped']);
            $this->deleteDiedDataPorts($updatedDataPortsMap['died']);
        } catch (\Throwable $e) {
            $this->logger->error('[ip]='.$this->appliance->getManagementDPort()->ipAddress.'; [message]='.$e->getMessage().'; [managementIp]='.$this->appliance->getManagementDPort()->ipAddress);
            throw new \Exception("Error: [ip]=".$this->appliance->getManagementDPort()->ipAddress);
        }
    }

    /**
     * @return array
     */
    private function mapUpdatedDataPorts(): array
    {
        $existsDataPorts = $this->appliance->dataPorts->toArray();
        $actualDataPorts = $this->actualDataPortsData;
        $updatedDataPortsMap = [
            'mappedByInterfaceIp' => [],
            'mappedByInterface' => [],
            'unmapped' => [],
            'died' => [],
        ];
        $updatedDataPortsMap['mappedByInterfaceIp'] = $this->mapDataPortsByInterfaceIp($actualDataPorts, $existsDataPorts);
        $updatedDataPortsMap['mappedByInterface'] = $this->mapDataPortsByInterface($actualDataPorts, $existsDataPorts);
        $updatedDataPortsMap['unmapped'] = $this->mapUnMappedDataPorts($actualDataPorts);
        $updatedDataPortsMap['died'] = $this->mapDiedDataPorts($existsDataPorts);
        return $updatedDataPortsMap;
    }

    /**
     * @param array $actualDataPorts
     * @param array $existsDataPorts
     * @return array
     */
    private function mapDataPortsByInterfaceIp(array &$actualDataPorts, array &$existsDataPorts): array
    {
        $mappedDataPortsByInterfaceIp = [];
        foreach ($actualDataPorts as $aK => $actualDataPort) {
            foreach ($existsDataPorts as $eK => $existsDataPort) {
                if ($actualDataPort->interface == $existsDataPort->details->portName && (new IpTools($actualDataPort->ip_address))->address == $existsDataPort->ipAddress) {
                    $mappedDataPortsByInterfaceIp[$actualDataPort->ip_address] = $existsDataPort->getPk();
                    unset($actualDataPorts[$aK]);
                    unset($existsDataPorts[$eK]);
                }
            }
        }
        return $mappedDataPortsByInterfaceIp;
    }

    /**
     * @param array $actualDataPorts
     * @param array $existsDataPorts
     * @return array
     */
    private function mapDataPortsByInterface(array &$actualDataPorts, array &$existsDataPorts): array
    {
        $mappedDataPortsByInterface = [];
        foreach ($actualDataPorts as $aK => $actualDataPort) {
            foreach ($existsDataPorts as $eK => $existsDataPort) {
                if ($actualDataPort->interface == $existsDataPort->details->portName) {
                    $mappedDataPortsByInterface[$actualDataPort->ip_address] = $existsDataPort->getPk();
                    unset($actualDataPorts[$aK]);
                    unset($existsDataPorts[$eK]);
                }
            }
        }
        return $mappedDataPortsByInterface;
    }

    /**
     * @param array $actualUnMappedDataPorts
     * @return array
     */
    private function mapUnMappedDataPorts(array $actualUnMappedDataPorts): array
    {
        $unMappedDataPorts = [];
        foreach ($actualUnMappedDataPorts as $actualUnMappedDataPort) {
            $unMappedDataPorts[] = $actualUnMappedDataPort->ip_address;
        }
        return $unMappedDataPorts;
    }

    /**
     * @param array $existsDiedDataPorts
     * @return array
     */
    private function mapDiedDataPorts(array $existsDiedDataPorts): array
    {
        $diedDataPorts = [];
        foreach ($existsDiedDataPorts as $existsDiedDataPort) {
            $diedDataPorts[] = $existsDiedDataPort->getPk();
        }
        return $diedDataPorts;
    }

    /**
     * @param array $updatedDataPortsMap
     * @throws \T4\Core\MultiException
     */
    private function updateDataPortsMappedByInterfaceIp(array $updatedDataPortsMap): void
    {
        foreach ($updatedDataPortsMap as $ip => $id) {
            $this->updateDataPort(DataPort::findByPK($id), $this->actualDataPortsData[$ip]);
        }
    }

    /**
     * @param array $updatedDataPortsMap
     */
    private function updateDataPortsMappedByInterface(array $updatedDataPortsMap): void
    {
        foreach ($updatedDataPortsMap as $ip => $id) {
            try {
                DataPort::getDbConnection()->beginTransaction();
                $this->deleteDataPortDuplicate($this->actualDataPortsData[$ip]);
                $this->updateDataPort(DataPort::findByPK($id), $this->actualDataPortsData[$ip]);
                DataPort::getDbConnection()->commitTransaction();
            } catch (\Throwable $e) {
                DataPort::getDbConnection()->rollbackTransaction();
                $this->logger->error('[ip]='.$ip.'; [message]='.$e->getMessage().'; [managementIp]='.$this->appliance->getManagementDPort()->ipAddress);
            }
        }
    }

    /**
     * @param array $unMappedDataPortsMap
     */
    private function createUnMappedDataPorts(array $unMappedDataPortsMap): void
    {
        foreach ($unMappedDataPortsMap as $ip) {
            try {
                DataPort::getDbConnection()->beginTransaction();
                $this->deleteDataPortDuplicate($this->actualDataPortsData[$ip]);
                $this->updateDataPort(new DataPort(), $this->actualDataPortsData[$ip]);
                DataPort::getDbConnection()->commitTransaction();
            } catch (\Throwable $e) {
                DataPort::getDbConnection()->rollbackTransaction();
                $this->logger->error('[ip]='.$ip.'; [message]='.$e->getMessage().'; [managementIp]='.$this->appliance->getManagementDPort()->ipAddress);
            }
        }
    }

    /**
     * @param array $diedDataPortsMap
     */
    private function deleteDiedDataPorts(array $diedDataPortsMap): void
    {
        foreach ($diedDataPortsMap as $id) {
            $diedExistsDataPort = DataPort::findByPK($id);
            if (false !== $diedExistsDataPort) {
                $diedExistsDataPort->delete();
            }
        }
    }

    /**
     * @param \stdClass $data
     */
    private function deleteDataPortDuplicate(\stdClass $data): void
    {
        $foundDataPort = DataPort::findByIpVrf((new IpTools($data->ip_address))->address, Vrf::getInstanceByName($data->vrf_name));
        if (false !== $foundDataPort) {
            $foundDataPort->delete();
        }
    }

    /**
     * @param DataPort $dataPort
     * @param \stdClass $data
     * @throws \T4\Core\MultiException
     */
    private function updateDataPort(DataPort $dataPort, \stdClass $data): void
    {
        $type = [];
        mb_ereg('^\D+', $data->interface, $type);
        $type = (!empty($type)) ? DPortType::getInstanceByType($type[0]) : DPortType::getEmpty();
        $mac = implode(':', str_split(mb_strtolower(mb_ereg_replace(':|\-|\.', '', $data->mac)), 2));
        $ipTool = new IpTools($data->ip_address);
        $vrf = $this->updateVrf($data->vrf_name, $data->vrf_rd);
        $isManagement = ($ipTool->address == $this->appliance->getManagementDPort()->ipAddress) ? true : false; // todo - management Ip ????
        $currentDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P');
        $dataPort->fill([
            'appliance' => $this->appliance,
            'portType' => $type,
            'macAddress' => $mac,
            'ipAddress' => $ipTool->address,
            'masklen' => $ipTool->masklen,
            'vrf' => $vrf,
            'isManagement' => $isManagement,
            'lastUpdate' => $currentDate,
        ]);
        $dataPort->details->portName = $data->interface;
        $dataPort->details->description = $data->description;
        $dataPort->save();
        if (count($dataPort->errors) > 0) {
            foreach ($dataPort->errors as $error) {
                $this->logger->error('[ip]='.$data->ip_address.'; [message]='.$error.'; [managementIp]='.$this->appliance->getManagementDPort()->ipAddress);
            }
        }
    }

    /**
     * @param $name
     * @param $rd
     * @return Vrf
     */
    private function updateVrf($name, $rd): Vrf
    {
        $vrf = Vrf::getInstanceByName($name);
        if ($vrf->rd !== $rd) {
            $vrf->rd = $rd;
            $vrf->save();
        }
        return $vrf;
    }

    /**
     * Validate data structure
     * {
     *   "dataSetType",
     *   "ip",
     *   "vrf_name",
     *   "networks": [
     *     {
     *        "interface",
     *        "ip_address",
     *        "vrf_name",
     *        "vrf_rd",
     *        "description",
     *        "mac",
     *     }
     *   ]
     * }
     * @param \stdClass $data
     * @return boolean
     */
    private function validateDataStructure(\stdClass $data): bool
    {
        if (!isset($data->dataSetType) || !isset($data->ip) || !isset($data->networks)) {
            return false;
        }
        foreach ($data->networks as $network) {
            if (!isset($network->interface) || !isset($network->ip_address) || !isset($network->vrf_name) || !isset($network->vrf_rd) || !isset($network->description) || !isset($network->mac)) {
                return false;
            }
        }
        return true;
    }
}
