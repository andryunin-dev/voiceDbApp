<?php
namespace App\Commands;

use App\Components\DSPphones;
use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Phone;
use T4\Console\Command;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

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

        $publishers = Appliance::findAllByType(ApplianceType::CUCM_PUBLISHER);
        foreach ($publishers as $publisher) {
            $cucmIp = $publisher->managementIp;
            $logger->info('START:[cucm]=' . $cucmIp);
            try {
                $registeredPhones = Phone::findAllRegisteredIntoCucm($cucmIp);
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


    public function actionSave()
    {
        $phonesData = file($this->getBackupFileName());
        $this->actionSavePhones($phonesData);
    }

    public function actionSaveFrom(string $cucmIp)
    {
        $phonesData = file($this->getBackupFileName($cucmIp));
        $this->actionSavePhones($phonesData);
    }

    protected function actionSavePhones($phonesData)
    {
        if (empty($phonesData)) {
            $this->writeLn('Warning: empty data');
            die;
        }
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        foreach ($phonesData as $phoneData) {
            $data = json_decode($phoneData);
            if (!is_null($data)) {
                try {

// todo - delete
$start = microtime(true);
                    $data = (new Std())->fromArray($data);
                    (new DSPphones())->process($data);
$end = microtime(true) - $start;
$this->writeLn(' fill and save ' . $end . ' sek ' . $data->name);

                } catch (Exception $e) {
                    $logger->error('UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . $phoneData);
                    echo 'UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . $phoneData . PHP_EOL;
                }
            }
        }
        $this->writeLn('Save phones - OK');
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

