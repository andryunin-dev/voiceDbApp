<?php

namespace App\Controllers;

use App\Models\Appliance;
use App\Models\Office;
use App\Models\Platform;
use App\Models\Vendor;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Mvc\Controller;

class Rsapl extends Controller
{
    public function actionDefault()
    {


        try {
            Appliance::getDbConnection()->beginTransaction();

            // Getting Datasets in JSON format from php://input
            $jsonData = json_decode(file_get_contents('php://input'));
            if (null == $jsonData) {
                throw new Exception('Empty an input dataset or Not a valid JSON');
            }

            // Determine the validity of the input data format
            $srcData = (new Std())->fill($jsonData);

            if (!isset($srcData->LotusId)) {
                throw new Exception('No field or Empty: LotusId');
            }
            if (!isset($srcData->platformVendor)) {
                throw new Exception('No field: platformVendor');
            }
            if (!isset($srcData->platformTitle)) {
                throw new Exception('No field: platformTitle');
            }
            if (!isset($srcData->platformSerial)) {
                throw new Exception('No field: platformSerial');
            }
            if (!isset($srcData->applianceType)) {
                throw new Exception('No field: applianceType');
            }
            if (!isset($srcData->applianceModules)) {
                throw new Exception('No field: applianceModules');
            }
            if (!isset($srcData->applianceSoft)) {
                throw new Exception('No field: applianceSoft');
            }
            if (!isset($srcData->softwareVersion)) {
                throw new Exception('No field: softwareVersion');
            }
            if (!isset($srcData->hostname)) {
                throw new Exception('No field: hostname');
            }
            if (!isset($srcData->ip)) {
                throw new Exception('No field: ip');
            }

            // Determine "Location"
            $office = Office::findByLotusId($srcData->LotusId);
            if (false == $office) {
                throw new Exception('Location not found');
            }

            // Determine "Vendor"
            $vendor = Vendor::findByTitle($srcData->platformVendor);
            if (false == $vendor) {
                $vendor = (new Vendor())
                    ->fill([
                        'title' => $srcData->platformVendor
                    ]);
            }

            // Determine "Platform"
            $platformTitle = $srcData->platformTitle;
            $platform = $vendor->platforms->filter(
                function () use ($platformTitle) {
                    if ($platformTitle == $this->title) {
                        return true;
                    }
                }
            );

//            if (false == $platform) {
//                $platform = (new Platform())
//                    ->fill([
//                        'vendor' => $vendor,
//                        'title' => $srcData->platformTitle
//                    ]);
//            }


            var_dump($platform);

            Appliance::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            var_dump($e);
        }
        catch (Exception $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            echo $e->getMessage();
        }


        die;
    }
}