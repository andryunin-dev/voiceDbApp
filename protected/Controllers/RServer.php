<?php
namespace App\Controllers;

use App\Components\DSPappliance;
use App\Components\DSPcluster;
use App\Components\DSPerror;
use App\Components\DSPpre;
use App\Components\DSPprefixes;
use App\Components\RLogger;
use App\Models\Appliance;
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
//        $debugLogger = RLogger::getInstance('RServer', realpath(ROOT_PATH . '/Logs/debug.log'));

        try {
            $rawInput = json_decode(file_get_contents('php://input'));
            $inputDataset = (new Std())->fromArray($rawInput);

            $logger = RLogger::getInstance('R-Server');

            if (0 == count($inputDataset)) {
                throw new Exception('DATASET: Not a valid JSON format or empty an input dataset');
            }
            if (empty($inputDataset->dataSetType)) {
                throw new Exception('DATASET: No field dataSetType or empty dataSetType');
            }

//            $debugLogger->info('START: ' . '[ip]=' . $inputDataset->ip . '; [dataSetType]=' . $inputDataset->dataSetType);

            switch ($inputDataset->dataSetType) {
                case 'appliance':
                    $logger = RLogger::getInstance('DS-appliance');
                    $result = (new DSPappliance($inputDataset))->run();
                    if (!($result instanceof Appliance)) {
                        throw new Exception('Runtime error');
                    }
                    break;

                case 'cluster':
                    $logger = RLogger::getInstance('DS-cluster');
                    $result = (new DSPcluster($inputDataset))->run();
                    if (true !== $result) {
                        throw new Exception('Runtime error');
                    }
                    break;

                case 'error':
                    $logger = RLogger::getInstance('DS-error');
                    $result = (new DSPerror($inputDataset))->run();
                    if (true !== $result) {
                        throw new Exception('Runtime error');
                    }
                    break;

                case 'prefixes':
                    $logger = RLogger::getInstance('DS-prefixes');
                    $result = (new DSPprefixes())->process($inputDataset);
                    if (true !== $result) {
                        throw new Exception('Runtime error');
                    }
                    break;

                default:
                    throw new Exception('DATASET: Not known dataSetType');
            }

        } catch (MultiException $errs) {
            foreach ($errs as $e) {
                $err['errors'][] = $e->getMessage();
                $logger->error('[host]=' . ($inputDataset->hostname ?? '""') . ' [manageIP]=' . ($inputDataset->ip ?? '""') . ' [message]=' . ($e->getMessage() ?? '""') . ' [dataset]=' . $rawInput);

//                $debugLogger->error('rserver: ' . '[ip]=' . $inputDataset->ip . '; [error]=' . ($e->getMessage() ?? '""') . '; [dataset]=' . $rawInput);

            }
        } catch (Exception $e) {
            $err['errors'][] = $e->getMessage();
            $logger->error('[host]=' . ($inputDataset->hostname ?? '""') . ' [manageIP]=' . ($inputDataset->ip ?? '""') . ' [message]=' . ($e->getMessage() ?? '""') . ' [dataset]=' . $rawInput);

//            $debugLogger->error('rserver: ' . '[ip]=' . $inputDataset->ip . '; [error]=' . ($e->getMessage() ?? '""') . '; [dataset]=' . $rawInput);
        }

//        $debugLogger->info('END: ' . '[ip]=' . $inputDataset->ip . '; [dataSetType]=' . $inputDataset->dataSetType);
//        $debugLogger->info('---------------------------------------');

        // Вернуть ответ
        $httpStatusCode = (isset($err)) ? 400 : 202;
        $response = (new Collection())
            ->merge(['ip' => $inputDataset->ip])
            ->merge(['httpStatusCode' => $httpStatusCode])
            ->merge((400 == $httpStatusCode) ? $err : [] );
        echo(json_encode($response->toArray()));
        die;
    }
}
