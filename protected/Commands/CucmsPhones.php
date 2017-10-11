<?php
namespace App\Commands;

use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Phone;
use T4\Console\Command;
use T4\Core\Exception;
use T4\Core\MultiException;

class CucmsPhones extends Command
{

    public function actionDefault()
    {
        $this->actionGetAll();
        $this->actionSave();
    }


    public function actionGetAll()
    {
        file_put_contents($this->getBackupFileName(),'');
        file_put_contents(realpath(ROOT_PATH . '/Logs/phones.log'),'');
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        // Получить все зарегистрированные телефоны из всех cucms
        foreach (Appliance::findAllByType(ApplianceType::CUCM_PUBLISHER) as $publisher) {
            $cucmIp = $publisher->managementIp;

            $logger->info('START:[cucm]=' . $cucmIp);

            try {

                $registeredPhones = Phone::findAllRegisteredIntoCucm($cucmIp);

                // Backup registered phones
                $fd = fopen($this->getBackupFileName(), 'a');
                foreach ($registeredPhones as $phone) {
                    fwrite($fd, json_encode($phone->getData()) . PHP_EOL);
                }
                fclose($fd);

            } catch (MultiException $errs) {
                foreach ($errs as $e) {
                    $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
                }
            } catch (Exception $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            } catch (\SoapFault $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            }

            $logger->info('----- Found registered phones: ' . $registeredPhones->count());
            $logger->info('END:[cucm]=' . $cucmIp);
        }

        $this->writeLn('Get phones from all cucms - OK');
    }

    public function actionSave()
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        $backupFile = $this->getBackupFileName();
        if (is_readable($backupFile)) {
            $phonesData = file($backupFile);
            foreach ($phonesData as $phoneData) {
                try {

                    $start = microtime(true);
                    $phone = (new Phone())->fill(json_decode($phoneData));
                    $phone->save();
                    $end = microtime(true) - $start;
                    $this->writeLn(' fill and save ' . $end . ' sek');

                } catch (Exception $e) {
                    $logger->error('UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData));
                    echo 'UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData) . PHP_EOL;
                }
            }

            $this->writeLn('Save phones - OK');
        } else {
            $this->writeLn('Save phones from - ERROR');
        }
    }


    /**
     * @param string $cucmIp
     *
     * !!! В командной строке $cucmIp передавать без кавычек
     */
    public function actionGetFrom(string $cucmIp)
    {
        file_put_contents($this->getBackupFileName($cucmIp),'');
        file_put_contents(realpath(ROOT_PATH . '/Logs/phones.log'),'');
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        if (ApplianceType::CUCM_PUBLISHER == Appliance::findByManagementIP($cucmIp)->type->type) {
            $logger->info('START:[cucm]=' . $cucmIp);

            try {

                $registeredPhones = Phone::findAllRegisteredIntoCucm($cucmIp);

                // Backup registered phones
                $fd = fopen($this->getBackupFileName($cucmIp), 'a');
                foreach ($registeredPhones as $phone) {
                    fwrite($fd, json_encode($phone->getData()) . PHP_EOL);
                }
                fclose($fd);

            } catch (MultiException $errs) {
                foreach ($errs as $e) {
                    $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
                }
            } catch (Exception $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            } catch (\SoapFault $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            }

            $logger->info('----- Found registered phones: ' . $registeredPhones->count());
            $logger->info('END:[cucm]=' . $cucmIp);
            $this->writeLn('Get phones from cucms ' . $cucmIp . ' - OK');

        } else {
            $this->writeLn('Cucm ' . $cucmIp . ' does not found');
        }
    }

    public function actionSaveFrom(string $cucmIp)
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        $backupFile = $this->getBackupFileName($cucmIp);
        if (is_readable($backupFile)) {
            $phonesData = file($backupFile);
            foreach ($phonesData as $phoneData) {
                try {

                    $start = microtime(true);
                    $phone = (new Phone())->fill(json_decode($phoneData));
                    $phone->save();
                    $end = microtime(true) - $start;
                    $this->writeLn(' fill and save ' . $end . ' sek');

                } catch (Exception $e) {
                    $logger->error('UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData));
                    echo 'UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData) . PHP_EOL;
                }
            }

            $this->writeLn('Save phones from ' . $cucmIp . ' - OK');
        } else {
            $this->writeLn('Save phones from ' . $cucmIp . ' - ERROR');
        }
    }


    protected function getBackupFileName(string $cucmIP = null)
    {
        if (null == $cucmIP) {
            return ROOT_PATH . DS . 'Backups' . DS . 'backup_phones.txt';
        } else {
            return ROOT_PATH . DS . 'Backups' . DS . 'backup_phones_' . preg_replace('~\.~', '_', $cucmIP) . '.txt';
        }
    }
}

