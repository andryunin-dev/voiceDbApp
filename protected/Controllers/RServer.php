<?php

namespace App\Controllers;

use App\Components\DataSetProcessor;
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
//        $startTime = microtime(true);

        $logger = new Logger('DS');
        $logger->pushHandler(new StreamHandler(ROOT_PATH . '/Logs/surveyOfAppliances.log', Logger::DEBUG));

        try {
            $inputDataset = json_decode(file_get_contents('php://input'));
            if (null === $inputDataset) {
                throw new Exception('DATASET: Not a valid JSON input dataset');
            }
            $inputDataset = (new Std())->fill($inputDataset);

            $res = (new DataSetProcessor($inputDataset))->run();
            if (false === $res) {
                throw new Exception('Dataset Processor: runtime error');
            }

        } catch (MultiException $e) {
            $errors = [];
            foreach ($e as $error) {
                $errors['errors'][] = $error->getMessage();
                $logger->error($inputDataset->ip . '-> ' . $error->getMessage());
            }
        } catch (Exception $e) {
            $errors['errors'] = $e->getMessage();
            $logger->error($inputDataset->ip . '-> ' . $e->getMessage());
        }

        // Подготовить и вернуть ответ
        $httpStatusCode = (isset($errors['errors'])) ? 400 : 202; // Bad Request OR Accepted
        $response = (new Collection())->merge(['ip' => $inputDataset->ip]);
        $response->merge(['httpStatusCode' => $httpStatusCode]);
        if (400 == $httpStatusCode) {
            $response->merge($errors);
        }

//        $stopTime = microtime(true);
//        $logger->error($inputDataset->ip . '-> ' . ($stopTime - $startTime));

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
        $inputDataset = json_decode(file_get_contents('php://input'));
        $ip = (isset($inputDataset->ip)) ? str_replace('/', '-', $inputDataset->ip) : '';

//        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_test/');
//        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_dataset_2/');
//        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_dataset_1/');
//        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_cache/');
        $cacheDir = realpath(ROOT_PATH . '/Tmp/Test_src/');

        $mt = explode(' ', microtime());
        $rawmc = explode('.', $mt[0]);
        $mc = $rawmc[1];
        $datetime = date('Y-m-d__G-i-s.', $mt[1]);
        $fileName = $cacheDir . '\\' . $ip . '__' . $datetime . $mc . '.json';

        $file = fopen($fileName, 'w+');
        fwrite($file,$rawdata);
        fclose($file);
        die;
    }
}
