<?php
namespace App\Controllers;

use App\Components\DSPappliance;
use App\Components\DSPcluster;
use App\Components\DSPerror;
use App\Components\DSPprefixes;
use App\Components\RLogger;
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
        $logger = RLogger::getInstance('R-Server');

        try {
            $rawInput = file_get_contents('php://input');
            $inputDataset = (new Std())->fromArray(json_decode($rawInput));
            if (empty($inputDataset->dataSetType)) {
                throw new Exception('DATASET: No field dataSetType or empty dataSetType');
            }
            switch ($inputDataset->dataSetType) {
                case 'appliance':
                    $logger = RLogger::getInstance('DS-appliance');
                    $result = (new DSPappliance())->process($inputDataset);
                    break;
                case 'cluster':
                    $logger = RLogger::getInstance('DS-cluster');
                    $result = (new DSPcluster())->process($inputDataset);
                    break;
                case 'error':
                    $logger = RLogger::getInstance('DS-error');
                    $result = (new DSPerror($inputDataset))->run();
                    break;
                case 'prefixes':
                    $logger = RLogger::getInstance('DS-prefixes');
                    $result = (new DSPprefixes())->process($inputDataset);
                    break;
                default:
                    throw new Exception('DATASET: Not known dataSetType');
            }
            if (!$result) {
                throw new Exception('R-Server: Runtime error');
            }
        } catch (MultiException $errs) {
            foreach ($errs as $e) {
                $err['errors'][] = $e->getMessage();
                $logger->error('[host]=' . ($inputDataset->hostname ?? '""') . ' [manageIP]=' . ($inputDataset->ip ?? '""') . ' [message]=' . ($e->getMessage() ?? '""') . ' [dataset]=' . $rawInput);
            }
        } catch (Exception $e) {
            $err['errors'][] = $e->getMessage();
            $logger->error('[host]=' . ($inputDataset->hostname ?? '""') . ' [manageIP]=' . ($inputDataset->ip ?? '""') . ' [message]=' . ($e->getMessage() ?? '""') . ' [dataset]=' . $rawInput);
        }

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
