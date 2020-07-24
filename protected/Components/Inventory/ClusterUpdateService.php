<?php
namespace App\Components\Inventory;

use App\Components\StreamLogger;
use App\Models\Appliance;
use App\Models\Cluster;
use Monolog\Logger;

class ClusterUpdateService
{
    private $cluster;

    /**
     * @param array $data
     * @throws \Exception
     */
    public function update(array $data)
    {
        $currentDevicesPkFormingCluster = [];
        $clusterTitle = $data['hostname'];
        $clusterMasterSerialNumber = !empty($data['clusterAppliances']) ? $data['clusterAppliances'][0]['platformSerial'] : '';
        array_walk(
            $data['clusterAppliances'],
            function ($applianceData) use ($clusterTitle, $clusterMasterSerialNumber, &$currentDevicesPkFormingCluster) {
                try {
                    if ($applianceData['platformSerial'] !== $clusterMasterSerialNumber) {
                        $applianceData['ip'] = null;
                        $applianceData['vrf_name'] = null;
                    }
                    $appliance = $this->appliance($applianceData['platformSerial'], $applianceData['platformVendor']);
                    $appliance->fill([
                        'cluster' => $this->cluster($clusterTitle)
                    ]);
                    (new ApplianceUpdateService())->update($appliance, $applianceData);
                    $currentDevicesPkFormingCluster[] = $appliance->getPk();
                } catch (\Throwable $e) {
                    $this->logger()->error(
                        '[message]=' . $e->getMessage() .
                        ' [appliance_serialNumber]=' . $applianceData['platformSerial'] .
                        ' [cluster]=' . $clusterTitle
                    );
                }
            }
        );
        $this->updateCompositionOfCluster($currentDevicesPkFormingCluster);
    }

    /**
     * @param array $currentDevicesPkFormingCluster
     */
    private function updateCompositionOfCluster(array $currentDevicesPkFormingCluster): void
    {
        $appliances = $this->cluster->appliances->toArray();
        array_walk(
            $appliances,
            function ($appliance) use ($currentDevicesPkFormingCluster) {
                if (!in_array($appliance->getPk(), $currentDevicesPkFormingCluster)) {
                    $appliance->fill(['cluster' => null])->save();
                }
            }
        );
    }

    /**
     * @param string $serialNumber
     * @param string $vendor
     * @return Appliance|mixed
     */
    private function appliance(string $serialNumber, string $vendor): Appliance
    {
        $appliance = Appliance::findBySerialVendor($serialNumber, $vendor);
        if (false === $appliance) {
            $appliance = new Appliance();
        }
        return $appliance;
    }

    /**
     * @param string $title
     * @return Cluster
     * @throws \T4\Core\MultiException
     */
    private function cluster(string $title): Cluster
    {
        if (is_null($this->cluster)) {
            $this->cluster = Cluster::instanceWithTitle($title);
        }
        return $this->cluster;
    }

    /**
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function logger(): Logger
    {
        return StreamLogger::instanceWith('DS-CLUSTER');
    }
}
