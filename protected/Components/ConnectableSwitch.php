<?php
namespace App\Components;

use App\Components\Connection\ConnectionImpl\SshConnection;
use App\Models\Appliance;
use App\Models\Vrf;

class ConnectableSwitch
{
    private const SWITCH = 'switch';
    private $appliance;
    private $managementIp;
    private $connection;

    public function __construct(int $applianceId, string $login, string $password)
    {
        $this->appliance = Appliance::findByPK($applianceId);
        if (false === $this->appliance || self::SWITCH != $this->appliance->type->type) {
            throw new \Exception("Switch (id=" . $applianceId . ") is not exists");
        }
        $this->managementIp = $this->appliance->getManagementDPort()->ipAddress;
        if (is_null($this->managementIp)) {
            throw new \Exception("Switch (id=" . $applianceId . ") has not management dataport");
        }
        $this->connection = new SshConnection($this->managementIp, $login, $password);
    }

    public function phoneNeighbors()
    {
        $neighbors = [];
        $connection = $this->connection->connect();
        if (false !== $connection) {
            $command = 'show cdp neighbors';
            $stream = ssh2_exec($connection, $command);
            $blockMode = true;
            if (false !== $stream && stream_set_blocking($stream, $blockMode)) {
                $maxLength = -1;
                $output = stream_get_contents($stream, $maxLength);
                $connection->close();

                if (false !== $output) {
                    $pattern = 'sep';
                    foreach (explode("\n", $output) as $item) {
                        if (mb_ereg_match($pattern, mb_strtolower($item))) {
                            $fields = mb_split(' ', mb_ereg_replace(' +', ' ', $item));

                            $name = $fields[0];
                            $cdpNeighborPort = $fields[1] . ' ' . $fields[2];
                            $cdpNeighborIP = $this->managementIp;

                            $connectedByPort = 0;
                            $port = 0;
                            if (false !== mb_ereg('port.+', mb_strtolower($item), $port) && false !== mb_ereg('\d+', $port[0], $port)) {
                                $connectedByPort = $port[0];
                            }

                            $location = $this->appliance->location;
                            $cdpNeighborDeviceId = $this->appliance->details->hostname; 


                            var_dump($item);
                            var_dump($fields);
                            var_dump($name);
                            var_dump($cdpNeighborPort);
                            var_dump($connectedByPort);
                            var_dump($cdpNeighborIP);
                            var_dump($location);
                            var_dump($cdpNeighborDeviceId);
                        }
                    }
                }
            }
        }

        die;
        return $neighbors;
    }
}
