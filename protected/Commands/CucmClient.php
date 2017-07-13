<?php
namespace App\Commands;

use App\Components\RLogger;
use App\Models\Cucm;
use App\Models\Phone;
use T4\Console\Command;
use T4\Core\Exception;
use T4\Core\MultiException;

class CucmClient extends Command
{
    public function actionDefault()
    {
        $logger = RLogger::getInstance('Cucm');

        foreach (Cucm::findAllPublishers() as $publisher) {
            $logger->info('START:[cucm]=' . $publisher->managementIp);
            try {

                foreach ($publisher->getRegisteredPhones() as $phone) {
                    $phone->update();
                }

            }catch (MultiException $errs) {
                foreach ($errs as $e) {
                    $logger->error('[cucm]=' . $publisher->managementIp . '; [message]=' . ($e->getMessage() ?? '""'));
                }
                echo 'CUCM[' . $publisher->managementIp .'] - ERRORS' . PHP_EOL;
            } catch (Exception $e) {
                $logger->error('[cucm]=' . $publisher->managementIp . '; [message]=' . ($e->getMessage() ?? '""'));
                echo 'CUCM[' . $publisher->managementIp .'] - ERROR' . PHP_EOL;
            } catch (\SoapFault $e) {
                $logger->error('[cucm]=' . $publisher->managementIp . '; [message]=' . ($e->getMessage() ?? '""'));
                echo 'CUCM[' . $publisher->managementIp .'] - ERROR' . PHP_EOL;
            }
            $logger->info('END:[cucm]=' . $publisher->managementIp);
        }

        echo 'OK' . PHP_EOL;
    }
}
