<?php

namespace App\Controllers;


use App\Components\Sorter;
use App\Models\Address;
use App\Models\ApplianceType;
use App\Models\City;
use App\Models\Office;
use App\Models\OfficeStatus;
use App\Models\Region;
use T4\Core\Exception;
use T4\Core\IArrayable;
use T4\Core\MultiException;
use T4\Http\Request;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault($ip)
    {
        var_dump($ip);
        $res = str_replace('\\', '/', $ip);
        $res = explode('/',$res);
        var_dump($res);die;
        var_dump(preg_replace('/\\\/', '/', $ip));die;
        var_dump(filter_var($ip, FILTER_VALIDATE_IP));die;
    }

    public function actionRegions($region = null)
    {
        //var_dump($region);
        if (!empty($region)) {
            $this->actionAddRegion($region);
        }
        $this->data->regions = Region::findAll(['order' => 'title']);
        //var_dump($this->data);
    }

    public function actionAddRegion($region = null)
    {
        try {
            Region::getDbConnection()->beginTransaction();
            if (!empty(trim($region['many']))) {
                $pattern = '~[\n\r]~';
                $regsInString = preg_replace($pattern, '', trim($region['many']));
                $regInArray = explode(',', $regsInString);
                try {
                    foreach ($regInArray as $region) {
                        (new Region())
                            ->fill(['title' => trim($region)])
                            ->save();
                    }
                } catch (MultiException $e) {
                    $e->prepend(new Exception('Ошибка пакетного ввода'));
                    throw $e;
                }
            } else {
                (new Region())
                    ->fill(['title' => $region['one']])
                    ->save();
            }
            Region::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Region::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
            //var_dump($this->data);die;
        }
    }

}