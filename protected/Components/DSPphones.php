<?php
namespace App\Components;

use App\Exceptions\DblockException;
use App\Exceptions\LocationException;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Cluster;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use App\Models\Vrf;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class DSPphones extends Std
{
    const PHONE = 'phone';
    const PHONESOFT = 'Phone Soft';
    const VENDOR = 'CISCO'; // Todo переделать, а пока так

    protected $dataSets;


    /**
     * DSPphones constructor.
     * @param null $dataSets
     */
    public function __construct($dataSets = null)
    {
        $this->dataSets = $dataSets;
    }


    public function run()
    {
        $location = (Office::findAll())->first()->lotusId;
        foreach ($this->dataSets as $dataSet) {
            $applianceDataSet = (new Std())->fill([
                'applianceType' => self::PHONE,
                'platformVendor' => self::VENDOR,
                'platformTitle' => $dataSet->modelNumber,
                'platformSerial' => ($dataSet->serialNumber) ?? $dataSet->Name,
                'applianceSoft' => self::PHONESOFT,
                'softwareVersion' => $dataSet->versionID,
                'ip' => $dataSet->IpAddress,
                'LotusId' => $location,
                'hostname' => $dataSet->cmName,
                'chassis' => $dataSet->modelNumber,
                'applianceModules' => [],
            ]);
//            var_dump($applianceDataSet);

            $result = (new DSPappliance($applianceDataSet))->run();
            var_dump($result);

        }


        return true;
    }

}
