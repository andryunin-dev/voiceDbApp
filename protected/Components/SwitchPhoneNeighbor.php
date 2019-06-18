<?php
namespace App\Components;

use App\Models\Office;
use App\Models\PhoneInfo;
use Monolog\Logger;

class SwitchPhoneNeighbor
{
    private $name;
    private $lotusId;
    private $cdpNeighborDeviceId;
    private $cdpNeighborIP;
    private $cdpNeighborPort;
    private $connectionPort;

    public function __construct(string $name, int $lotusId, string $cdpNeighborDeviceId, string $cdpNeighborIP, string $cdpNeighborPort, int $connectionPort)
    {
        $this->name = $name;
        $this->lotusId = $lotusId;
        $this->cdpNeighborDeviceId = $cdpNeighborDeviceId;
        $this->cdpNeighborIP = $cdpNeighborIP;
        $this->cdpNeighborPort = $cdpNeighborPort;
        $this->connectionPort = $connectionPort;
    }

    public function update(): void
    {
        $phoneInfo = PhoneInfo::findByColumn('name', $this->name);
        if (false === $phoneInfo) {
            throw new \Exception('PhoneInfo is not exists [name]=' . $this->name);
        }
        $phoneInfo->fill([
            'cdpNeighborDeviceId' => $this->cdpNeighborDeviceId,
            'cdpNeighborIP' => $this->cdpNeighborIP,
            'cdpNeighborPort' => $this->cdpNeighborPort,
        ])->save();
        $office = Office::findByColumn('lotusId', $this->lotusId);
        if (false === $office) {
            throw new \Exception('Office is not exists [lotusId]=' . $this->lotusId);
        }
        $phoneInfo->phone->fill([
            'location' => $office,
        ])->save();
    }

    public function checkWrongConnectionPort(Logger $logger): void
    {
        if (2 == $this->connectionPort) {
            $logger->error('[message]=' . $this->name . ' is connected on Port 2');
        }
    }
}
