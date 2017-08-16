<?php
namespace App\Commands;

use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\Phone;
use T4\Console\Command;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;

class CucmClient extends Command
{
    const PUBLISHER = 'cmp';

    public function actionDefault()
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));
        $backupFile = realpath(ROOT_PATH . '/Backups/backup_phones.txt');
        file_put_contents($backupFile,'');

        // Получить все зарегистрированные телефоны из всех cucms
        foreach (Appliance::findAllByType(self::PUBLISHER) as $publisher) {
            $logger->info('START:[cucm]=' . $publisher->managementIp);

            try {

                $registeredPhones = Phone::findAllRegisteredIntoCucm($publisher->managementIp);

                // Backup registered phones
                $fd = fopen($backupFile, 'a');
                foreach ($registeredPhones as $phone) {
                    fwrite($fd, json_encode($phone->getData()) . PHP_EOL);
                }
                fclose($fd);

            } catch (MultiException $errs) {
                foreach ($errs as $e) {
                    $logger->error('[cucm]=' . $publisher->managementIp . '; [message]=' . ($e->getMessage() ?? '""'));
                }
            } catch (Exception $e) {
                $logger->error('[cucm]=' . $publisher->managementIp . '; [message]=' . ($e->getMessage() ?? '""'));
            } catch (\SoapFault $e) {
                $logger->error('[cucm]=' . $publisher->managementIp . '; [message]=' . ($e->getMessage() ?? '""'));
            }

            $logger->info('----- Found registered phones: ' . $registeredPhones->count());
            $logger->info('END:[cucm]=' . $publisher->managementIp);
        }


        // Save registered phones
        $phonesData = explode(PHP_EOL, file_get_contents($backupFile));

        foreach ($phonesData as $phoneData) {
            try {
                (new Phone())
                    ->fill(json_decode($phoneData))
                    ->save();
            } catch (Exception $e) {
                $logger->error('UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData));
                echo 'UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData) . PHP_EOL;
            }
        }

        echo 'OK' . PHP_EOL;
    }


    public function actionSave()
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        // Save registered phones
        $backupFile = realpath(ROOT_PATH . '/Backups/backup_phones.txt');
        $phonesData = explode(PHP_EOL, file_get_contents($backupFile));

        foreach ($phonesData as $phoneData) {
            try {
                (new Phone())
                    ->fill(json_decode($phoneData))
                    ->save();
            } catch (Exception $e) {
                $logger->error('UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData));
                echo 'UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData) . PHP_EOL;
            }
        }

        echo 'OK' . PHP_EOL;
    }
}
