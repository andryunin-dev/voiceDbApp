<?php

namespace App\Components;

use T4\Core\Std;

/**
 * Class Permissions
 * @package App\Components
 *
 * use for users permissions
 *
 * @property bool $readOnly - только чтение
 * @property bool $changeDescription - редактирование дескрипшинове
 * @property bool $setInUse
 * @property bool $addOffice
 * @property bool $editOffice
 * @property bool $delOffice
 * @property bool $addEmptyAppliance - добавление устройств
 * @property bool $addAppliance - добавление устройств(полных)
 * @property bool $editAppliance - редактирование устройств
 * @property bool $changeApplianceType - изменять роль устройства
 * @property bool $changeManagementIP
 * @property bool $changeModuleAndPorts - добавление/редактирование устройств(полных)

 * @property bool $delAppliance
 */
class Permissions extends Std
{

}