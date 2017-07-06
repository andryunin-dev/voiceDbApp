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
use App\Models\PhoneInfo;
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
//        $location = (Office::findAll())->first()->lotusId;

//// todo -- сделать проверку новый телефон или нет, чтобы не затереть некоторые поля, например (softwareVersion, platformSerial, platformTitle, platformTitle)
//
        foreach ($this->dataSets as $dataSet) {
//            $phoneDataSet = (new Std())->fill([
//                'applianceType' => self::PHONE,
//                'platformVendor' => self::VENDOR,
//                'platformTitle' => ($dataSet->modelNumber) ?? $dataSet->type,
//                'platformSerial' => ($dataSet->serialNumber) ?? $dataSet->name,
//                'applianceSoft' => self::PHONESOFT,
//                'softwareVersion' => $dataSet->versionID,
//                'ip' => $dataSet->ipAddress,
//                'LotusId' => $location,
//                'hostname' => $dataSet->cmName,
//                'chassis' => ($dataSet->modelNumber) ?? $dataSet->type,
//                'applianceModules' => [],
//            ]);
////            var_dump($phoneDataSet);
//
//            $phone = (new DSPappliance($phoneDataSet))->returnAppliance();
//            var_dump($phone);



            $phone = Appliance::findByPK(2274);

            $phoneInfo = (new PhoneInfo())->fill([
//                'phone' => $phone,
//                'type' => $dataSet->type,
//                'name' => $dataSet->name,
//                'macAddress' => ($dataSet->macAddress) ?? substr($dataSet->name,-12),
//                'prefix' => preg_replace('~\..+~','',$dataSet->prefix),
//                'phoneDN' => $dataSet->phoneDN,
//                'status' => $dataSet->status,
//                'description' => $dataSet->description,
//                'css' => $dataSet->css,
//                'devicePool' => $dataSet->devicePool,
//                'alertingName' => $dataSet->alertingName,
//                'partition' => $dataSet->partition,
            ])
            ->save();
            var_dump($phoneInfo);
            die;

        }

        die;

        return true;
    }

}
