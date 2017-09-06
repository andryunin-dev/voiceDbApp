<?php

namespace App\Controllers;

use App\Components\Ip;
use App\Components\Reports\SoftReport;
use App\Components\Reports\VendorReport;
use App\Components\Timer;
use App\Models\Appliance;
use App\Models\DataPort;
use App\Models\DPortType;
use App\Models\Network;
use App\Models\Office;
use App\Models\Region;
use App\Models\Vrf;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Core\Url;
use T4\Dbal\Connection;
use T4\Dbal\Query;
use T4\Http\Helpers;
use T4\Mvc\Controller;

class Test extends Controller
{
    public function actionDefault()
    {

    }
}