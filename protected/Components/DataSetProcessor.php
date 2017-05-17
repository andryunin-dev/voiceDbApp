<?php

namespace App\Components;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class DataSetProcessor extends Std
{
    const APPLIANCE = 'appliance';
    const CLUSTER = 'cluster';

    protected $dataSet;

    /**
     * DataSetProcessor constructor.
     * @param $dataSet
     */
    public function __construct($dataSet)
    {
        $this->dataSet = $dataSet;
    }

    /**
     * @return bool
     */
    public function run()
    {
        $this->verifyDataSet();

        $dataSetDeviceType = $this->determineDeviceType();

        if (self::APPLIANCE == $dataSetDeviceType) {
            return $this->processApplianceDataSet($this->dataSet);
        }

        if (self::CLUSTER == $dataSetDeviceType) {
            return $this->processClusterDataSet();
        }

        return false;
    }

    /**
     * @return bool
     */
    protected function processClusterDataSet()
    {
        // TODO: Create or Update Cluster

        // Create or Update Cluster's appliances
        foreach ($this->dataSet->clusterAppliances as $dataSetAppliance) {
            $this->processApplianceDataSet($dataSetAppliance);
        }

        return true;
    }

    /**
     * @param $dataSet
     * @return bool
     */
    protected function processApplianceDataSet($dataSet)
    {
        $dataSet = $this->beforeProcessApplianceDataSet($dataSet);

        $office = $this->processLocationDataSet($dataSet->LotusId);
        $vendor = $this->processVendorDataSet($dataSet->platformVendor);
        $platform = $this->processPlatformDataSet($vendor ,$dataSet->chassis);
        $platformItem = $this->processPlatformItemDataSet($platform ,$dataSet->platformSerial);
        $software = $this->processSoftwareDataSet($vendor ,$dataSet->applianceSoft);
        $softwareItem = $this->processSoftwareItemDataSet($software ,$dataSet->softwareVersion);
        $applianceType = $this->processApplianceTypeDataSet($dataSet->applianceType);
        $appliance = $this->processApplianceItemDataSet($office, $applianceType, $vendor, $platformItem, $softwareItem,$dataSet->hostname);


        // Create or Update Appliance

        // TODO: Сделать проверку на принадлежность кластеру

        return true;
    }

    /**
     * @param $dataSet
     * @return mixed
     */
    protected function beforeProcessApplianceDataSet($dataSet)
    {
        $matches = [
            $dataSet->platformVendor,
            '-CHASSIS',
            'CHASSIS',
        ];
        foreach ($matches as $match) {
            $dataSet->chassis = mb_ereg_replace($match, '', $dataSet->chassis, "i");
        }

        return $dataSet;
    }

    /**
     * @param $lotusId
     * @return mixed
     * @throws Exception
     */
    protected function processLocationDataSet($lotusId)
    {
        $office = Office::findByLotusId($lotusId);
        if (!($office instanceof Office)) {
            throw new Exception('Location not found, LotusId = ' . $lotusId);
        }

        return $office;
    }

    /**
     * @param $title
     * @return Vendor|bool
     */
    protected function processVendorDataSet($title)
    {
        $vendor = Vendor::findByTitle($title);
        if (!($vendor instanceof Vendor)) {
            $vendor = (new Vendor())
                ->fill([
                    'title' => $title
                ])
                ->save();
        }

        return $vendor;
    }

    /**
     * @param Vendor $vendor
     * @param $title
     * @return Platform|bool
     */
    protected function processPlatformDataSet(Vendor $vendor, $title)
    {
        $platform = $vendor->platforms->filter(
            function($platform) use ($title) {
                return $title == $platform->title;
            }
        )->first();
        if (!($platform instanceof Platform)) {
            $platform = (new Platform())
                ->fill([
                    'vendor' => $vendor,
                    'title' => $title
                ])
                ->save();
        }

        return $platform;
    }

    /**
     * @param Platform $platform
     * @param $serialNumber
     * @return PlatformItem|bool
     */
    protected function processPlatformItemDataSet(Platform $platform, $serialNumber)
    {
        $platformItem = $platform->platformItems->filter(
            function($platformItem) use ($serialNumber) {
                return $serialNumber == $platformItem->serialNumber;
            }
        )->first();
        if (!($platformItem instanceof PlatformItem)) {
            $platformItem = (new PlatformItem())
                ->fill([
                    'platform' => $platform,
                    'serialNumber' => $serialNumber
                ])
                ->save();
        }

        return $platformItem;
    }

    /**
     * @param Vendor $vendor
     * @param $title
     * @return Software|bool
     */
    protected function processSoftwareDataSet(Vendor $vendor, $title)
    {
        $software = $vendor->software->filter(
            function($software) use ($title) {
                return $title == $software->title;
            }
        )->first();
        if (!($software instanceof Software)) {
            $software = (new Software())
                ->fill([
                    'vendor' => $vendor,
                    'title' => $title
                ])
                ->save();
        }

        return $software;
    }

