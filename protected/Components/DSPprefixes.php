<?php
namespace App\Components;

use App\Models\Appliance;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Vrf;
use T4\Core\Std;

class DSPprefixes extends Std
{
    private $logger;

    private $appliance;
    private $managementIp;
    private $dataPortsFromDb = [];
    private $inputDataPorts = [];


    public function __construct()
    {
        $this->logger = RLogger::getInstance('DS-prefixes');
    }

    /**
     * Update DataPorts of the Appliance
     *
     * @param Std $inputDataPorts
     * @throws \Exception
     */
    public function process(Std $inputDataPorts)
    {
        // Validate input data structure
        if (!$this->validateInputDataStructure($inputDataPorts)) {
            throw new \Exception("Not valid input data structure");
        }

        // Get InputDataPorts
        $this->inputDataPorts = $inputDataPorts->networks->toArray();

        // Get management IpAddress
        $this->managementIp = $inputDataPorts->ip;

        // Find Appliance by managementIp from Db
        $this->appliance = Appliance::findByManagementIP($this->managementIp);
        if (false === $this->appliance) {
            throw new \Exception("Appliance not found");
        }

        // Get Appliance's dataPorts from Db
        $this->appliance->dataPorts->map(
            function ($dataPort) {
                $this->dataPortsFromDb[$dataPort->getPk()] = $dataPort;
            }
        );

        // Find the correspondence between the DataPorts of the Appliance and the DataPorts from the input data
        $matchedDataPorts = $this->dataPortMap($this->dataPortsFromDb, $this->inputDataPorts);

        // Update Appliance's DataPorts
        $this->updateDataPortsMatchedByNameAndIp($matchedDataPorts["matchedByNameAndIp"]);
        $this->updateDataPortsMatchedByName($matchedDataPorts["matchedByName"]);
        $this->createDataPorts($matchedDataPorts["notMatched"]);

        // Delete not updated DataPorts of the Appliance
        $this->deleteNotUpdatedDataPortsFromDb($matchedDataPorts["notUpdated"]);
    }

    /**
     * Determine the match DataPorts from the Input Data to DataPorts of the Appliance
     *
     * @param array $dataPortsFromDb
     * @param array $inputDataPorts
     * @return array
     */
    private function dataPortMap(array $dataPortsFromDb, array $inputDataPorts): array
    {
        $dataPorts = [
            "matchedByNameAndIp" => [],
            "matchedByName" => [],
            "notMatched" => [],
            "notUpdated" => [],
        ];

        // Determine the match DataPorts from the Input Data and DataPorts of the Appliance by the INTERFACE NAME AND IPADDRESS
        foreach ($inputDataPorts as $k => $inputDataPort) {
            foreach ($dataPortsFromDb as $pk => $dataPortFromDb) {
                if ($inputDataPort["interface"] == $dataPortFromDb->details->portName &&
                    (new IpTools($inputDataPort["ip_address"]))->address == $dataPortFromDb->ipAddress
                ) {
                    $dataPorts["matchedByNameAndIp"][$k] = $pk;

                    $dataPortsFromDb = array_diff_key($dataPortsFromDb, array_fill_keys([$pk], ""));
                    $inputDataPorts = array_diff_key($inputDataPorts, array_fill_keys([$k], ""));
                }
            }
        }

        // Determine the match DataPorts from the Input Data and DataPorts of the Appliance by the INTERFACE NAME
        foreach ($inputDataPorts as $k => $inputDataPort) {
            foreach ($dataPortsFromDb as $pk => $dataPortFromDb) {
                if ($inputDataPort["interface"] == $dataPortFromDb->details->portName) {
                    $dataPorts["matchedByName"][$k] = $pk;

                    $dataPortsFromDb = array_diff_key($dataPortsFromDb, array_fill_keys([$pk], ""));
                    $inputDataPorts = array_diff_key($inputDataPorts, array_fill_keys([$k], ""));
                }
            }
        }

        // Determine the not matched DataPorts from the Input Data
        foreach ($inputDataPorts as $k => $inputDataPort) {
            $dataPorts["notMatched"][] = $k;
        }

        // Determine the not updated DataPorts of the Appliance
        foreach ($dataPortsFromDb as $pk => $dataPortFromDb) {
            $dataPorts["notUpdated"][] = $pk;
        }

        return $dataPorts;
    }

