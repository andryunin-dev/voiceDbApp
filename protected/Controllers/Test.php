<?php

namespace App\Controllers;

use App\Models\Address;
use App\Models\City;
use App\Models\Region;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        $conn = $this->app->db->phpUnitTests;

        $region = (new Region())
            ->fill(['title' => 'region title'])
            ->save();
        $city = (new City())
            ->fill([
                'title' => 'city title',
                'diallingCode' => '8452',
                'region' => $region
            ])
            ->save();
        $address = (new Address())
            ->fill([
                'title' => 'address string'
            ])
            ->save();

        var_dump($address);
        $city->delete();
        $region->delete();

        die;

    }

}