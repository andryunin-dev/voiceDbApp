<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 14.11.2018
 * Time: 17:38
 */

namespace App\Controllers;


use App\ViewModels\MappedLocations;
use T4\Mvc\Controller;

class MappingTables extends Controller
{
    public function actionLocationMapping()
    {
        $this->data->locations = MappedLocations::findAll();
        
    }
    
}