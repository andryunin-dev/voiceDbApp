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
        $query = (new Query())
            ->select(['model', 'prefix'])
            ->distinct()
            ->from(DevPhoneInfoGeo::getTableName())
            ->where('prefix NOTNULL')
            ->limit(10);
        var_dump(DevPhoneInfoGeo::findAllByQuery($query));
        die;
    }
}