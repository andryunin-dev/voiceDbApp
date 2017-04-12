<?php

namespace App\Controllers;

use App\Components\CiscoParser;
use App\Components\DataProcessor;
use App\Models\Appliance;
use App\Models\DataPort;
use T4\Core\Std;
use T4\Mvc\Controller;
use T4\Core\Exception;
use T4\Core\MultiException;

class RServer extends Controller
{
    public function actionDefault()
    {
        try {
            $rawdata = file_get_contents('php://input');
            var_dump($rawdata);

            $fileName = function () {
                $dir = 'C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\';

                $mt = microtime();
                $mt = explode(' ', $mt);
                $rawmc = explode('.', $mt[0]);

                $datetime = date('YmdGis', $mt[1]);
                $mc = $rawmc[1];

                return $dir . 'item_' . $datetime . $mc . '.json';
            };

            $file = fopen($fileName(), 'w+');
            fwrite($file,$rawdata);
            fclose($file);

        } catch (MultiException $e) {
            $this->data->errors = $e;
        } catch (Exception $e) {
            $this->data->errors = (new MultiException())->add($e);
        }

        die();
    }

    public function actionDP()
    {
        $ip = '192.168.1.1/24';
        $serial = 'FCZ1033997X-';

//        $appliance = (DataPort::findAllByIp($ip))->appliance;
        $appliance = Appliance::findByPK(1);

        // По IP адресу доступно известное устройство из БД
        if (!empty($appliance)) {

            // Это ТЕКУЩЕЕ устройство связанное с IP адресом в БД
            if ($serial == $appliance->platform->serialNumber) {
                echo 'Current Appliance';
                // Обновим данные устройства в БД
            } else {
                // Это ДРУГОЕ устройство, не связанное с этим IP адресом, но существующее в БД
                echo 'Another Appliance';
                // Удалить IP адрес у Current Appliance (определить его по текущему IP адресу)
                // Изменить IP адрес для Another Appliance
                // Изменить Office по IP адресу для Another Appliance
            }
        } else {
            // Устройство не найдено в БД
            echo 'Create new Appliance';
        }

        die();
    }

    public function actionShow()
    {
        $rawdata = (new Std())->fill(json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_2017041011131081254400.json')));
        var_dump($rawdata);

        var_dump(json_last_error_msg());

        die();
    }

    public function actionShowSoftware()
    {
        $rawdata = (new Std())->fill(json_decode(file_get_contents('C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_2017041011131081254400.json')));
//        var_dump($rawdata->software);

        $software = CiscoParser::getSoftware($rawdata->software);

        var_dump($software);
        die();
    }
}
