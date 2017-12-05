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
            $logAsArray[$key][0] = preg_replace('~(\[.+\])~','$0<br>', $logAsArray[$key][0]);
        }
        $this->data->records = $logAsArray;
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

    public function actionPhones()
    {
        // Фильтруем логи
        $logFiles = scandir(ROOT_PATH . DS . 'Logs');
        $filteredLogs = [];
        foreach ($logFiles as $logFile) {
            if (1 == preg_match('~phones_~', $logFile)) {
                $logs = file(ROOT_PATH . DS . 'Logs' . DS . $logFile, FILE_IGNORE_NEW_LINES);
                foreach ($logs as $log) {
                    if (1 == preg_match('~ERROR~', $log)) {
                        $filteredLogs[] = $log;
                    }
                    if (1 == preg_match('~It is not found in AXL~', $log) && 1 == preg_match('~\[class\]=Phone~', $log)) {
                        $filteredLogs[] = $log;
                    }
                    if (1 == preg_match('~The office is not defined~', $log) && 1 != preg_match('~Default router \(\) is not defined~', $log)) {
                        $filteredLogs[] = $log;
                    }
                    if (1 == preg_match('~It does not have web access~', $log) &&
                        1 != preg_match('~Cisco 30 VIP~', $log) &&
                        1 != preg_match('~Cisco VGC Phone~', $log) &&
                        1 != preg_match('~Cisco ATA~', $log) &&
                        1 != preg_match('~Analog Phone~', $log) &&
                        1 != preg_match('~Cisco 7936~', $log)
                    ) {
                        $filteredLogs[] = $log;
                    }
                }
            }
        }

        $logTemplates = [
            'message' => '\[message\]=',
            'class' => '\[class\]=',
            'model' => '\[model\]=',
            'name' => '\[name\]=',
            'ip' => '\[ip\]=',
            'publisher' => '\[publisher\]=',
            'number' => '\[number\]=',
        ];

        $displayedLogs = [];
        foreach ($filteredLogs as $k => $log) {
            $logKeys = ['title'];
            $logTemplate = '';
            foreach ($logTemplates as $k => $item) {
                if (1 == preg_match('~' . $item . '~', $log)) {
                    if (empty($logTemplate)) {
                        $logTemplate .= $item;
                    } else {
                        $logTemplate .= '|' . $item;
                    }
                    $logKeys[] = $k;
                }
            }
            $splitLog = preg_split('~' . $logTemplate . '~', $log);
            if (false !== $splitLog) {
                $log = array_combine($logKeys, $splitLog);
                $log['title'] = preg_replace('~(\[.+\])~','$0<br>', $log['title']);
                $log['title'] = preg_replace('~CUCM-\d+\.\d+\.\d+\.\d+~','$0<br>', $log['title']);
                $displayedLogs[] = $log;
            }
        }

        $this->data->records = $displayedLogs;
    }
}
