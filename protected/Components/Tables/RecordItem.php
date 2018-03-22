<?php

namespace App\Components\Tables;

use T4\Core\Std;

class RecordItem extends Std
{
    public function getValue($key)
    {
        $res = $this->$key;
        return $this->$key;
    }
}