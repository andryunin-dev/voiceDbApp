<?php

namespace App\Controllers;


use App\Models\City;
use App\Models\Region;
use T4\Mvc\Controller;

class Modal extends Controller
{
    public function actionAddRegion()
    {

    }

    public function actionEditRegion($id)
    {
        if (false !== $region = Region::findByPK($id)) {
            $this->data->region = $region;
        } else {
            header('Location: /admin/regions');
        }
    }

    public function actionAddCity()
    {
        $this->data->regions = Region::findAll();
    }

    public function actionEditCity($id)
    {
        //прикрутить сортировку
        if (false !== $city = City::findByPK($id)) {
            $this->data->city = $city;
            $this->data->regions = Region::findAll();
        } else {
            header('Location: /admin/regions');
        }
    }

}