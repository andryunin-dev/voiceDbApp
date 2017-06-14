<?php

namespace App\Controllers;

use T4\Mvc\Controller;

class Logs extends Controller
{
    const LOGFILE = ROOT_PATH . '/Logs/surveyOfAppliances.log';

    public function actionDefault()
    {
        $logs = file(self::LOGFILE, FILE_IGNORE_NEW_LINES);
        $this->data->records = array_reverse($logs);

        $this->data->eraseLogUrl = '/logs/erase';
    }

    public function actionErase()
    {
        $logfile = fopen(self::LOGFILE, 'w');
        ftruncate($logfile,0);
        fclose($logfile);
        header('Location: /logs');
        die;
    }
}
