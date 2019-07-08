<?php
namespace App\Controllers;

use App\Components\DSPerror;
use App\Components\InventoryAppliance;
use App\Components\InventoryCluster;
use App\Components\Prefixes;
use App\Components\StreamLogger;
use T4\Core\Collection;
use T4\Core\Exception;
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
        try {
            $logger = StreamLogger::instanceWith('DS-INPUT');
            $rawInput = file_get_contents('php://input');
            $actualData = json_decode($rawInput);
            if (empty($actualData->dataSetType)) {
                $logger->error('[message]=No field dataSetType or empty dataSetType; [dataset]='.json_encode($actualData));
                throw new Exception('No field dataSetType or empty dataSetType');
            }
            switch ($actualData->dataSetType) {
                case 'cluster':
                    (new InventoryCluster($actualData))->update();
                    break;
                case 'appliance':
                    (new InventoryAppliance($actualData))->update();
                    break;
                case 'prefixes':
                    (new Prefixes($actualData))->upgrade();
                    break;
                case 'error':
                    (new DSPerror($actualData))->log();
                    break;
                default:
                    throw new \Exception('Not known dataSetType');
            }
        } catch (\Throwable $e) {
            $err['errors'][] = $e->getMessage();
        }

        // Вернуть ответ
        $httpStatusCode = (isset($err)) ? 400 : 202;
        $response = (new Collection())
            ->merge(['ip' => $actualData->ip])
            ->merge(['httpStatusCode' => $httpStatusCode])
            ->merge((400 == $httpStatusCode) ? $err : [] );
        echo(json_encode($response->toArray()));
        die;
    }
}
