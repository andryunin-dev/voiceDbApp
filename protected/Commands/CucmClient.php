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
        $phones = new Collection();
        foreach (Appliance::findAllByType(self::PUBLISHER) as $publisher) {
            $logger->info('START:[cucm]=' . $publisher->managementIp);

            try {

                $registeredPhones = Phone::findAllRegisteredIntoCucm($publisher->managementIp);
                $phones->merge($registeredPhones);

                // Backup registered phones
                $fd = fopen($backupFile, 'a');
                foreach ($phones as $phone) {
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

            $logger->info('END:[cucm]=' . $publisher->managementIp);
        }
        $logger->info('----- Found registered phones: ' . $phones->count());
        echo 'Found registered phones: ' . $phones->count() . PHP_EOL;


        // Save registered phones
        $phonesData = explode(PHP_EOL, file_get_contents($backupFile));

        foreach ($phonesData as $phoneData) {
            try {
                $phone = (new Phone())
                    ->fill(json_decode($phoneData))
                    ->save();
            } catch (Exception $e) {
                $logger->error('UPDATE PHONE: [name]=' . $phone->name . '; [ip]=' . $phone->ipAddress . '; [cucm]=' . $phone->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phone->getData()));
                echo 'UPDATE PHONE: [name]=' . $phone->name . '; [ip]=' . $phone->ipAddress . '; [cucm]=' . $phone->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phone->getData()) . PHP_EOL;
            }
        }

        echo 'OK' . PHP_EOL;
    }
}
