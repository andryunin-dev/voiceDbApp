<?php

namespace App\Controllers;

use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Mvc\Controller;

class RClient extends Controller
{
    public function actionDefault()
    {
//        $url = "http://10.99.120.170/rserver/dp";
        $url = "http://10.99.120.170/rsapl";

//        $dir = 'C:\OpenServer\domains\voice.loc\protected\Test_JSON';
        $dir = 'C:\OpenServer\domains\voice.loc\protected\Test_JSON_min';
        $files = scandir($dir);

        foreach ($files as $file) {
            if ('.' == $file || '..' == $file) {
                continue;
            }

            $filePath = realpath($dir . '\\' . $file);
            $data = json_decode(file_get_contents($filePath));

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_HEADER, ['Content-type: application/json']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            $result =  curl_exec($curl);
            var_dump($result);
            curl_close($curl);

//            var_dump($file);
//            var_dump($data);
        }

        die;
    }


    public function actionOne()
    {

        $rawdata = json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON_min\\item_2017041816031761079000.json'));
        $jsondata = json_encode($rawdata);
//        $jsondata = '{"platformSerial":"FOC10475AWV","product_number":"VIC2-2FXS"}';


        $url = "http://10.99.120.170/rsapl";
//        $url = "http://10.99.120.170/rserver";


        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HEADER, ['Content-type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsondata);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result =  curl_exec($curl);
        var_dump($result);
        curl_close($curl);

        die;
    }
}
