<?php
namespace App\Components\Inventory;

use App\Components\DateTimeService;
use App\Components\IpTools;
use App\Components\StreamLogger;
use App\Models\Appliance;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Vrf;
use Monolog\Logger;
use T4\Core\MultiException;
use T4\Core\Std;

class PrefixesUpdateService
{
    private $appliance;

    /**
     * @param Appliance $appliance
     * @param array $data
     */
    public function update(Appliance $appliance, array $data): void
    {
        $this->appliance = $appliance;
        $this->updateBgpData($data['bgp_as'], $data['bgp_networks']);
        $this->updateDataPorts($data['networks'], $data['ip']);
    }

    /**
     * @param array $portsData
     * @param string $managementIp
     */
    private function updateDataPorts(array $portsData, string $managementIp): void
    {
        $currentDataPortsPk = [];
        array_walk(
            $portsData,
            function ($portData) use ($managementIp, &$currentDataPortsPk) {
                $ipTool = new IpTools($portData['ip_address']);
                $dataPort = $this->dataPort($ipTool->address, $this->vrf($portData['vrf_name']));
                $dataPort->fill([
                    'ipAddress' => $ipTool->address,
                    'masklen' => $ipTool->masklen,
                    'vrf' => $this->updatedVrf($portData['vrf_name'], $portData['vrf_rd']),
                    'isManagement' => ($portData['ip_address'] == $managementIp),
                    'portType' => $this->portType($portData['interface']),
                    'macAddress' => $this->sanitizedMac($portData['mac']),
                    'appliance' => $this->appliance,
                    'lastUpdate' => (new DateTimeService())->now(),
                ]);
                if (is_null($dataPort->details)) {
                    $dataPort->details = new Std();
                }
                $dataPort->details->portName = $this->portName($portData['interface'], $portData['vni']);
                $dataPort->details->description = $portData['description'];
                $dataPort->save();
                $currentDataPortsPk[] = $dataPort->getPk();
                if (count($dataPort->errors) > 0) {
                    $this->logger()->error($dataPort->errors[0]);
                }
            }
        );
        $this->cleanDataPorts($currentDataPortsPk);
    }

    /**
     * @param array $currentDataPortsPk
     */
    private function cleanDataPorts(array $currentDataPortsPk): void
    {
        $dataPorts = $this->appliance->dataPorts->toArray();
        array_walk(
            $dataPorts,
            function ($dataPort) use ($currentDataPortsPk) {
                if (!in_array($dataPort->getPk(), $currentDataPortsPk)) {
                    $dataPort->delete();
                }
            }
        );
    }

    /**
     * @param string $interface
     * @param string $vni
     * @return string
     */
    private function portName(string $interface, string $vni): string
    {
        return $interface . (!empty($vni) ? ' vni' . $vni : ''); // todo - "vni" сливается с "portname" до решения по "vni"
    }

    /**
     * @param string $interface
     * @return DPortType
     * @throws MultiException
     */
    private function portType(string $interface): DPortType
    {
        return (false !== mb_ereg('^\D+', $interface, $regs))
            ? DPortType::instanceWithType($regs[0])
            : DPortType::getEmpty();
    }

    /**
     * @param string $mac
     * @return string
     */
    private function sanitizedMac(string $mac): string
    {
        return implode(':', str_split(mb_strtolower(mb_ereg_replace(':|\-|\.', '', $mac)), 2));
    }

    /**
     * @param string $ip
     * @param Vrf $vrf
     * @return DataPort
     */
    private function dataPort(string $ip, Vrf $vrf): DataPort
    {
        $dataPort = $this->appliance->findDataPortByIpVrf($ip, $vrf);
        if (false === $dataPort) {
            $this->removeDuplicateDataPortIfExist($ip, $vrf);
            $dataPort = new DataPort();
        }
        return $dataPort;
    }

    /**
     * @param string $ip
     * @param Vrf $vrf
     */
    private function removeDuplicateDataPortIfExist(string $ip, Vrf $vrf): void
    {
        $duplicatePort = DataPort::findByIpVrf($ip, $vrf);
        if (false !== $duplicatePort) {
            $duplicatePort->delete();
        }
    }

    /**
     * @param string $vrfName
     * @return Vrf
     * @throws \T4\Core\MultiException
     */
    private function vrf(string $vrfName): Vrf
    {
        return Vrf::instanceWithName($vrfName);
    }

    /**
     * @param string $name
     * @param string $rd
     * @return Vrf
     * @throws MultiException
     */
    private function updatedVrf(string $name, string $rd): Vrf
    {
        $vrf = Vrf::instanceWithName($name);
        if ($vrf->rd !== $rd) {
            $vrf->rd = $rd;
            $vrf->save();
        }
        return $vrf;
    }

    /**
     * @param string $bgpAs
     * @param array $bgpNetworks
     */
    private function updateBgpData(string $bgpAs, array $bgpNetworks): void
    {
        if (is_null($this->appliance->details)) {
            $this->appliance->details = new Std();
        }
        $this->appliance->details->bgp_as = $bgpAs;
        $this->appliance->details->bgp_networks = $bgpNetworks;
        $this->appliance->save();
    }

    /**
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function logger(): Logger
    {
        return StreamLogger::instanceWith('DS-PREFIXES');
    }
}
