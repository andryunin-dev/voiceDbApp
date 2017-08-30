<?php
/**
 * Created by PhpStorm.
 * User: karasev-dl
 * Date: 13.07.2017
 * Time: 13:43
 */

namespace App\Controllers;


use App\Components\Timer;
use App\Components\UrlExt;
use App\ViewModels\DevModulePortGeo;
use T4\Core\Std;
use T4\Dbal\QueryBuilder;
use T4\Http\Request;
use T4\Mvc\Controller;

class Voice extends Controller
{
    public function actionCucms() {
        $timer = Timer::instance();
        $timer->fix('start action');

        $getParams = [
            'reg' => ['clause' => 'region_id = :region_id', 'param' => ':region_id'],
            'city' => ['clause' => 'city_id = :city_id', 'param' => ':city_id'],
            'loc' => ['clause' => 'location_id = :location_id', 'param' => ':location_id'],
            'cl' => ['clause' => 'cluster_id = :cluster_id', 'param' => ':cluster_id'],
            'type' => ['clause' => '"appType_id" = :appType_id', 'param' => ':appType_id'],
            'pl' => ['clause' => '"platform_id" = :platform_id', 'param' => ':platform_id'],
            'soft' => ['clause' => '"software_id" = :software_id', 'param' => ':software_id'],
            'softVer' => ['clause' => '"softwareVersion" = :softwareVersion', 'param' => ':softwareVersion'],
            'activeAge' => ['clause' => '"appAge" < :appAge', 'param' => ':appAge'],
            'noActiveAge' => ['clause' => '("appAge" >= :appAge OR "appAge" ISNULL)', 'param' => ':appAge'],
            'inUse' => ['clause' => '"appInUse" = :appInUse', 'param' => ':appInUse'],
            'ven' => ['clause' => '"platformVendor_id" = :platformVendor_id', 'param' => ':platformVendor_id']

        ];

        $http = new Request;
        $this->data->url = new UrlExt($http->url->toArrayRecursive());
        $where = [];
        $params = [];
        $order = DevModulePortGeo::sortOrder();
        $maxAge = 73;
        $networkDevFilter = implode(',', DevModulePortGeo::cucmDevTypes_id());
        $where[] = '"appType_id" IN (' . $networkDevFilter . ')';

        if (0 == $http->get->count()) {
            $order = DevModulePortGeo::sortOrder();
        } else {
            $getParams = new Std($getParams);
            if (isset($http->get->maxAge)) {
                $maxAge = $http->get->maxAge;
            }
//            var_dump($getParams);die;
            foreach ($http->get as $key => $val) {
                if (! isset($getParams->$key)) {
                    continue;
                }
                if ('order' == $key) {
                    $order = DevModulePortGeo::sortOrder($val);
                    continue;
                }
                $where[] = $getParams->$key->clause;
                $params[$getParams->$key->param] = $val;
            }
        }
        $where = implode(' AND ', $where);
        $query = (new QueryBuilder())
            ->select()
            ->from(DevModulePortGeo::getTableName())
            ->where($where)
            ->params($params)
            ->order($order);
//        var_dump($query);
        $this->data->geoDevs = DevModulePortGeo::findAllByQuery($query);
        $this->data->navbar->count = $this->data->geoDevs->count();
        $this->data->exportUrl = '/export/hardInvExcel';
        $this->data->maxAge = $maxAge;
        $this->data->activeLink->devices = true;
        $timer->fix('end action');
    }
}