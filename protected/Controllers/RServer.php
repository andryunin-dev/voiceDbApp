<?php
namespace App\Controllers;

use App\Components\DSPerror;
use App\Components\WorkAppliance;
use App\Components\WorkCluster;
use App\Components\WorkPrefixes;
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
            $rawInput = file_get_contents('php://input');
            $actualData = json_decode($rawInput);
            if (empty($actualData->dataSetType)) {
                throw new Exception('DATASET: No field dataSetType or empty dataSetType');
            }
            switch ($actualData->dataSetType) {
                case 'appliance':
                    (new WorkAppliance($actualData))->update();
                    break;
                case 'cluster':
                    (new WorkCluster($actualData))->update();
                    break;
                case 'prefixes':
                    (new WorkPrefixes($actualData))->update();
                    break;
                case 'error':
                    (new DSPerror($actualData))->log();
                    break;
                default:
                    throw new \Exception('DATASET: Not known dataSetType');
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
