<?php

namespace App\Controllers;


use App\Models\City;
use App\Models\Module;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Platform;
use App\Models\Region;
use App\Models\Software;
use App\Models\Vendor;
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
        $this->data->regions = Region::findAll(['order' => 'title']);
    }

    public function actionEditCity($id)
    {
        if (true == $city = City::findByPK($id)) {
            $this->data->city = $city;
            $this->data->regions = Region::findAll(['order' => 'title']);
        } else {
            header('Location: /admin/cities');
        }
    }

    public function actionAddStatus()
    {

    }
    public function actionEditStatus($id)
    {
        if (true == $status = OfficeStatus::findByPK($id)) {
            $this->data->status = $status;
        } else {
            header('Location: /admin/cities');
        }
    }

    public function actionAddOffice()
    {
        $asc = function (City $city_1, City $city_2) {
            return strnatcmp($city_1->region->title, $city_2->region->title);
        };

        $this->data->regions = Region::findAll(['order' => 'title']);
        $this->data->cities = City::findAll()->uasort($asc);
        $this->data->statuses = OfficeStatus::findAll(['order' => 'title']);
    }

    public function actionEditOffice($id)
    {
        if (true == $office = Office::findByPK($id)) {
            $this->data->office = $office;

            $asc = function (City $city_1, City $city_2) {
                return strnatcmp($city_1->region->title, $city_2->region->title);
            };

            $this->data->regions = Region::findAll(['order' => 'title']);
            $this->data->cities = City::findAll()->uasort($asc);
            $this->data->statuses = OfficeStatus::findAll(['order' => 'title']);
        } else {
            header('Location: /admin/cities');
        }
    }

    public function actionAddPlatform()
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
    }

    public function actionEditPlatform($id)
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
        $this->data->platform = Platform::findByPK($id);
    }

    public function actionAddModule()
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
    }

    public function actionEditModule($id)
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
        $this->data->module = Module::findByPK($id);
    }

    public function actionAddSoftware()
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
    }

    public function actionEditSoftware($id)
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
        $this->data->software = Software::findByPK($id);
    }

    public function actionAddVendor()
    {

    }

    public function actionEditVendor($id)
    {
        $this->data->vendor = Vendor::findByPK($id);
    }

    public function actionOfficeDetail($id)
    {
        var_dump(Office::findByPK($id));
        $this->data->office = Office::findByPK($id);
    }
}