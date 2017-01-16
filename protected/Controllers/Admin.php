<?php

namespace App\Controllers;

use App\Components\Publisher;
use App\Models\Address;
use App\Models\City;
use App\Models\Office;
use App\Models\OfficeStatus;
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
        $this->data->regions = Region::findAll(['order' => 'title']);
    }

    public function actionAddOffice($data)
    {
        $region = Region::findByPK($data->regId);
        $city = City::findByPK($data->cityId);
        $address = (new Address())
            ->fill(['address' => $data->address, 'city' => $city])
            ->save();
//        $status = new OfficeStatus(['status' => 'open']);
        $office = (new Office())
            ->fill(['title' =>$data->title, 'address' => $address, 'status' => $status])
            ->save();
        var_dump($office);die;

        $office->save();
        var_dump($office);die;
    }

    public function actionCities()
    {
        $this->data->regions = Region::findAll(['order' => 'title']);
    }

    public function actionAddCity($city)
    {
        $region = Region::findByPK($city['regId']);

        $newCity = (new City())
            ->fill(['title' => $city['title']]);
        $newCity->region = $region;
        $newCity->save();

        header('Location: /admin/cities');
    }

    public function actionDelCity($id = null)
    {
        if (!empty($id)) {
            City::findByPK($id)
                ->delete();
        }

        header('Location: /admin/cities');
    }

    public function actionRegs()
    {
        $this->data->regions = Region::findAll(['order' => 'title']);
    }

    public function actionAddReg($region = null)
    {
        if (!empty($region)) {
            if (!empty(trim($region['many']))) {
                $pattern = '~[\n\r]~';
                $regsInString = preg_replace($pattern, '', $region['many']);
                $regInArray = explode(',', $regsInString);

                foreach ($regInArray as $region) {
                    (new Region())
                        ->fill(['title' => trim($region)])
                        ->save();
                }

            } elseif (!empty(trim($region['one']))) {
                (new Region())
                    ->fill(['title' => $region['one']])
                    ->save();
            }
        }
        header('Location: /admin/Regs');
    }

    public function actionDelRegion($id)
    {
        Region::findByPK($id)->delete();
        header('Location: /admin/regs');
    }

}