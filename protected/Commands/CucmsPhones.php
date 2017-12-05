<?php

namespace App\Commands;

use App\Components\DSPphones;
use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Phone;
use App\Models\PhoneInfo;
use App\ViewModels\DevModulePortGeo;
use T4\Console\Command;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Dbal\Query;

class CucmsPhones extends Command
{
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

    public function actionGetNeighbors()
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
                            $logger->error('UPDATE NEIGHBORS: [message]=Phone is connected by Port ' . (int)$phonePort[0] . '; [model]=' . $phoneInfo->model . '; [name]=' . $phoneInfo->name . '; [ip]=' . $phoneInfo->phone->dataPorts->first()->ipAddress);
                        }
                    }
                }
            }
        }
        $this->writeLn('UPDATE NEIGHBORS - ok');
    }
}
