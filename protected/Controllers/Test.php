<?php

namespace App\Controllers;

use App\Models\Appliance;
use App\ViewModels\DevGeo_View;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Test extends Controller
{
    protected $conditionalFormats = [
        'beginWith' => '%s%%'
    ];
    
    public function actionFunctions()
    {
        $array = ['test', 't2'];
        var_dump(implode(' OR ', $array));
        $string = sprintf($this->conditionalFormats['beginWith'], 'test');
        $obj = new Std();
        $obj->value = 'test';
        
        var_dump($string);
        if ($obj->test) {
            echo 'value exists';
        } else {
            echo 'value isn\'t exists';
        }
        die;
    }
    public function actionJson()
    {

        $jstring = '
        {  
            "Person":[  
               "name",
               "age"
            ],
            "Device":{  
               "Platform":[  
                  "sn",
                  "inv"
               ],
               "Software":[  
                  "ver"
               ]
        }
}
        ';
        echo $jstring;
        var_dump(json_decode($jstring));die;
    }
    public function actionGetPhone()
    {

        $name = '';
        $cmd = 'php '.ROOT_PATH.DS.'protected'.DS.'t4.php cucmsPhones'.DS.'getPhoneByName --name='. $name;
        exec($cmd, $result);

        var_dump($result);

        die;
    }


    public function actionDeleteVeryOldAnalogPhones()
    {
        $query = (new Query())
            ->select(['appliance_id', 'appAge', 'platformSerial'])
            ->from(DevGeo_View::getTableName())
            ->where('"appType" = :appType AND ("platformTitle" = :platform_title_1 OR "platformTitle" = :platform_title_2) AND "appAge" > 300')
            ->params([
                ':appType' => 'phone',
                ':platform_title_1' => 'Analog Phone',
                ':platform_title_2' => 'VGC Phone',
            ]);

        $res = DevGeo_View::findAllByQuery($query);
        $counter = 0;
        foreach ($res as $dev) {
            $item = Appliance::findByPK($dev->appliance_id);
            if ($item instanceof Appliance) {
                $item->delete();

                echo ++$counter . ' - ' . $item->platform->platform->title . ' - has been deleted' . "\n";
            }
        }
    }
}