<?php

namespace App\Controllers;

use App\Models\Address;
use App\Models\Office;
use App\Models\Region;
use T4\Mvc\Controller;

class Index
    extends Controller
{

    public function actionDefault()
    {

        $office = new Office(['title' => 'test Office']);
        $office->region->fill(['title' => 'Саратовский']);
        $office->city->fill(['title' => 'Саратовский']);

        var_dump($office); die;

    }

}