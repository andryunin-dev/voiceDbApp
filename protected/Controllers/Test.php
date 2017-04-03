<?php

namespace App\Controllers;


use App\Components\Ip;
use App\Components\Sorter;
use App\Models\Address;
use App\Models\ApplianceType;
use App\Models\City;
use App\Models\DataPort;
use App\Models\Network;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Region;
use T4\Console\Application;
use T4\Core\Exception;
use T4\Core\IArrayable;
use T4\Core\MultiException;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        /**
         * @var Network $net
         */

//
//        $res = (new Network())->fill(['address' => '192.168.1.0/24'])->save();
//        $res = (new Network())->fill(['address' => '192.168.1.0/26'])->save();
//        $res = (new Network())->fill(['address' => '192.168.1.64/26'])->save();
//        $res = (new Network())->fill(['address' => '192.168.1.0/25'])->save();
//        $res = (new Network())->fill(['address' => '192.168.2.0/25'])->save();
//
//        $res = Network::findByAddress('192.168.1.0/25');
//        $res->address = '192.168.2.0/24';
//        $res->save();
//        $res->delete();

//        $res = (new Network())->fill(['address' => '192.168.1.0/25'])->save();
//        $res = (new Network())->fill(['address' => '192.168.1.0/26'])->save();
//        Network::getDbConnection()->beginTransaction();
//        $res = (new Network())->fill(['address' => '192.168.2.0/24'])->save();
//        Network::getDbConnection()->rollbackTransaction();
//        $res = (new Network())->fill(['address' => '192.168.1.0/25']);
//        $res = Network::findByAddress('192.168.3.0/24');
//        var_dump($res);
//
//        $res->address = '192.168.1.0/26';
//        var_dump($res);
//
//        $res->save();
//        var_dump($res);

        /**
         * @var Network $res
         */
//        $res->save();
//        $res->delete();
//        $res->fill([
//            'address' => '192.168.2.0/26'
//        ])
//            ->save();

//        $query = (new Query())
//            ->select()
//            ->from(Network::getTableName())
//            ->where('__prt ISNULL');

//        $res = Network::findAllRoots();
        var_dump($res);die;
//        $res->deleteFromTree();
//        $res->fill([
//            'address' => '192.168.2.0/26'
//        ])
//            ->update();
//        var_dump($res->getPk());die;
//        (new Network())->fill(['address' => '192.168.1.0/24'])->save();
//        (new Network())->fill(['address' => '192.168.1.32/27'])->save();
//        (new Network())->fill(['address' => '192.168.1.0/26'])->save();
//        (new Network())->fill(['address' => '192.168.1.64/26'])->save();
//        (new Network())->fill(['address' => '192.168.1.0/25'])->save();
//        (new Network())->fill(['address' => '192.168.1.192/26'])->save();

//        $net = Network::findByAddress('192.168.1.0/25');
//        $net->delete();
//        $net->parent = $net->findParentNetwork();
//        $net->save();
//        var_dump($net);
//        var_dump($net);die;
    }

    public function actionRegions($region = null)
    {
        //var_dump($region);
        if (!empty($region)) {
            $this->actionAddRegion($region);
        }
        $this->data->regions = Region::findAll(['order' => 'title']);
        //var_dump($this->data);
    }

    public function actionAddAppliance()
    {
        $this->data->response = 'Hello!';
        $json = '{"management_ip": "10.10.5.192", "chassis": "CISCO3945-CHASSIS", "modules": [{"serial": "FOC16352NNA", "product_number": "C3900-SPE250/K9"}, {"serial": "QCS1619P38Y", "product_number": "PWR-3900-AC"}, {"serial": "QCS1619P3BE", "product_number": "PWR-3900-AC"}, {"serial": "FOC163772DY", "product_number": "EHWIC-1GE-SFP-CU"}, {"serial": "FOC16382JCK", "product_number": "EHWIC-4ESG"}, {"serial": "FOC16382K39", "product_number": "EHWIC-4ESG"}, {"serial": "FOC16270WUG", "product_number": "SM-D-ES3G-48-P"}], "serial": "FCZ163377FU", "lotus_id": "101"}';
        $dec = json_decode($json);
        var_dump($dec);die;
    }

    public function actionAddRegion($region = null)
    {
        try {
            Region::getDbConnection()->beginTransaction();
            if (!empty(trim($region['many']))) {
                $pattern = '~[\n\r]~';
                $regsInString = preg_replace($pattern, '', trim($region['many']));
                $regInArray = explode(',', $regsInString);
                try {
                    foreach ($regInArray as $region) {
                        (new Region())
                            ->fill(['title' => trim($region)])
                            ->save();
                    }
                } catch (MultiException $e) {
                    $e->prepend(new Exception('Ошибка пакетного ввода'));
                    throw $e;
                }
            } else {
                (new Region())
                    ->fill(['title' => $region['one']])
                    ->save();
            }
            Region::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        }
    }

}