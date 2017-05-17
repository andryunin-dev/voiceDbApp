<?php

namespace App\Components;

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
//        // Create preprocess function for
//        $matches = [
//            $dataSet->platformVendor,
//            '-CHASSIS',
//            'CHASSIS',
//        ];
//        foreach ($matches as $match) {
//            $dataSet->chassis = mb_ereg_replace($match, '', $dataSet->chassis, "i");
//        }
//        if (false === $dataSet->chassis) {
//            throw new Exception('DATASET: Title chassis ERROR');
//        }


        // Create or Update Appliance

        // TODO: Сделать проверку на принадлежность кластеру

        return true;
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