    /**
     * @param Software $software
     * @param $version
     * @return SoftwareItem|bool
     */
    protected function processSoftwareItemDataSet(Software $software, $version)
    {
        $softwareItem = $software->softwareItems->filter(
            function($softwareItem) use ($version) {
                return $version == $softwareItem->version;
            }
        )->first();
        if (!($softwareItem instanceof SoftwareItem)) {
            $softwareItem = (new SoftwareItem())
                ->fill([
                    'software' => $software,
                    'version' => $version
                ])
                ->save();
        }

        return $softwareItem;
    }

    /**
     * @param $type
     * @return ApplianceType|bool
     */
    protected function processApplianceTypeDataSet($type)
    {
        $applianceType = ApplianceType::findByType($type);
        if (!($applianceType instanceof ApplianceType)) {
            $applianceType = (new ApplianceType())
                ->fill([
                    'type' => $type
                ])
                ->save();
        }

        return $applianceType;
    }

    /**
     * @param Office $office
     * @param ApplianceType $applianceType
     * @param Vendor $vendor
     * @param PlatformItem $platformItem
     * @param SoftwareItem $softwareItem
     * @param $hostname
     * @return Appliance|bool
     */
    protected function processApplianceItemDataSet(
        Office $office,
        ApplianceType $applianceType,
        Vendor $vendor,
        PlatformItem $platformItem,
        SoftwareItem $softwareItem,
        $hostname
    )
    {
        $appliance = ($platformItem->appliance instanceof Appliance) ? $platformItem->appliance : (new Appliance());
        $appliance->fill([
            'location' => $office,
            'type' => $applianceType,
            'vendor' => $vendor,
            'platform' => $platformItem,
            'software' => $softwareItem,
            'details' => [
                'hostname' => $hostname,
            ]
        ])->save();

        return $appliance;
    }

    /**
     * @return bool
     */
    protected function verifyDataSet()
    {
        if (isset($this->dataSet->clusterAppliances)) {
            return $this->verifyClusterDataSet();
        }

        return $this->verifyApplianceDataSet($this->dataSet);
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function verifyClusterDataSet()
    {
        if (!isset($this->dataSet->hostname)) {
            throw new Exception('DATASET: No field hostname for cluster');
        }
        if (empty($this->dataSet->clusterAppliances)) {
            throw new Exception('DATASET: Empty clusterAppliances');
        }

        foreach ($this->dataSet->clusterAppliances as $dataSetAppliance) {
            $this->verifyApplianceDataSet($dataSetAppliance);
        }

        return true;
    }

    /**
     * @param $dataSet
     * @return bool
     * @throws MultiException
     */
    protected function verifyApplianceDataSet($dataSet)
    {
        $errors = new MultiException();

        if (!isset($dataSet->LotusId)) {
            $errors->add(new Exception('DATASET: No field LotusId'));
        }
        if (empty($dataSet->LotusId)) {
            $errors->add(new Exception('DATASET: Empty LotusId'));
        }
        if (!is_numeric($dataSet->LotusId)) {
            $errors->add(new Exception('DATASET: LotusId is not valid'));
        }
        if (!isset($dataSet->platformVendor)) {
            $errors->add(new Exception('DATASET: No field platformVendor'));
        }
        if (!isset($dataSet->platformSerial)) {
            $errors->add(new Exception('DATASET: No field platformSerial'));
        }
        if (!isset($dataSet->applianceType)) {
            $errors->add(new Exception('DATASET: No field applianceType'));
        }
        if (!isset($dataSet->applianceModules)) {
            $errors->add(new Exception('DATASET: No field applianceModules'));
        }
        if (!empty($dataSet->applianceModules)) {
            foreach ($dataSet->applianceModules as $moduleDataset) {

                if (!isset($moduleDataset->product_number)) {
                    $errors->add(new Exception('DATASET: No field applianceModule->product_number'));
                }
                if (!isset($moduleDataset->serial)) {
                    $errors->add(new Exception('DATASET: No field applianceModule->serial'));
                }
                if (!isset($moduleDataset->description)) {
                    $errors->add(new Exception('DATASET: No field applianceModule->description'));
                }
                if (empty($moduleDataset->serial) || empty($moduleDataset->product_number)) {
                    $errors->add(new Exception('DATASET: Empty applianceModule->serial or applianceModule->product_number'));
                }
            }
        }
        if (!isset($dataSet->applianceSoft)) {
            $errors->add(new Exception('DATASET: No field applianceSoft'));
        }
        if (!isset($dataSet->softwareVersion)) {
            $errors->add(new Exception('DATASET: No field softwareVersion'));
        }
        if (!isset($dataSet->hostname)) {
            $errors->add(new Exception('DATASET: No field hostname'));
        }
        if (!isset($dataSet->ip)) {
            $errors->add(new Exception('DATASET: No field ip'));
        }
        if (!isset($dataSet->chassis)) {
            $errors->add(new Exception('DATASET: No field chassis'));
        }

        // Если DataSet не валидный, то заканчиваем работу
        if (0 < $errors->count()) {
            throw $errors;
        }

        return true;
    }

    /**
     * @return string
     */
    protected function determineDeviceType()
    {
        if (isset($this->dataSet->clusterAppliances)) {
            return self::CLUSTER;
        }

        return self::APPLIANCE;
    }
}
