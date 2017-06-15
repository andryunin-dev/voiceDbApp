<?php

namespace App\Controllers;

use T4\Mvc\Controller;

class Logs extends Controller
{
    const LOGFILE = ROOT_PATH . '/Logs/surveyOfAppliances.log';

    public function actionDefault()
    {
        $logs = file(self::LOGFILE, FILE_IGNORE_NEW_LINES);
        $records = array_reverse($logs);
        $logAsArray = [];
        foreach ($records as $key => $value) {
            $logAsArray[$key] = preg_split('~\[host\]=|\[manageIP\]=|\[message\]=|\[dataset\]=~', $value);
        }
        $this->data->records = $logAsArray;
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
