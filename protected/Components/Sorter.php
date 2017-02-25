<?php
/**
 * Created by PhpStorm.
 * User: rust
 * Date: 17.02.2017
 * Time: 10:41
 */

namespace App\Components;


use App\Models\Office;

class Sorter
{
    public static function ascOffice(Office $office_1, Office $office_2) {
        return strnatcmp($office_1->address->city->region->title, $office_2->address->city->region->title);
    }
}