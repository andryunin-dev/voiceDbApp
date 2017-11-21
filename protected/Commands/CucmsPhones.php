<?php

namespace App\Commands;

use App\Components\DSPphones;
use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Phone;
use T4\Console\Command;
use T4\Core\MultiException;
use T4\Core\Std;

class CucmsPhones extends Command
{
    public function actionDefault()
    {
        $publishers = Appliance::findAllByType(ApplianceType::CUCM_PUBLISHER);
        foreach ($publishers as $publisher) {
            $publisherIp = $publisher->managementIp;
            if (false !== $publisherIp) {
                $this->actionGetFrom($publisherIp);
            }
        }
        $this->writeLn('Get phones from all cucms - OK');
        die;
    }

    public function actionGetFrom($publisherIp)
    {
        $logFile = ROOT_PATH . DS . 'Logs' . DS . 'phones_' . preg_replace('~\.~', '_', $publisherIp) . '.log';
        file_put_contents($logFile, '');
        $logger = RLogger::getInstance('CUCM-' . $publisherIp, $logFile);
        try {
            $registeredPhonesData = Phone::findAllRegisteredIntoCucm($publisherIp);
            foreach ($registeredPhonesData as $phoneData) {
                try {
                    $data = (new Std())->fromArray($phoneData->getData());
                    (new DSPphones())->process($data);
                } catch (\Throwable $e) {
                    $logger->error('UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData->getData()));
                }
            }
            $this->writeLn('[publisher]=' . $publisherIp . '; Get phones - OK');
        } catch (MultiException $errs) {
            foreach ($errs as $e) {
                $logger->error('UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""'));
            }
        } catch (\Throwable $e) {
            $logger->error('UPDATE PHONE: [message]=' . ($e->getMessage() ?? '""'));
        }
    }
}
