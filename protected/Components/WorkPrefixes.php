<?php
namespace App\Components;


use App\Models\Appliance;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Vrf;

class WorkPrefixes
{
    private $appliance;
    private $data;
    private $updatedDataPorts = [];
    private $logger;

    /**
     * WorkPprefixes constructor.
     * @param \stdClass $data
     * @throws \Exception
     */
    public function __construct(\stdClass $data)
    {
        $this->data = $data;
        $this->logger = StreamLogger::getInstance('DS-PREFIXES');
    }

    public function update(): void
    {
        try {
            if (!$this->validateDataStructure()) {
                throw new \Exception("Not valid input data structure");
            }
            $this->appliance = Appliance::findByManagementIpVrf($this->data->ip, $this->data->vrf_name);
            if (false === $this->appliance) {
                throw new \Exception("Appliance not found");
            }
            $this->updateApplianceDataPorts();
            $this->removeFakeDataPorts();
        } catch (\Throwable $e) {
            $this->logger->error('[ip]='.$this->data->ip.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->data));
            throw new \Exception("Error: [ip]=".$this->data->ip);
        }
    }

    private function updateApplianceDataPorts(): void
    {
        foreach ($this->data->networks as $data) {
            try {
                $dataPort = DataPort::findByApplianceInterface($this->appliance, $data->interface);
                if (false === $dataPort) {
                    $dataPort = new DataPort();
                }
                $this->deleteDataPortDuplicate($dataPort, $data);
                $this->updateDataPort($dataPort, $data);
            } catch (\Throwable $e) {
                $this->logger->error('[ip]='.$data->ip_address.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->data));
            }
        }
    }

    /**
     * @param DataPort $dataPort
     * @param \stdClass $data
     */
    private function deleteDataPortDuplicate(DataPort $dataPort, \stdClass $data): void
    {
        $foundDataPort = DataPort::findByIpVrf((new IpTools($data->ip_address))->address, Vrf::getInstanceByName($data->vrf_name));
        if (false !== $foundDataPort && ($dataPort->isNew() || $foundDataPort->getPk() != $dataPort->getPk())) {
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
        $isManagement = ($ipTool->address == $this->data->ip) ? true : false;
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
                $this->logger->error('[ip]='.$data->ip_address.'; [message]='.$error.' [dataset]='.json_encode($this->data));
            }
        }
        $this->updatedDataPorts[] = $dataPort->getPk();
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

    private function removeFakeDataPorts(): void
    {
        foreach ($this->appliance->dataPorts as $dataPort) {
            if (!in_array($dataPort->getPk(), $this->updatedDataPorts)) {
                $dataPort->delete();
            }
        }
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
     * @return boolean
     */
    private function validateDataStructure(): bool
    {
        if (!isset($this->data->dataSetType) || !isset($this->data->ip) || !isset($this->data->networks)) {
            return false;
        }
        foreach ($this->data->networks as $network) {
            if (!isset($network->interface) || !isset($network->ip_address) || !isset($network->vrf_name) || !isset($network->vrf_rd) || !isset($network->description) || !isset($network->mac)) {
                return false;
            }
        }
        return true;
    }
}
