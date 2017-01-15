<?php

namespace App\Controllers;

use App\Components\Publisher;
use App\Models\City;
use App\Models\Region;
use T4\Mvc\Controller;

class Admin extends Controller
{
    public function actionDefault()
    {
//        Publisher::publishFrameworks();
//        $this->app->assets->publishJsFile('/Templates/js/script.js');
//        $this->app->assets->publishCssFile('/Templates/css/style.css');
    }
    public function actionOffices()
    {

    }
    public function actionAllCities()
    {
        $this->data->regions = Region::findAll(['order' => 'title']);

    }

    public function actionNewRegion($region = null)
    {
        if (!empty($region)) {
            (new Region())
                ->fill(['title' => $region])
                ->save();
        }
        header('Location: /admin/allCities');
    }

}