<?php

namespace App\Commands;

use App\Components\Cucm;
use App\Components\Cucm\CdpPhoneService;
use App\Components\Cucm\Models\RedirectedPhone;
use App\Components\DSPphones;
use App\Components\RLogger;
use App\Components\StreamLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\PhoneInfo;
use App\ViewModels\DevModulePortGeo;
use Monolog\Logger;
use T4\Console\Command;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Dbal\Query;

class CucmsPhones extends Command
{
    /**
     * @throws \Exception
     */
    public function actionGetNeighborsBySnmp()
    {
        $logFile = ROOT_PATH . DS . 'Logs' . DS . 'phones_neighbors.log';
        file_put_contents($logFile, '');
        $logger = RLogger::getInstance('PHONE', $logFile);

        $query = (new Query())
            ->select('"managementIp", hostname')
            ->from(DevModulePortGeo::getTableName())
            ->where('"appType" = :switch AND "managementIp" IS NOT NULL')
            ->params([
                ':switch' => 'switch',
            ])
        ;
        $switches = DevModulePortGeo::findAllByQuery($query);
        foreach ($switches as $switch) {
            $session = new \SNMP(\SNMP::VERSION_2c, $switch->managementIp, 'RegionRS2005');
            $neighbors = $session->walk('.1.3.6.1.4.1.9.9.23.1.2.1.1.6');
            $neighborsPort = $session->walk('.1.3.6.1.4.1.9.9.23.1.2.1.1.7');
            $neighborsInterface = $session->walk('.1.3.6.1.4.1.9.9.23.1.1.1.1.6');
            $session->close();

            foreach ($neighbors as $key => $neighbor) {
                if (1 == preg_match('~SEP.{12}~', $neighbor, $phoneName)) {
                    $phoneInfo = PhoneInfo::findByColumn('name', $phoneName[0]);
                    if (false !== $phoneInfo) {
                        $neighborPortKey = preg_replace('~9.9.23.1.2.1.1.6~', '9.9.23.1.1.1.1.6', $key);
                        $neighborPortKey = preg_replace('~\.\d+$~','', $neighborPortKey);
                        preg_match('~".+"+~', $neighborsInterface[$neighborPortKey], $cdpNeighborPort);

                        $cdpNeighborPort = str_replace('"', '', $cdpNeighborPort[0]);
                        $cdpNeighborDeviceId = $switch->hostname;
                        $cdpNeighborIP = $switch->managementIp;

                        if ($cdpNeighborDeviceId != $phoneInfo->cdpNeighborDeviceId || $cdpNeighborIP != $phoneInfo->cdpNeighborIP || $cdpNeighborPort != $phoneInfo->cdpNeighborPort) {
                            try {
                                $phoneInfo->fill([
                                    'cdpNeighborDeviceId' => $cdpNeighborDeviceId,
                                    'cdpNeighborIP' => $cdpNeighborIP,
                                    'cdpNeighborPort' => $cdpNeighborPort,
                                ]);
                                $phoneInfo->save();
                            } catch (MultiException $errs) {
                                foreach ($errs as $e) {
                                    $logger->error('UPDATE NEIGHBORS: [message]=' . ($e->getMessage() ?? '""'));
                                }
                            } catch (\Throwable $e) {
                                $logger->error('UPDATE NEIGHBORS: [message]=' . ($e->getMessage() ?? '""'));
                            }
                            $this->writeLn($phoneInfo->name . ': ' . $cdpNeighborDeviceId . ' - ' . $cdpNeighborIP . ' - ' . $cdpNeighborPort);
                        }

                        // Если порт подключения телефона не 'Port 1', сообщить об ошибке
                        $portKey = preg_replace('~9.9.23.1.2.1.1.6~', '9.9.23.1.2.1.1.7', $key);
                        preg_match('~\d+~', $neighborsPort[$portKey], $phonePort);
                        if (1 != (int)$phonePort[0]) {
                            $logger->error('UPDATE NEIGHBORS: [message]=Phone is connected by Port ' . (int)$phonePort[0] . '; [model]=' . $phoneInfo->model . '; [name]=' . $phoneInfo->name . '; [ip]=' . $phoneInfo->phone->dataPorts->first()->ipAddress . '; [number]=' . $phoneInfo->prefix . '-' . $phoneInfo->phoneDN . '; [office]=' . $phoneInfo->phone->location->title . '; [city]=' . $phoneInfo->phone->location->address->city->title . '; [address]=' . $phoneInfo->phone->location->address->address);
                        }
                    }
                }
            }
        }
        $this->writeLn('UPDATE NEIGHBORS - ok');
    }