    /**
     * Update DataPorts of the Appliance for which there are corresponding DataPorts from the Input Data matched by Name And Ip
     *
     * @param array $matchedDataPorts
     */
    private function updateDataPortsMatchedByNameAndIp(array $matchedDataPorts)
    {
        foreach ($matchedDataPorts as $inputDataPortPk => $dataPortFromDbPk) {
            $inputDataPort = $this->inputDataPorts[$inputDataPortPk];
            $dataPortFromDb = $this->dataPortsFromDb[$dataPortFromDbPk];

            $this->deleteDuplicatesOneDataPort($dataPortFromDb);
            $this->updateDataPort($dataPortFromDb, $inputDataPort);
        }
    }

    /**
     * Update DataPorts of the Appliance for which there are corresponding DataPorts from the Input Data matched by Name
     *
     * @param array $matchedDataPorts
     */
    private function updateDataPortsMatchedByName(array $matchedDataPorts)
    {
        foreach ($matchedDataPorts as $inputDataPortPk => $dataPortFromDbPk) {
            $inputDataPort = $this->inputDataPorts[$inputDataPortPk];
            $dataPortFromDb = $this->dataPortsFromDb[$dataPortFromDbPk];

            $this->deleteFakeDataPortsByIp($inputDataPort);
            $this->updateDataPort($dataPortFromDb, $inputDataPort);
        }
    }

    /**
     * Create new DataPorts of the Appliance
     *
     * @param array $notMatchedDataPorts
     */
    private function createDataPorts(array $notMatchedDataPorts)
    {
        foreach ($notMatchedDataPorts as $inputDataPortPk) {
            $inputDataPort = $this->inputDataPorts[$inputDataPortPk];
            $dataPort = new DataPort();

            $this->deleteFakeDataPortsByIp($inputDataPort);
            $this->updateDataPort($dataPort, $inputDataPort);
        }
    }

    /**
     * Delete not updated DataPorts of the Appliance
     *
     * @param array $notUpdatedDataPortsFromDb
     */
    private function deleteNotUpdatedDataPortsFromDb(array $notUpdatedDataPortsFromDb)
    {
        foreach ($notUpdatedDataPortsFromDb as $pk) {
            $dataPort = $this->dataPortsFromDb[$pk];
            $dataPort->delete();
        }
    }

    /**
     * Update DataPorts of the Appliance
     *
     * @param DataPort $dataPort
     * @param array $data
     */
    private function updateDataPort(DataPort $dataPort, array $data)
    {
        try {
            $type = [];
            mb_ereg('^\D+', $data['interface'], $type);
            $type = (!empty($type)) ? DPortType::getInstanceByType($type[0]) : DPortType::getEmpty();

            $mac = implode(':', str_split(mb_strtolower(mb_ereg_replace(':|\-|\.', '', $data['mac'])), 2));
            $ipTool = new IpTools($data['ip_address']);
            $vrf = $this->updateVrf($data['vrf_name'], $data['vrf_rd']);
            $isManagement = ($ipTool->address == $this->managementIp) ? true : false;
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
            $dataPort->details->portName = $data['interface'];
            $dataPort->details->description = $data['description'];
            $dataPort->save();
        } catch (\Throwable $e) {
            $this->logger->error('[ip]='.$data['ip_address'].'; [message]='.$e->getMessage());
        }
    }

    /**
     * Delete fake DataPorts by IpAddress
     *
     * @param array $data
     */
    private function deleteFakeDataPortsByIp(array $data)
    {
        $ip = (new IpTools($data['ip_address']))->address;

        $fakeDataPorts = DataPort::findAllByIp($ip);
        foreach ($fakeDataPorts as $fakeDataPort) {
            $fakeDataPort->delete();
        }
    }

    /**
     * Delete duplicates one DataPort
     *
     * @param DataPort $dataPort
     */
    private function deleteDuplicatesOneDataPort(DataPort $dataPort)
    {
        $duplicatedDataPorts = DataPort::findAllByIp($dataPort->ipAddress);
        foreach ($duplicatedDataPorts as $duplicateDataPort) {
            if ($duplicateDataPort->getPk() != $dataPort->getPk()) {
                $duplicateDataPort->delete();
            }
        }
    }

    /**
     * Update Vrf
     *
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
     * Validate structure of the input data set
     *
     * @param Std $data
     * {
     *   "dataSetType",
     *   "ip",
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
    private function validateInputDataStructure(Std $data): bool
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
