<?php

namespace App\Commands;

use App\Components\Cucm\CdpPhoneService;
use App\Components\Cucm\Cucm;
use App\Components\Cucm\CucmsService;
use App\Components\Cucm\Models\RedirectedPhone;
use App\Components\DSPphones;
use App\Components\StreamLogger;
use Monolog\Logger;
use T4\Console\Command;
use T4\Core\Std;

class CucmsPhones extends Command
{
    /**
     * Update registered phones from CUCMs
     * @throws \Exception
     */
    public function actionUpdate(): void
    {
        $logger = $this->cucmLogger();
        $cucms = (new CucmsService())->cucms();
        array_walk(
            $cucms,
            function ($cucm) use ($logger) {
                try {
                    $this->updateFrom($cucm);
                } catch (\Throwable $e) {
                    $logger->error(
                        '[message]=' . $e->getMessage() .
                        ' [cucm]=' . $cucm->ip()
                    );
                }
            }
        );
        $this->writeLn('Phones updated');
    }

    /**
     * Update registered phones from CUCM with $ip
     * @param string $ip CUCM's ipaddress
     * @throws \Exception
     */
    public function actionUpdateFrom(string $ip): void
    {
        $logger = $this->cucmLogger();
        try {
            $this->updateFrom((new CucmsService())->cucmWithIp($ip));
            $this->writeLn('Phones updated');
        } catch (\Throwable $e) {
            $logger->error(
                '[message]=' . $e->getMessage() .
                ' [cucm]=' . $ip
            );
            $this->writeLn('Runtime error');
        }
    }

    /**
     * Updating data on phone neighbors connected to the switches under the CDP protocol
     */
    public function actionUpdateDataOnPhoneCdpNeighbors(): void
    {
        $logger = StreamLogger::instanceWith('PHONES_CDP_NEIGHBORS');
        try {
            (new CdpPhoneService())->updateDataOfPhonesConnectedToPollingSwitches();
            $this->writeLn('Data updated');
        } catch (\Throwable $e) {
            $logger->error('[message]=' . $e->getMessage());
            $this->writeLn('Runtime error');
        }
    }

    /**
     * Update redirected phones
     * @throws \Exception
     */
    public function actionUpdateRedirectedPhones(): void
    {
        $logger = StreamLogger::instanceWith('PHONES_REDIRECTED_UPDATE');
        $cucms = (new CucmsService())->cucms();
        array_walk(
            $cucms,
            function ($cucm) use ($logger) {
                try {
                    $redirectedPhones = $cucm->redirectedPhones();
                    array_walk(
                        $redirectedPhones,
                        function ($redirectedPhone) use ($logger) {
                            try {
                                $redirectedPhone->persist();
                            } catch (\Throwable $e) {
                                $logger->error(
                                    '[message]=' . $e->getMessage()
                                    . ' [redirectedPhone]=' . $redirectedPhone->device
                                    . ' [cucm]= ' . $redirectedPhone->cucm
                                );
                            }
                        }
                    );
                } catch (\Throwable $e) {
                    $logger->error(
                        '[message]=' . $e->getMessage()
                        . ' [cucm]= ' . $cucm->ip()
                    );
                }
            }
        );
        $redirectedPhones = RedirectedPhone::findAll()->toArray();
        array_walk(
            $redirectedPhones,
            function ($redirectedPhone) {
                if ($redirectedPhone->isOverdue()) {
                    $redirectedPhone->delete();
                }
            }
        );
        $this->writeLn('Redirected phones updated');
    }

    /**
     * Update registered phones from CUCM
     * @param Cucm $cucm
     * @throws \SoapFault
     */
    private function updateFrom(Cucm $cucm): void
    {
        $logger = $this->cucmLogger($cucm->ip());
        $registeredPhones = $cucm->registeredPhones();
        array_walk(
            $registeredPhones,
            function ($registeredPhone) use ($logger) {
                try {
                    $dataOfPhone = new Std($registeredPhone->toArray());
                    (new DSPphones())->process($dataOfPhone);
                } catch (\Throwable $e) {
                    $logger->error(
                        '[message]=' . $e->getMessage() .
                        ' [phone]=' . $dataOfPhone['name'] .
                        ' [cucm]=' . $dataOfPhone['publisherIp']
                    );
                }
            }
        );
    }

    /**
     * @param string $ip
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function cucmLogger(string $ip = null): Logger
    {
        if (is_null($ip)) {
            return StreamLogger::instanceWith('CUCM');
        }
        $log = ROOT_PATH.DS.'Logs'.DS.'cucm_'.$ip.'.log';
        file_put_contents($log, '');
        return StreamLogger::instanceWith('CUCM', $log);
    }
}
