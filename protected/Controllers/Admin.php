<?php

namespace App\Controllers;

use App\Components\Publisher;
use T4\Mvc\Controller;

class Admin extends Controller
{
    public function actionDefault()
    {
        Publisher::publishFrameworks();
        $this->app->assets->publishJsFile('/Templates/js/script.js');
        $this->app->assets->publishCssFile('/Templates/css/style.css');
    }

}