<?php

namespace App\ViewModels;

use App\Components\IpTools;
use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class ModuleItem_View
 * @package App\ViewModels
 *
 * @property int $appliance_id
 * @property int $moduleItem_id
 * @property string $serialNumber
 * @property string $inventoryNumber
 * @property string $details
 * @property string $comment
 * @property bool $inUse
 * @property bool $notFound
 * @property string $lastUpdate
 * @property int $module_id
 * @property string $title
 * @property string $description
 */
class ModuleItem_View extends Model
{
    protected static $schema = [
        'table' => 'view.geo_dev_module_port',
        'columns' => [
            'appliance_id' => ['type' => 'int', 'length' => 'big'],
            'moduleItem_id' => ['type' => 'int', 'length' => 'big'],
            'serialNumber' => ['type' => 'string'],
            'inventoryNumber' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string'],
            'inUse' => ['type' => 'boolean'],
            'notFound' => ['type' => 'boolean'],
            'lastUpdate' => ['type' => 'datetime'],
            'moduleItemAge' => ['type' => 'int'],
            'module_id' => ['type' => 'int', 'length' => 'big'],
            'title' => ['type' => 'string'],
            'description' => ['type' => 'string']
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }

    public function lastUpdateDate()
    {
        return $this->lastUpdate ? (new \DateTime($this->lastUpdate))->format('Y-m-d') : null;
    }

    public function lastUpdateDateTime()
    {
        return $this->lastUpdate ? ('last update: ' . ((new \DateTime($this->lastUpdate))->setTimezone(new \DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i \M\S\K(P)')) : null;
    }
}