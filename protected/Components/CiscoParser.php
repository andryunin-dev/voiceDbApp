<?php

namespace App\Components;

use T4\Core\Std;

class CiscoParser extends Std
{
    public static function getSoftware(String $data)
    {
        if (!empty($data)) {

            $dataSrc = explode("\n", $data);
            $dataSrc = explode(',', $dataSrc[0]);

            $softwareData = new Std();
            $softwareData->title = trim($dataSrc[1]);
            $softwareData->version = trim($dataSrc[2] . $dataSrc[3]);
            $softwareData->details = trim($dataSrc[0]);

            return $softwareData;
        }
    }
}