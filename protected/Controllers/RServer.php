<?php

namespace App\Controllers;

use T4\Core\Std;
use T4\Mvc\Controller;
use T4\Core\Exception;
use T4\Core\MultiException;

class RServer extends Controller
{
    public function actionDefault()
    {
        try {
            $rawdata = file_get_contents('php://input');
//            var_dump($rawdata);

            $fileName = function () {
                $dir = 'C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\';

                $mt = microtime();
                $mt = explode(' ', $mt);
                $rawmc = explode('.', $mt[0]);

                $datetime = date('YmdGis', $mt[1]);
                $mc = $rawmc[1];

                return $dir . 'item_' . $datetime . $mc . '.json';
            };

            $file = fopen($fileName(), 'w+');
            fwrite($file,$rawdata);
            fclose($file);
        } catch (MultiException $e) {
            $this->data->errors = $e;
        } catch (Exception $e) {
            $this->data->errors = (new MultiException())->add($e);
        }

        die();
    }

    public function actionShow()
    {
        $rawdata = (new Std())->fill(json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\test_data.json')));
        var_dump($rawdata);

        die();
    }
}
