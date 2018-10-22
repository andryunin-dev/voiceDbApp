<?php

namespace App\Components\Import\NeighborsImpl;

use App\Components\Connection\ConnectionImpl\SshConnectionHandler;
use App\Components\Import\Neighbors;
use App\Models\Office;
use App\Models\PhoneInfo;
use App\ViewModels\DevModulePortGeo;
use Monolog\Logger;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Orm\Exception;

class PhonesCdpNeighborsFromSwitchesBySsh implements Neighbors
{

    private const COMMAND_CDP_NEIGHBORS = 'show cdp neighbors';
    private const MAX_LENGTH = -1;
    private const FASTETHERNET_SHORT_NAME = 'FAS';
    private const FASTETHERNET_FULL_NAME = 'FastEthernet ';
    private const GIGABITETHERNET_SHORT_NAME = 'GIG';
    private const GIGABITETHERNET_FULL_NAME = 'GigabitEthernet ';
    private const RIGHT_CONNECTED_PHONE_PORT = 1;

    private $sshConnectionHandler;
    private $logger;

    /**
     * SwitchNeighborsBySsh constructor.
     * @param SshConnectionHandler $sshConnectionHandler
     * @param Logger $logger
     */
    public function __construct(SshConnectionHandler $sshConnectionHandler, Logger $logger)
    {
        $this->sshConnectionHandler = $sshConnectionHandler;
        $this->logger = $logger;
    }

    /**
     * Import phone neighbors
     *
     * @return mixed|void
     */
    public function importNeighbors()
    {
        // Get polled switches
        $switches = $this->getSwitches();
        // For each switch
        foreach ($switches as $switch) {
            try {
                // Get switch neighbors
                $neighbors = $this->switchGetNeighbors($switch);

                // For each neighbor
                foreach ($neighbors as $neighbor) {
                    // Import phone neighbor
                    $this->importPhoneNeighbor($neighbor, $switch);
                }
            } catch (\Throwable $e) {
                $this->logger->error('UPDATE NEIGHBORS: [message]=' . ($e->getMessage() ?? '""'));
            }
        }
    }

    /**
     * Get polled switches
     *
     * @return DevModulePortGeo
     */
    private function getSwitches()
    {
        $query = (new Query())
            ->select('"managementIp", hostname')
            ->from(DevModulePortGeo::getTableName())
            ->where('"appType" = :switch AND "managementIp" IS NOT NULL AND "platformTitle" NOT IN (:title1, :title2, :title3, :title4, :title5, :title6, :title7, :title8)')
            ->params([
                ':switch' => 'switch',
                ':title1' => 'WS-C4948',
                ':title2' => 'WS-C4948-10GE',
                ':title3' => 'WS-C4948E',
                ':title4' => 'WS-C6509-E',
                ':title5' => 'WS-C6513',
                ':title6' => 'N2K-C2232PP',
                ':title7' => 'N5K-C5548P',
                ':title8' => 'WS-CBS3110G-S-I',
            ])
        ;
        return DevModulePortGeo::findAllByQuery($query);
    }

    /**
     * Get switch neighbors
     *
     * @param $switch
     * @return array
     * @throws Exception
     */
    private function switchGetNeighbors($switch)
    {
        // Establish the connection
        $connection = $this->sshConnectionHandler->getConnect($switch->managementIp);
        if (false == $connection) {
            throw new Exception('Connection to the switch(' . $switch->managementIp . ') is not established');
        }

        // Execute the command
        $stream = ssh2_exec($connection, self::COMMAND_CDP_NEIGHBORS);

        // Get the output of the command
        stream_set_blocking($stream, true);
        $neighbors = stream_get_contents($stream, self::MAX_LENGTH);
        return explode("\n", $neighbors);
    }

    /**
     * Import neighbor, If the neighbor is phone and existed
     *
     * @param $neighbor
     * @param $switch
     */
    private function importPhoneNeighbor($neighbor, $switch)
    {
        try {
            // Defined if the neighbor is a phone
            $phoneInfo = $this->definePhoneBySEP($neighbor);
            // If the neighbor is phone
            if (false !== $phoneInfo) {

                // Update Phone Location
                $this->updatePhoneLocation($phoneInfo, $switch);

                // Update Phone SDP Neighbor
                $this->updatePhoneCdpNeighbor($phoneInfo, $switch, $neighbor);

                // Check Phone Port
                $this->checkPhonePort($neighbor, $phoneInfo);
            }
        } catch (MultiException $errs) {
            foreach ($errs as $e) {
                $this->logger->error('UPDATE NEIGHBORS: [message]=' . ($e->getMessage() ?? '""'));
            }
        } catch (\Throwable $e) {
            $this->logger->error('UPDATE NEIGHBORS: [message]=' . ($e->getMessage() ?? '""'));
        }
    }

    /**
     * Define the "SEP ..." phone and find it in the DB
     *
     * @param $item
     * @return PhoneInfo|bool
     */
    private function definePhoneBySEP($item)
    {
        $phoneInfo = false;
        $item = mb_strtoupper($item);
        if (1 == preg_match('~SEP.{12}~', $item, $phoneName)) {
            $phoneInfo = PhoneInfo::findByColumn('name', $phoneName[0]);
        }
        return $phoneInfo;
    }

