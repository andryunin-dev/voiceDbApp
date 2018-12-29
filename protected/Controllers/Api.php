<?php

namespace App\Controllers;

use App\Models\Region;
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
        $filters = json_decode(file_get_contents('php://input'));
    
        $query = (new Query())
            ->select(['region_id', 'region'])
            ->distinct()
            ->from(Geo_View::getTableName())
            ->where('"region" NOTNULL')
            ->order('"region"');
        $res = Geo_View::findAllByQuery($query);
        $output = [];
        /**
         * @var Geo_View $item
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
        $condition = ['"office_id" NOTNULL'];
        if (! empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['office_id', 'office'])
            ->distinct()
            ->from(Geo_View::getTableName())
            ->where(join(' AND ', $condition))
            ->order('"office_id"');
        $res = Geo_View::findAllByQuery($query);
        $output = [];
        /**
         * @var Geo_View $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->office_id, 'label' => $item->office];
        }
        $this->data->rc = $output;
    }
}