<?php
namespace App\ViewModels;

use T4\Orm\Model;

/**
 * Class PhoneRecorder_View
 * @package App\ViewModels
 *
 * @property string $city
 * @property string $office
 * @property string $phoneName
 * @property string $phoneModel
 * @property string $recorder
 * @property string $phoneDN
 * @property string $displayedDN
 * @property string $ipAddress
 * @property \DateTime $lastUpdate
 * @property int $appAge
 */
class PhoneRecorder_View extends Model
{
    protected static $schema = [
        'table' => 'view.phone_recorder',
        'columns' => [
            'city' => ['type' => 'text'],
            'office' => ['type' => 'text'],
            'phoneName' => ['type' => 'text'],
            'phoneModel' => ['type' => 'text'],
            'recorder' => ['type' => 'text'],
            'phoneDN' => ['type' => 'text'],
            'displayedDN' => ['type' => 'text'],
            'ipAddress' => ['type' => 'string'],
            'lastUpdate' => ['type' => 'datetime'],
            'appAge' => ['type' => 'int'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }
}
