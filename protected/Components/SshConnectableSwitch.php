<?php
namespace App\Components;

use App\Components\Connection\Connection;
use App\Models\Appliance;
use Monolog\Logger;

class SshConnectableSwitch
{
    private const SWITCH = 'switch';
    private $id;
    private $connection;
    private $logger;

    public function __construct(int $id, Connection $connection, Logger $logger)
    {
        $this->id = $id;
        $this->connection = $connection;
        $this->logger = $logger;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function phoneNeighbors(): array
    {
        $isPhone = function (string $neighbor): bool {
            $phonePattern = 'sep';
            return mb_ereg_match($phonePattern, mb_strtolower($neighbor));
        };
        $connectionPhonePort = function (string $commandOutput): int {
            $connectionPort = -1; // not known port
            $subString = '';
            if (false !== mb_ereg('port.+', mb_strtolower($commandOutput), $subString) && false !== mb_ereg('\d+', $subString[0], $subString)) {
                $connectionPort = (int)$subString[0];
            }
            return $connectionPort;
        };
        $phoneNeighbor = function (string $neighbor, Appliance $switch) use ($connectionPhonePort): SwitchPhoneNeighbor {
            $fields = mb_split(' ', mb_ereg_replace(' +', ' ', $neighbor));
            $name = $fields[0];
            $cdpNeighborPort = $fields[1] . $fields[2];
            $lotusId = $switch->location->lotusId;
            $cdpNeighborDeviceId = $switch->details->hostname;
            $cdpNeighborIP = $switch->getManagementDPort()->ipAddress;
            $connectionPort = $connectionPhonePort($neighbor);
            $phoneNeighbor = new SwitchPhoneNeighbor(
                $name,
                $lotusId,
                $cdpNeighborDeviceId,
                $cdpNeighborIP,
                $cdpNeighborPort,
                $connectionPort
            );
            return $phoneNeighbor;
        };
        try {
            $phoneNeighbors = [];
            $switch = $this->appliance();
            $neighbors = explode("\n", $this->commandOutput('show cdp neighbors'));
            foreach ($neighbors as $neighbor) {
                try {
                    if ($isPhone($neighbor)) {
                        $phoneNeighbors[] = $phoneNeighbor($neighbor, $switch);
                    }
                } catch (\Throwable $e) {
                    $this->logger->error('[message]=' . $e->getMessage() . ' [ip]=' . Appliance::findByPK($this->id)->getManagementDPort()->ipAddress);
                }
            }
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        }
        return $phoneNeighbors;
    }

    /**
     * @return Appliance
     * @throws \Exception
     */
    private function appliance(): Appliance
    {
        $appliance = Appliance::findByPK($this->id);
        if (false === $appliance || self::SWITCH != mb_strtolower($appliance->type->type)) {
            throw new \Exception('Switch (id=' . $this->id . ') is not exists');
        }
        if (is_null($appliance->getManagementDPort()->ipAddress)) {
            throw new \Exception("Switch (id=" . $this->id . ") has not management ip");
        }
        return $appliance;
    }

    /**
     * @param string $command
     * @return string
     * @throws \Exception
     */
    private function commandOutput(string $command): string
    {
        $resource = $this->connection->connect();
        if (false === $resource) {
            throw new \Exception('Switch is not accessible');
        }
        $stdOut = @ssh2_exec($resource, $command); // @ - silence operator (no warning)
        $strErr = @ssh2_fetch_stream($stdOut, SSH2_STREAM_STDERR);
        $blockMode = true;
        stream_set_blocking($strErr, $blockMode);
        stream_set_blocking($stdOut, $blockMode);
        $maxLengthOfString = -1;
        $output = @stream_get_contents($stdOut, $maxLengthOfString); // @ - silence operator (no warning)
        $errors = @stream_get_contents($strErr, $maxLengthOfString);
        fclose($strErr);
        fclose($stdOut);
        if (false === $output) {
            throw new \Exception('ssh2_exec errors [errors]=' . $errors);
        }
        $this->connection->close();
        return $output;
    }
}
