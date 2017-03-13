<?php

namespace App\Controllers;

use App\Components\Parser;
use App\Models\Address;
use App\Models\OfficeStatus;
use T4\Mvc\Controller;

class Index
    extends Controller
{

    public function actionDefault()
    {
        header('Location: /admin/offices');
    }
}