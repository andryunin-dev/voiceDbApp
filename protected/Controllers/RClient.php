<?php

namespace App\Controllers;

use T4\Core\Std;
use T4\Mvc\Controller;

class RClient extends Controller
{
    public function actionDefault()
    {
//        $data = [
//            'book' => 'JSSJ',
//            'author' => 'John',
//            'date' => '31.02.2012',
//        ];

        $data = json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_201704179572700894900.json'));
//        var_dump($data);

//        var_dump(json_encode($data));
//        die;

        $url = "http://voice.loc/rserver/dp";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HEADER, ['Content-type: application/json']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $result =  curl_exec($curl);
        var_dump($result);
        curl_close($curl);

        die;
    }
}