    /**
     * Update Phone Location
     *
     * @param PhoneInfo $phoneInfo
     * @param $switch
     */
    private function updatePhoneLocation(PhoneInfo $phoneInfo, $switch)
    {
        // Get Phone Appliance
        $phone = $phoneInfo->phone;

        // Get switch data
        $switchData = DevModulePortGeo::findByColumn('managementIp', $switch->managementIp);

        // Phone Location check - "Phone Location must match Swith Location", if not -> do it
        if ($phone->location->getPk() != $switchData->office_id) {

            // update Phone Location
            $phone->fill(['location' => Office::findByPK($switchData->office_id)]);

            // update Phone->details->switchLocationLastUpdate
            if (is_null($phone->details) || !($phone->details instanceof Std)) {
                $phone->details = new Std();
            }
            $phone->details->switchLocationLastUpdate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P');

            $phone->save();
        }
    }

    /**
     * Update Phone SDP Neighbor
     *
     * @param PhoneInfo $phoneInfo
     * @param $switch
     * @param $switchNeighborData
     * @throws MultiException
     */
    private function updatePhoneCdpNeighbor(PhoneInfo $phoneInfo, $switch, $switchNeighborData)
    {
        // Define the CDP_NEIGHBOR_PORT from "SWITCH NEIGHBOR"
        preg_match('~SEP.{12}\s+\S+\s+\S+~', $switchNeighborData, $dataItems);
        $cdpNeighborPort = trim(preg_replace('~SEP.{12}~', '', $dataItems[0]));
        preg_match('~\S+~', $cdpNeighborPort, $portType);
        $portType = mb_strtoupper($portType[0]);
        switch ($portType) {
            case self::FASTETHERNET_SHORT_NAME:
                $cdpNeighborPort = preg_replace('~\S+\s+~', self::FASTETHERNET_FULL_NAME, $cdpNeighborPort);
                break;
            case self::GIGABITETHERNET_SHORT_NAME:
                $cdpNeighborPort = preg_replace('~\S+\s+~', self::GIGABITETHERNET_FULL_NAME, $cdpNeighborPort);
                break;
        }

        // Define the CDP_NEIGHBORS_DEVICE_ID from "SWITCH HOSTNAME"
        $cdpNeighborDeviceId = $switch->hostname;

        // Define the CDP_NEIGHBORS_IP from "SWITCH MANAGEMENT_IP"
        $cdpNeighborIP = $switch->managementIp;

        // Define the status of changes
        $wereChanges = false;

        // If the Phone cdpNeighborDeviceId has changed, change them
        if (!is_null($cdpNeighborDeviceId) && $cdpNeighborDeviceId != $phoneInfo->cdpNeighborDeviceId) {
            $phoneInfo->fill([
                'cdpNeighborDeviceId' => $cdpNeighborDeviceId,
            ]);
            $wereChanges = true;
        }

        // If the Phone cdpNeighborIP has changed, change them
        if (!is_null($cdpNeighborIP) && $cdpNeighborIP != $phoneInfo->cdpNeighborIP) {
            $phoneInfo->fill([
                'cdpNeighborIP' => $cdpNeighborIP,
            ]);
            $wereChanges = true;
        }

        // If the Phone cdpNeighborPort has changed, change them
        if (!is_null($cdpNeighborPort) && $cdpNeighborPort != $phoneInfo->cdpNeighborPort) {
            $phoneInfo->fill([
                'cdpNeighborPort' => $cdpNeighborPort,
            ]);
            $wereChanges = true;
        }

        // If were changes, save them
        if ($wereChanges) {
            $phoneInfo->save();
        }
    }

    /**
     * Check Phone's Port
     *
     * @param $switchNeighborData
     * @param PhoneInfo $phoneInfo
     * @throws Exception
     */
    private function checkPhonePort($switchNeighborData, PhoneInfo $phoneInfo)
    {
        // Define Phone port number from Switch Neighbor Data
        preg_match('~port\s+\d+~', mb_strtolower($switchNeighborData), $phonePortData);
        preg_match('~\d+~', $phonePortData[0], $phoneConnectedPort);
        $phoneConnectedPort = (int)$phoneConnectedPort[0];

        // If the connection port of the Phone is not 'Port 1', report an error
        if (self::RIGHT_CONNECTED_PHONE_PORT != $phoneConnectedPort) {
            throw new Exception('Phone is connected by Port ' . $phoneConnectedPort . '; [model]=' . $phoneInfo->model . '; [name]=' . $phoneInfo->name . '; [ip]=' . $phoneInfo->phone->dataPorts->first()->ipAddress . '; [number]=' . $phoneInfo->prefix . '-' . $phoneInfo->phoneDN . '; [office]=' . $phoneInfo->phone->location->title . '; [city]=' . $phoneInfo->phone->location->address->city->title . '; [address]=' . $phoneInfo->phone->location->address->address);
        }
    }
}
