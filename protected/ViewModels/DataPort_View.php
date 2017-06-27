<?php

namespace App\ViewModels;

use App\Components\IpTools;
use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class DataPort_View
 * @package App\ViewModels
 *
 * @property int $appliance_id
 * @property int $network_id
 * @property string $ipAddress
 * @property int $masklen
 * @property string $macAddress
 * @property string $details
 * @property string $comment
 * @property bool $isManagement
 * @property int $portType_id
 * @property string $portType
 */
class DataPort_View extends Model
{
    protected static $schema = [
        'table' => 'view.geo_dev_module_port',
        'columns' => [
            'appliance_id' => ['type' => 'int', 'length' => 'big'],
            'network_id' => ['type' => 'int', 'length' => 'big'],
            'ipAddress' => ['type' => 'string'],
            'masklen' => ['type' => 'int'],
            'macAddress' => ['type' => 'string'],
            'details' => ['type' => 'jsonb'],
            'comment' => ['type' => 'string'],
            'isManagement' => ['type' => 'boolean'],
            'portType_id' => ['type' => 'int', 'length' => 'big'],
            'portType' => ['type' => 'string']
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }

    protected function getCidrIpAddress()
    {
        $ip = new IpTools($this->ipAddress, $this->masklen);
        if ($ip->is_valid) {
            return $ip->cidrAddress;
        } else {
            return null;
        }
    }
}