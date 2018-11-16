<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 14.11.2018
 * Time: 17:38
 */

namespace App\Controllers;


use App\MappingModels\RoutersSwitches;
use App\ViewModels\MappedLocations_View;
use App\ViewModels\MappedLotusLocations_1CLocations_View;
use T4\Mvc\Controller;

class MappingTables extends Controller
{
    public function actionLocationMapping()
    {
        $this->data->locations = MappedLocations_View::findAll();
        
    }
    
    public function actionLotusAnd1CLocations()
    {
        $this->data->locations = MappedLotusLocations_1CLocations_View::findAll();
        
    }
    public function actionRoutersSwitches()
    {
        $this->data->devs = RoutersSwitches::findAll();
    }
    
}