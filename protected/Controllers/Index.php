<?php

namespace App\Controllers;

use App\Components\Parser;
use App\Models\Address;
use App\Models\OfficeStatus;
use T4\Mvc\Controller;

class Index
    extends Controller
{

    public function actionDefault()
    {
        $data = 'Москва,Москва,"МО, Домодедовский р-он, г.Домодедово, мкр.Северный,ул.Логистическая,д.1,ПЛК ""Северное Домодедово"",зд.К-10 ",194,"Москва, ПЛК Северное Домодедово"';
        //$res = str_replace('""', '', $data);
        $res = Parser::lotusTerritory($data);
        var_dump($res);die;
    }
}