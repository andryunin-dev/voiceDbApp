<?php

namespace App\Controllers;

use App\Models\Office;
use T4\Core\Collection;
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
            $rawdata = json_decode(file_get_contents($filePath));
            $jsondata = json_encode($rawdata);

            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($curl, CURLOPT_HEADER, ['Content-type: application/json']);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $jsondata);
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

        $rawdata = json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\Tmp\\Test_JSON_min\\item_2017041816031761079000.json'));
        $jsondata = json_encode($rawdata);

        $url = "http://10.99.120.170/rserver";
//        $url = "http://10.99.120.170/rserver";


        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
//        curl_setopt($curl, CURLOPT_HEADER, ['Content-type: application/json']);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsondata);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        $result =  curl_exec($curl);
        $result =  json_decode(curl_exec($curl));
        curl_close($curl);

        var_dump($result);

        die;
    }

    public function actionTest()
    {
        $a = [];
        $a[1] = (new Std())->fill(['n1' => 'v1']);
        $a[0] = (new Std())->fill(['n0' => 'v0']);
        $a[2] = (new Std())->fill(['n2' => 'v2']);

//        var_dump(new Collection($a));

        $a[1] = 'v1';
        $a[0] = 'v0';
        $a[2] = 'v2';

//        var_dump(new Collection($a));die;
        $a = new Collection();
        var_dump(0 === $a->count());die;
        var_dump($a);die;
    }

}
