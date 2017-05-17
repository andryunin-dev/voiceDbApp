<?php

namespace App\Controllers;

use App\Components\DataSetProcessor;
use App\Components\WebRequest;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Module;
use App\Models\ModuleItem;
use App\Models\Network;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use App\Models\Vrf;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Mvc\Controller;

class RServer extends Controller
{
    /**
     * Warning: Используется только для ТЕСТОВ
     */
    public function actionTest()
    {
        $this->app->db->default = $this->app->db->phpUnitTest;
        $this->actionDefault();
    }

    public function actionDefault()
    {
        define('SLEEPTIME', 100000); // микросекунды
        define('ITERATIONS', 520); // Колличество попыток получить доступ к db.lock файлу

//        $startTime = microtime(true);

        $logger = new Logger('RServer');
        $logger->pushHandler(new StreamHandler(ROOT_PATH . '/Logs/surveyOfAppliances.log', Logger::DEBUG));

        try {
            // Getting Datasets in JSON format from php://input
            $srcData = json_decode(file_get_contents('php://input'));
            if (null === $srcData) {
                throw new Exception('DATASET: Not a valid JSON input dataset');
            }

//            $srcData = (new Std())->fill($jsonData);
//            if (0 == count($srcData)) {

            if (0 == count($srcData)) {
                throw new Exception('DATASET: Empty an input dataset');
            }

//            (new DataSetProcessor($srcData))->run();




//-----------------------------------------------

            // Determine "Location"
            $office = Office::findByLotusId($srcData->LotusId);
            if (!($office instanceof Office)) {
                throw new Exception('Location not found, LotusId = ' . $srcData->LotusId);
            }

            // Основная обработка данных в транзакции
            try {
                // Заблокировать db.lock файл
                $dbLockFile = fopen(ROOT_PATH_PROTECTED . '/db.lock', 'w');
                if (false === $dbLockFile) {
                    throw new Exception('Can not open the lock file');;
                }

                $blockedFile = flock($dbLockFile, LOCK_EX | LOCK_NB);

                $n = ITERATIONS; // Кол-во попыток доступа к db.lock
                while (false === $blockedFile && 0 !== $n--) {
                    usleep(SLEEPTIME);
                    $blockedFile = flock($dbLockFile, LOCK_EX | LOCK_NB);
                }

                if (false === $blockedFile) {
                    fclose($dbLockFile);
                    throw new Exception('Can not get the lock file');;
                }

                // Обработка данных
                Appliance::getDbConnection()->beginTransaction();

                // Determine "Vendor"
                $vendor = Vendor::findByTitle($srcData->platformVendor);
                if (!($vendor instanceof Vendor)) {
                    $vendor = (new Vendor())
                        ->fill([
                            'title' => $srcData->platformVendor
                        ])
                        ->save();
                }

                // Determine "Platform"
                $platform = $vendor->platforms->filter(
                    function($platform) use (&$srcData) {
                        return $srcData->chassis == $platform->title;
                    }
                )->first();
                if (!($platform instanceof Platform)) {
                    $platform = (new Platform())
                        ->fill([
                            'vendor' => $vendor,
                            'title' => $srcData->chassis
                        ])
                        ->save();
                }

                // Determine "PlatformItem"
                $platformItem = $platform->platformItems->filter(
                    function($platformItem) use (&$srcData) {
                        return $srcData->platformSerial == $platformItem->serialNumber;
                    }
                )->first();
                if (!($platformItem instanceof PlatformItem)) {
                    $platformItem = (new PlatformItem())
                        ->fill([
                            'platform' => $platform,
                            'serialNumber' => $srcData->platformSerial
                        ])
                        ->save();
                }

                // Determine "Software"
                $software = $vendor->software->filter(
                    function($software) use (&$srcData) {
                        return $srcData->applianceSoft == $software->title;
                    }
                )->first();
                if (!($software instanceof Software)) {
                    $software = (new Software())
                        ->fill([
                            'vendor' => $vendor,
                            'title' => $srcData->applianceSoft
                        ])
                        ->save();
                }

                // Determine "SoftwareItem"
                $softwareItem = $software->softwareItems->filter(
                    function($softwareItem) use (&$srcData) {
                        return $srcData->softwareVersion == $softwareItem->version;
                    }
                )->first();
                if (!($softwareItem instanceof SoftwareItem)) {
                    $softwareItem = (new SoftwareItem())
                        ->fill([
                            'software' => $software,
                            'version' => $srcData->softwareVersion
                        ])
                        ->save();
                }

                // Determine "Appliance Type"
                $applianceType = ApplianceType::findByType($srcData->applianceType);
                if (!($applianceType instanceof ApplianceType)) {
                    $applianceType = (new ApplianceType())
                        ->fill([
                            'type' => $srcData->applianceType
                        ])
                        ->save();
                }

                // Determine "Appliance"
                $appliance = ($platformItem->appliance instanceof Appliance) ? $platformItem->appliance : (new Appliance());
                $appliance->fill([
                    'location' => $office,
                    'type' => $applianceType,
                    'vendor' => $vendor,
                    'platform' => $platformItem,
                    'software' => $softwareItem,
                    'details' => [
                        'hostname' => $srcData->hostname,
                    ]
                ])->save();
                if (!($appliance instanceof Appliance)) {
                    throw new Exception('The appliance is not created');
                }

                // Determine the USED "Modules"
                $usedModules = new Collection();
                foreach ($srcData->applianceModules as $moduleDataset) {

                    // Determine "Module"
                    $module = $vendor->modules->filter(
                        function($module) use (&$moduleDataset) {
                            return $moduleDataset->product_number == $module->title;
                        }
                    )->first();

                    if (!($module instanceof Module)) {
                        $module = (new Module())
                            ->fill([
                                'vendor' => $vendor,
                                'title' => $moduleDataset->product_number,
                                'description' => $moduleDataset->description,
                            ])
                            ->save();
                        $vendor->refresh();
                    }

                    // Determine "ModuleItem"
                    $result = $module->moduleItems->filter(
                        function($moduleItem) use (&$moduleDataset) {
                            return $moduleDataset->serial == $moduleItem->serialNumber;
                        }
                    )->first();
                    $moduleItem = ($result instanceof ModuleItem) ? $result : (new ModuleItem());
                    $moduleItem->fill([
                        'module' => $module,
                        'serialNumber' => $moduleDataset->serial,
                        'appliance' => $appliance,
                        'location' => $appliance->location,
                    ])->save();

                    $usedModules->add($moduleItem);
                }

                // Determine the UNUSED "Modules"
                $appliance->refresh();
                $dbModules = $appliance->modules;
                if (0 < $dbModules->count()) {
                    foreach ($appliance->modules as $dbModule) {
                        if (!$usedModules->existsElement(['serialNumber' => $dbModule->serialNumber])) {
                            $dbModule->fill([
                                'appliance' => null,
                            ])->save();
                        }
                    }
                }

                // Determine "DataPort"
                $ip = $srcData->ip;
                $vrf = $srcData->vrf ?? Vrf::instanceGlobalVrf();  // TODO: Добавить в обработку $srcData->vrf
                $portTypeDefault = 'Ethernet';  // TODO: Возможно в будущем будем передавать $portType в запросе, а пока так

                $dataPort = DataPort::findByIpVrf($ip, $vrf);

                if (!($dataPort instanceof DataPort)) {

                    $portType = DPortType::findByType($portTypeDefault);
                    if (!($portType instanceof DPortType)) {
                        $portType = (new DPortType())
                            ->fill([
                                'type' => $portTypeDefault,
                            ])
                            ->save();
                    }

                    $dataPort = (new DataPort())
                        ->fill([
                            'ipAddress' => $ip,
                            'portType' => $portType,
                            'appliance' => $appliance,
                            'vrf' => $vrf,
                        ])
                        ->save();
                }
                if (!($dataPort instanceof DataPort)) {
                    throw new Exception('The DataPort is not created');
                }

                Appliance::getDbConnection()->commitTransaction();

                // Снять блокировку файлв db.lock
                flock($dbLockFile, LOCK_UN);
                fclose($dbLockFile);

            } catch (Exception $e) {
                if (false !== $blockedFile) {
                    Appliance::getDbConnection()->rollbackTransaction();
                }

                throw new Exception($e->getMessage());
            }

//-----------------------------------------------

        } catch (MultiException $e) {
            $errors = [];
            foreach ($e as $error) {
                $errors['errors'][] = $error->getMessage();
                $logger->error($srcData->ip . '-> ' . $error->getMessage());
            }

        } catch (Exception $e) {
            $errors['errors'] = $e->getMessage();
            $logger->error($srcData->ip . '-> ' . $e->getMessage());
        }

        // Подготовить и вернуть ответ
        $httpStatusCode = (isset($errors['errors'])) ? 400 : 202; // Bad Request OR Accepted
        $response = (new Collection())->merge(['ip' => $srcData->ip]);
        $response->merge(['httpStatusCode' => $httpStatusCode]);
        if (400 == $httpStatusCode) {
            $response->merge($errors);
        }

//        $stopTime = microtime(true);
//        $logger->error($srcData->ip . '-> ' . ($stopTime - $startTime));

        echo(json_encode($response->toArray()));

        die;
    }

    public function actionLog()
    {
        $logFile = ROOT_PATH . '/Logs/surveyOfAppliances.log';
        $this->data->logs = file($logFile,FILE_IGNORE_NEW_LINES);
    }

    public function actionInfile()
    {
        $rawdata = file_get_contents('php://input');

//        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_test/');
//        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_dataset_2/');
        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_src/');
        $mt = explode(' ', microtime());
        $rawmc = explode('.', $mt[0]);
        $mc = $rawmc[1];
        $datetime = date('YmdGis', $mt[1]);
        $fileName = $cacheDir . '\\' . 'item_' . $datetime . $mc . '.json';

        $file = fopen($fileName, 'w+');
        fwrite($file,$rawdata);
        fclose($file);
    }
}
