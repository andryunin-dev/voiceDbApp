<?php

namespace App\Controllers;


use T4\Mvc\Controller;

class Db extends Controller
{
    public function actionDefault()
    {
        header('Location: /db/diagram');
    }
    public function actionDiagram()
    {
    }
}