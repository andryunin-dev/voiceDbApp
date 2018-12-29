<?php

namespace App\Controllers;

use App\Models\Region;
use App\ViewModels\ApiView_Geo;
use App\ViewModels\Geo_View;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Api extends Controller
{
    public function actionGetRegCenters() {
        // respond to preflights
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = json_decode(file_get_contents('php://input'));
    
        $query = (new Query())
            ->select('regCenter')
            ->distinct()
            ->from(Geo_View::getTableName())
            ->where('"regCenter" NOTNULL')
            ->order('"regCenter"');
        $res = Geo_View::findAllByQuery($query);
        $output = [];
        /**
         * @var Geo_View $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->regCenter, 'label' => $item->regCenter];
        }
        $this->data->rc = $output;
    }
    public function actionGetRegions() {
        // respond to preflights
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['region_id NOTNULL'];
        if (! empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['region_id', 'region'])
            ->from(ApiView_Geo::getTableName())
            ->where(join(' AND ', $condition))
            ->group('region_id, region')
            ->order('region');
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->region_id, 'label' => $item->region];
        }
        $this->data->rc = $output;
    }
    public function actionGetOffices() {
        // respond to preflights
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['location_id NOTNULL'];
        if (! empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['location_id', 'office'])
            ->from(ApiView_Geo::getTableName())
            ->where(join(' AND ', $condition))
            ->group('location_id, office')
            ->order('"office"');
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->location_id, 'label' => $item->office];
        }
        $this->data->rc = $output;
    }
}