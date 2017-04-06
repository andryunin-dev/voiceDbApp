<?php

namespace App\Controllers;

use T4\Mvc\Controller;

class RClient extends Controller
{
    public function actionDefault()
    {
        $url = "http://voice.loc/rserver";
        $data = [
            'book' => 'JSSJ',
            'author' => 'John',
            'date' => '31.02.2012',
        ];

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
