<?php

namespace App\Components;

use T4\Core\Std;

/**
 * Class Permissions
 * @package App\Components
 *
 * use for users permissions
 *
 * @property $readOnly - только чтение
 * @property $changeDescription - редактирование дескрипшинове
 * @property $setInUse
 * @property $addEmptyAppliance - добавление устройств
 * @property $changeApplianceType - изменять роль устройства
 * @property $changeManagementIP
 * @property $addAppliance - добавление устройств(полных)
 * @property $changeModuleAndPorts - добавление устройств(полных)

 * @property $delAppliance
 */
class Permissions extends Std
{

}