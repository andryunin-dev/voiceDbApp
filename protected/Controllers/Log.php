<?php

namespace App\Controllers;

use App\Components\Log\ApplianceLog;
use App\Components\Log\DisplayedLog;
use App\Components\Log\LevelLog;
use App\Components\Log\PhoneLog;
use T4\Mvc\Controller;

class Log extends Controller
{
    const LOGFILE = ROOT_PATH . '/Logs/surveyOfAppliances.log';
    const PHONES_ERRORS_LOG_FILE_NAME = 'phoneErrors.txt';

    public function actionErase($type)
    {
        switch ($type) {
            case "appliance":
                (new ApplianceLog())->erase();
                break;
            case "phone":
                (new PhoneLog())->erase();
                break;
            default:
        }
        header('Location: /log/'.$type);
        die;
    }

    public function actionAppliance()
    {
        $level = 'error';
        $fields = ['header', 'ip', 'message'];
        $this->data->records = (
            new DisplayedLog(
                $fields,
                new LevelLog(
                    $level,
                    new ApplianceLog()
                )
            )
        )->list();
        $this->data->eraseApplianceLogUrl = '/log/erase?type=appliance';
    }

    /**
     * Для использования этого action
     * todo - Изменить вывод логов в телефонных скриптах
     */
    public function actionPhone_NEW()
    {
        die;

        $level = 'error';
        $fields = ['header', 'ip', 'message', 'class', 'model', 'name', 'publisher', 'number', 'office', 'city', 'address'];
        $this->data->records = (
        new DisplayedLog(
            $fields,
            new LevelLog(
                $level,
                new PhoneLog()
            )
        )
        )->list();
        $this->data->erasePhoneLogUrl = '/log/erase?type=phone';
        $this->data->phonesLog = '/export/phonesLog'; // todo - экспорт в ТХТ переделать как в actionAppliance()
    }

    public function actionPhone()
    {
        $this->data->phonesLog = '/export/phonesLog';
        $errorLogFile = ROOT_PATH.DS.'Logs'.DS.self::PHONES_ERRORS_LOG_FILE_NAME;

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

        // for export phones errors logs
        file_put_contents($errorLogFile, implode(PHP_EOL, $filteredLogs));

        $logTemplates = [
            'message' => '\[message\]=',
            'class' => '\[class\]=',
            'model' => '\[model\]=',
            'name' => '\[name\]=',
            'ip' => '\[ip\]=',
            'publisher' => '\[publisher\]=',
            'number' => '\[number\]=',
            'office' => '\[office\]=',
            'city' => '\[city\]=',
            'address' => '\[address\]=',
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
            foreach ($splitLog as $k => $item) {
                $splitLog[$k] = trim(preg_replace('~\[\]~', '',$item));
            }

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
