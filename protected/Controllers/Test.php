<?php

namespace App\Controllers;


use App\Models\Address;
use App\Models\City;
use App\Models\Region;
use T4\Core\IArrayable;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {
        $vendor = (new \App\Models\Vendor())
            ->fill([
                'title' => 'vendor name'
            ])
            ->save();

        $software = (new \App\Models\Software())
            ->fill([
                'title' => 'soft title',
                'vendor' => $vendor
            ])
            ->save();

        $softwareItem = (new \App\Models\SoftwareItem())
            ->fill([
                'version' => 'soft ver',
                'detail' => ['propName' => 'propValue'],
                'comment' => 'soft comment',
                'software' => $software
            ])
            ->save();

        die;
    }
}