<?php

namespace App\Models;


use App\Components\Permissions;
use T4\Core\TSingleton;
use T4\Core\TStdGetSet;
use T4\Http\Request;

/**
 * Class User
 * @package App\Models
 *
 * @property Permissions $permissions
 */
class User
{
    use TSingleton;
    use TStdGetSet;

    public static function instance($new = false)
    {
        static $instance = null;
        if (null === $instance || $new) {
            $instance = new static;
        }
        $instance->createUser();
        return $instance;
    }

    /**
     * создаем пользователя, пока без базы
     * уровни:
     * 0 - readOnly - только чтение
     * 1 - changeDescription - редактирование дескрипшинов
     * 2 - setInUse
     * 3 - addEmptyAppliance - добавление устройств
     * 4 - changeApplianceType - изменять роль устройства
     * 5 - changeManagementIP
     * 6 - addAppliance - добавление устройств
     * 7 - changeModuleAndPorts - add del modules and ports
     * 9 -
     *
     *
     * 20 - delAppliance
     */
    protected function createUser()
    {
        $defaultLvl = 10;
        $http = new Request();
        $lvl = (int)$http->get->userLvl ?? $defaultLvl;
        $lvl = ($lvl > 0 && $lvl < 50) ? $lvl : $defaultLvl;
        $this->level = $lvl;

        $this->permissions = new Permissions();
        $this->permissions->readOnly = (0 == $lvl) ? true : false;
        $this->permissions->changeDescription = (1 <= $lvl) ? true : false;
        $this->permissions->setInUse = (2 <= $lvl) ? true : false;
        $this->permissions->addOffice = (3 <= $lvl) ? true : false;
        $this->permissions->addEmptyAppliance = (3 <= $lvl) ? true : false;
        $this->permissions->changeApplianceType = (4 <= $lvl) ? true : false;
        $this->permissions->changeManagementIP = (5 <= $lvl) ? true : false;
        $this->permissions->addAppliance = (6 <= $lvl) ? true : false;
        $this->permissions->changeModuleAndPorts = (7 <= $lvl) ? true : false;
        $this->permissions->delAppliance = (15 <= $lvl) ? true : false;
        $this->permissions->delOffice = (15 <= $lvl) ? true : false;
    }

    public $level;
    public $debugMode = false;
}