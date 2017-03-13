<?php

namespace App\Controllers;


use App\Components\Sorter;
use App\Models\Address;
use App\Models\City;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Region;
use T4\Core\IArrayable;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        var_dump(get_current_user());die;
    }

    /**
     * action вывода всех офисов
     */
    public function actionOffices()
    {
        $asc = function (Office $office_1, Office $office_2) {
            return strnatcmp($office_1->address->city->region->title, $office_2->address->city->region->title);
        };

        $this->data->offices = Office::findAll()->uasort($asc);
        $this->data->regions = Region::findAll();
        $this->data->statuses = OfficeStatus::findAll();
    }

}