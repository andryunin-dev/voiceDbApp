<?php

namespace App\Controllers;

use App\Components\Parser;
use App\Models\OfficeStatus;
use T4\Mvc\Controller;

class Index
    extends Controller
{

    public function actionDefault()
    {
        $data = '
        Архангельск,Архангельск,"Обводный канал, дом 67/ улица Попова дом 42",64,"Архангельск, ККО Архангельск - Попова"
Архангельск,Архангельск,"просп. Троицкий, д.67/К.Маркса, д.8",2,"Архангельск, пр. Троицкий 67"
        ';
        Parser::lotusTerritory($data);
    }
}