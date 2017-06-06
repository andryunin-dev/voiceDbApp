<?php

namespace App\Models;


use T4\Core\TSingleton;
use T4\Orm\Model;

class User extends Model
{
    use TSingleton;

    public function __construct()
    {
        parent::__construct();
    }

    public $level = 3;
    public $debugMode = false;
}