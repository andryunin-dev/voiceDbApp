<?php

namespace App\Controllers;

use App\Components\Publisher;
use App\Models\Address;
use App\Models\City;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Region;
use phpDocumentor\Reflection\Types\This;
use T4\Mvc\Controller;

class Admin extends Controller
{
    public function actionDefault()
    {
//        Publisher::publishFrameworks();
//        $this->app->assets->publishJsFile('/Templates/js/script.js');
//        $this->app->assets->publishCssFile('/Templates/css/style.css');
    }

    public function actionOfficeStatuses()
    {
        $this->data->statuses = OfficeStatus::findAll();
    }

    public function actionAddStatus($status = null)
    {
        if (!empty($status)) {
            if (!empty(trim($status['many']))) {
                $pattern = '~[\n\r]~';
                $statsInString = preg_replace($pattern, '', $status['many']);
                $statsInArray = explode(',', $statsInString);

                foreach ($statsInArray as $status) {
                    (new OfficeStatus())
                        ->fill(['title' => trim($status)])
                        ->save();
                }
            } elseif (!empty(trim($status['one']))) {
                (new OfficeStatus())
                    ->fill(['title' => trim($status['one'])])
                    ->save();
            }
        }
        header('Location: /admin/OfficeStatuses');
    }

    public function actionDelStatus($id = null)
    {
        OfficeStatus::findByPK($id)->delete();
        header('Location: /admin/officeStatuses');
    }

    /**
     * @var Region[] $regions
     */
    public function actionOffices()
    {
        $regions = Region::findAll();
        foreach ($regions as $region) {
            foreach ($region->cities as $city) {
                foreach ($city->addresses as $address) {
                        var_dump($address->office);
                }
            }
        }
        var_dump($regions);
        die;
        $this->data->statuses = OfficeStatus::findAll(['order' => 'title']);
    }

    public function actionAddOffice($data)
    {
        $region = Region::findByPK($data->regId);
        $city = City::findByPK($data->cityId);
        $status = OfficeStatus::findByPK($data->statId);
        $address = (new Address())
            ->fill(['address' => $data->address, 'city' => $city])
            ->save();
        $office = (new Office())
            ->fill(['title' =>$data->title, 'address' => $address, 'status' => $status])
            ->save();
        $office->save();

        header('Location: /admin/offices');
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

    public function actionRegions()
    {
        $this->data->regions = Region::findAll(['order' => 'title']);
    }

    public function actionAddRegion($region = null)
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
        header('Location: /admin/Regions');
    }

    public function actionDelRegion($id)
    {
        Region::findByPK($id)->delete();
        header('Location: /admin/regions');
    }

}