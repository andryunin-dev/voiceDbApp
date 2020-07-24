<?php
namespace App\Controllers;

use App\Components\Inventory\UpdateService;
use App\Components\StreamLogger;
use Monolog\Logger;
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
        $response = 'ok';
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            (new UpdateService())->update($data);
        } catch (\Throwable $e) {
            $this->logger()->error($e->getMessage());
            $response = json_encode(['error' => 'Runtime error']);
        }
        echo($response);
        die;
    }

    /**
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function logger(): Logger
    {
        return StreamLogger::instanceWith('DS-INPUT');
    }
}
