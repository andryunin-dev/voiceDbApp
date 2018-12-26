<?php

namespace App\Controllers;

use App\Models\Region;
use App\ViewModels\Geo_View;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Api extends Controller
{
    public function actionGetRegCenters() {
        $query = (new Query())
            ->select('regCenter')
            ->distinct()
            ->from(Geo_View::getTableName())
            ->order('"regCenter"');
        $res = Geo_View::findAllByQuery($query);
        $output = [];
        /**
         * @var Geo_View $item
         */
        foreach ($res as $item) {
            $output[] = ['volume' => $item->regCenter, 'label' => $item->regCenter];
        }
        $this->data->data = $output;
    }
}