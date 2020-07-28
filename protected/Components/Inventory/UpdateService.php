<?php
namespace App\Components\Inventory;

use App\Components\IpTools;
use App\Components\StreamLogger;
use App\Models\Appliance;
use Monolog\Logger;

class UpdateService
{
    /**
     * @param array $data
     * @throws \Exception
     */
    public function update(array $data): void
    {
        try {
            if (!(new DatasetValidator())->validate($data)) {
                throw new \Exception("Dataset is not valid [dataset]=" . json_encode($data));
            }
            switch ($data['dataSetType']) {
                case 'cluster':
                    (new ClusterUpdateService())->update($data);
                break;
                case 'appliance':
                    $appliance = $this->appliance(
                        $data['platformSerial'],
                        $data['platformVendor'],
                        $data['ip'],
                        $data['vrf_name']
                    );
                    (new ApplianceUpdateService())->update($appliance, $data);
                    break;
                case 'prefixes':
                    $appliance = $this->applianceWithManagementIpVrf($data['ip'], $data['vrf_name']);
                    (new PrefixesUpdateService())->update($appliance, $data);
                    break;
                case 'error':
                    (new ErrorLoggingService())->log($data);
                    break;
                default:
                    throw new \Exception('Unknown dataSetType [dataset]=' . json_encode($data));
            }
        } catch (\Throwable $e) {
            $this->logger()->error('[message]=' . $e->getMessage());
            throw new \Exception('Runtime error');
        }
    }

    /**
     * @param string $serialNumber
     * @param string $platformVendor
     * @param string $managementIp
     * @param string $vrf_name
     * @return Appliance
     * @throws \Exception
     */
    private function appliance(string $serialNumber, string $platformVendor, string $managementIp, string $vrf_name): Appliance
    {
        // Find a appliance by the vendor and the serialNumber
        $appliance = Appliance::findBySerialVendor($serialNumber, $platformVendor);
        if (false === $appliance && !is_null($managementIp) && !is_null($vrf_name)) {
            // Find a blank appliance by the managementIp and the empty serialNumber
            $appliance = Appliance::findByManagementIpVrf((new IpTools($managementIp))->address, $vrf_name);
            $appliance = (false !== $appliance && empty($appliance->platform->serialNumber)) ? $appliance : false;
        }
        if (false === $appliance) {
            throw new \Exception('Appliance is not found [serialNumber]=' . $serialNumber . ' [ip]=' . $managementIp);
        }
        return $appliance;
    }

    /**
     * @param string $managementIp
     * @param string $vrf_name
     * @return Appliance
     * @throws \Exception
     */
    private function applianceWithManagementIpVrf(string $managementIp, string $vrf_name): Appliance
    {
        $appliance = Appliance::findByManagementIpVrf((new IpTools($managementIp))->address, $vrf_name);
        if (false === $appliance) {
            throw new \Exception('Appliance is not found [managementIp]=' . $managementIp . ' [vrf_name]=' . $vrf_name);
        }
        return $appliance;
    }

    /**
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function logger(): Logger
    {
        return StreamLogger::instanceWith('DS-INPUT');
    }
}
