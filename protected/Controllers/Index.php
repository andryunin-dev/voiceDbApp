<?php

namespace App\Controllers;

use App\Components\Publisher;
use App\Models\Address;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\PstnNumber;
use App\Models\Region;
use T4\Mvc\Controller;

class Index
    extends Controller
{

    public function actionDefault()
    {
        $status = new OfficeStatus(['status' => 'test']);
        $status->save();
//        Publisher::publishFrameworks();
//        $this->app->assets->publishJsFile('/Templates/js/script.js');
//        $this->app->assets->publishCssFile('/Templates/css/style.css');
   }

   public function actionOffices()
    {
        Publisher::publishFrameworks();
        $this->app->assets->publishJsFile('/Templates/js/script.js');
        $this->app->assets->publishCssFile('/Templates/css/style.css');
   }

    public function actionTest()
    {
        $office = new Office(['title' => 'test Office']);
        $office->region->fill(['title' => 'Саратовский']);
        $office->city->fill(['title' => 'Саратовский']);

        var_dump($office); die;
    }
}