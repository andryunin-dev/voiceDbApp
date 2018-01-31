<?php

namespace App\Controllers;

use App\ViewModels\DevPhoneInfoGeo;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Index
    extends Controller
{

    public function actionDefault()
    {
       header('location: /locations');
    }
}