<?php

namespace App\Commands;

use App\Components\DSPphones;
use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Phone;
use App\Models\PhoneInfo;
use App\ViewModels\DevModulePortGeo;
use T4\Console\Application;
use T4\Console\Command;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Dbal\Query;

class CucmsPhones extends Command
{
    const SSH_PORT = 22;
    const COMMAND_CDP_NEIGHBORS = 'show cdp neighbors';
    const MAX_LENGTH = -1;
    const FASTETHERNET_SHORT_NAME = 'FAS';
    const FASTETHERNET_FULL_NAME = 'FastEthernet';
    const GIGABITETHERNET_SHORT_NAME = 'GIG';
    const GIGABITETHERNET_FULL_NAME = 'GigabitEthernet';


    public function actionDefault()
    {
        $publishers = Appliance::findAllByType(ApplianceType::CUCM_PUBLISHER);
        foreach ($publishers as $publisher) {
            $publisherIp = $publisher->managementIp;
            if (false !== $publisherIp) {
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
        $this->writeLn('Get phones from all cucms - OK');
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

    public function actionGetNeighborsBySsh()
    {
        $logFile = ROOT_PATH . DS . 'Logs' . DS . 'phones_neighbors.log';
        file_put_contents($logFile, '');
        $logger = RLogger::getInstance('PHONE', $logFile);

        $app = Application::instance();
        $login = $app->config->ssh->login;
        $password =$app->config->ssh->password;

        $query = (new Query())
            ->select('"managementIp", hostname')
            ->from(DevModulePortGeo::getTableName())
            ->where('"appType" = :switch AND "managementIp" IS NOT NULL AND "platformTitle" NOT IN (:title1, :title2, :title3, :title4, :title5, :title6, :title7, :title8, :title9, :title10)')
            ->params([
                ':switch' => 'switch',
                ':title1' => 'WS-C4948',
                ':title2' => 'WS-C4948-10GE',
                ':title3' => 'WS-C4948E',
                ':title4' => 'WS-C6509-E',
                ':title5' => 'WS-C6513',
                ':title6' => 'WS-C2232PP',
                ':title7' => 'WS-C5548P',
                ':title8' => 'WS-CBS3110G-S-I',
                ':title9' => 'N5K-C5548P',
                ':title10' => 'N2K-C2232PP',
            ])
        ;
        $switches = DevModulePortGeo::findAllByQuery($query);
        foreach ($switches as $switch) {
            // establish a connection
            $connection = ssh2_connect($switch->managementIp, self::SSH_PORT);
            $authorization = ssh2_auth_password($connection, $login, $password);
            if (false === $connection || !$authorization) {
                $logger->warning('UPDATE NEIGHBORS: [message]=Connection to the switch(' . $switch->managementIp . ') is not established');
                continue;
            }

            // execute the command
            $stream = ssh2_exec($connection, self::COMMAND_CDP_NEIGHBORS);

            // get the output of the command
            stream_set_blocking($stream, true);
            $result = stream_get_contents($stream, self::MAX_LENGTH);
            $items = explode("\n", $result);

            // find in the output of the console command phones by name and update their neighbors
            foreach ($items as $item) {
                $item = mb_strtoupper($item);
                if (1 == preg_match('~SEP.{12}~', $item, $phoneName)) {
                    $phoneInfo = PhoneInfo::findByColumn('name', $phoneName[0]);
                    if (false !== $phoneInfo) {
                        // define the cdpNeighborPort
                        preg_match('~SEP.{12}\s+\S+\s+\S+~', $item, $cdpNeighborPort);
                        $cdpNeighborPort = trim(preg_replace('~SEP.{12}~', '', $cdpNeighborPort[0]));
                        preg_match('~\S+~', $cdpNeighborPort, $portType);
                        switch ($portType[0]) {
                            case self::FASTETHERNET_SHORT_NAME:
                                $cdpNeighborPort = preg_replace('~\S+\s+~', self::FASTETHERNET_FULL_NAME, $cdpNeighborPort);
                                break;
                            case self::GIGABITETHERNET_SHORT_NAME:
                                $cdpNeighborPort = preg_replace('~\S+\s+~', self::GIGABITETHERNET_FULL_NAME, $cdpNeighborPort);
                                break;
                        }
                        // define the cdpNeighborDeviceId
                        $cdpNeighborDeviceId = $switch->hostname;
                        // define the cdpNeighborIP
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
                        preg_match('~PORT\s+\d+~', $item, $port);
                        preg_match('~\d+~', $port[0], $phonePort);
                        if (1 != (int)$phonePort[0]) {
                            $logger->error('UPDATE NEIGHBORS: [message]=Phone is connected by Port ' . (int)$phonePort[0] . '; [model]=' . $phoneInfo->model . '; [name]=' . $phoneInfo->name . '; [ip]=' . $phoneInfo->phone->dataPorts->first()->ipAddress . '; [number]=' . $phoneInfo->prefix . '-' . $phoneInfo->phoneDN . '; [office]=' . $phoneInfo->phone->location->title . '; [city]=' . $phoneInfo->phone->location->address->city->title . '; [address]=' . $phoneInfo->phone->location->address->address);
                        }
                    }
                }
            }
        }
        $this->writeLn('UPDATE NEIGHBORS - ok');
    }
}
