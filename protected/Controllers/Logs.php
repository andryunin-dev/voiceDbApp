<?php

namespace App\Controllers;

use T4\Mvc\Controller;

class Logs extends Controller
{
    public function actionDefault()
    {
        $path = ROOT_PATH . '/Logs/surveyOfAppliances.log';
        $logs = file($path, FILE_IGNORE_NEW_LINES);
        $this->data->records = array_reverse($logs);
    }

}