    /**
     * Updating data on phone neighbors connected to the switches under the CDP protocol
     */
    public function actionUpdateDataOnPhoneCdpNeighbors()
    {
        $logger = StreamLogger::instanceWith('PHONES_CDP_NEIGHBORS');
        try {
            (new CdpPhoneService())->updateDataOnPhoneCdpNeighborsConnectedToPollingSwitches();
        } catch (\Throwable $e) {
            $logger->error('[message]=Runtime error [error]=' . $e->getMessage());
            $this->writeLn('Runtime error');
        }
        $this->writeLn('Data has updated');
    }

    /**
     * @param string $name
     */
    public function actionGetPhoneByName(string $name)
    {
        $childsPid = [];
        foreach ($this->publishers() as $publisher) {
            switch ($pid = pcntl_fork()) {
                case -1:
                    $this->writeln('Could not spawn child process');
                    break;
                case 0:
                    // Child process - workhorse
                    try {
                        if (false !== $ip = $publisher->managementIp) {
                            if (false !== $phone = (new Cucm($ip))->phoneWithName($name)) {
                                $this->writeLn($phone->toJson());
                            }
                        }
                    } catch (\Throwable $e) {
                        $this->writeLn(json_encode(['error' => [$ip => $e->getMessage()]]));
                    }
                    exit();
                default:
                    // Keep the pid of child processes in the parent process
                    $childsPid[] = $pid;
            }
        }
        // Wait for all child processes to complete
        foreach ($childsPid as $childPid) {
            pcntl_waitpid($childPid, $status);
        }
        exit();
    }
    public function actionGetPhoneByName2(string $name)
    {
        foreach ($this->publishers() as $publisher) {
            try {
                if (false !== $ip = $publisher->managementIp) {
                    if (false !== $phone = (new Cucm($ip))->phoneWithName($name)) {
                        $this->writeLn($phone->toJson());
                    }
                }
            } catch (\Throwable $e) {
                $this->writeLn(json_encode(['error' => [$ip => $e->getMessage()]]));
            }
        }
        exit();
    }

    /**
     * @throws \Exception
     */
    public function actionUpdate(): void
    {
        foreach ($this->publishers() as $publisher) {
            if (false !== $ip = $publisher->managementIp) {
                $this->actionUpdateFrom($ip);
            }
        }
        $this->writeLn('Phones updated');
    }

    /**
     * @param string $ip
     * @throws \Exception
     */
    public function actionUpdateFrom(string $ip): void
    {
        $logger = $this->cucmLogger($ip);
        try {
            foreach ((new Cucm($ip))->phones() as $phone) {
                try {
                    (new DSPphones())->process(new Std($phone->toArray()));
                } catch (\Throwable $e) {
                    $logger->error('[message]=' . $e->getMessage() . ' [publisher]=' . $ip);
                }
            }
        } catch (\Throwable $e) {
            $logger->error('[message]=' . $e->getMessage() . ' [publisher]=' . $ip);
        }
        $this->writeLn('Phones cucm '. $ip .' updated');
    }

    /**
     * Update redirected phones
     * @throws \Exception
     */
    public function actionUpdateRedirectedPhones(): void
    {
        $logger = StreamLogger::instanceWith('PHONES_REDIRECTED_UPDATE');
        $publishers = $this->publishers()->toArray();
        array_walk(
            $publishers,
            function ($publisher) use ($logger) {
                try {
                    $redirectedPhones = (new Cucm($publisher->managementIp))->redirectedPhones();
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
                        . ' [cucm]= ' . $publisher->managementIp
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
     * @return mixed
     */
    private function publishers()
    {
        return Appliance::findAllByType(ApplianceType::CUCM_PUBLISHER);
    }

    /**
     * @param string $ip
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function cucmLogger(string $ip): Logger
    {
        $log = ROOT_PATH.DS.'Logs'.DS.'cucm_'.$ip.'.log';
        file_put_contents($log, '');
        return StreamLogger::instanceWith('CUCM', $log);
    }
}
