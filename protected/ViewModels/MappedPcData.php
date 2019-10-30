<?php

namespace App\ViewModels;

use T4\Core\Collection;
use T4\Orm\Model;

/**
 * Class MappedPcData
 * @package App\ViewModels
 *
 * @property string $pc_mac
 * @property string $pc_os_name
 * @property string $pc_os_edition
 * @property string $pc_os_version
 * @property string $pc_os_sp
 * @property string $pc_os_bits
 * @property string $pc_kernel
 * @property string $pc_mstsc
 * @property string $pc_name
 * @property string $pc_drive_serial
 * @property int $pc_drive_size
 * @property string $pc_cpu
 * @property string $pc_memory
 * @property datetime $pc_last_update
 * @property string $pc_inv_number
 * @property datetime $pc_inv_update
 * @property string $pc_rn
 * @property string $pc_ip
 * @property string $pc_ses_login
 * @property string $pc_ses_domain
 * @property string $sw_hostname
 * @property string $sw_ip
 * @property string $sw_interface
 * @property int $client_mac_amount
 * @property datetime $dhcp
 * @property int $vpn
 * @property string $employee
 * @property string $position
 * @property string $division
 * @property string $sw_model
 * @property string $sw_serialNumber
 * @property string $sw_inventoryNumber
 * @property string $sw_office
 * @property string $sw_reg_center
 * @property string $sw_city
 * @property string $sw_address
 * @property string $phone_model
 * @property string $phone_dn
 */
class MappedPcData extends Model
{
    protected static $schema = [
        'table' => 'view.mappedPcData',
        'columns' => [
            'pc_mac' => ['type' => 'string'],
            'pc_os_name' => ['type' => 'string'],
            'pc_os_edition' => ['type' => 'string'],
            'pc_os_version' => ['type' => 'string'],
            'pc_os_sp' => ['type' => 'string'],
            'pc_os_bits' => ['type' => 'string'],
            'pc_kernel' => ['type' => 'string'],
            'pc_mstsc' => ['type' => 'string'],
            'pc_name' => ['type' => 'string'],
            'pc_drive_serial' => ['type' => 'string'],
            'pc_drive_size' => ['type' => 'int'],
            'pc_cpu' => ['type' => 'string'],
            'pc_memory' => ['type' => 'string'],
            'pc_last_update' => ['type' => 'datetime'],
            'pc_inv_number' => ['type' => 'string'],
            'pc_inv_update' => ['type' => 'datetime'],
            'pc_rn' => ['type' => 'string'],
            'pc_ip' => ['type' => 'string'],
            'pc_ses_login' => ['type' => 'string'],
            'pc_ses_domain' => ['type' => 'string'],
            'sw_hostname' => ['type' => 'string'],
            'sw_ip' => ['type' => 'string'],
            'sw_interface' => ['type' => 'string'],
            'client_mac_amount' => ['type' => 'int'],
            'dhcp' => ['type' => 'datetime'],
            'vpn' => ['type' => 'int'],
            'employee' => ['type' => 'string'],
            'position' => ['type' => 'string'],
            'division' => ['type' => 'string'],
            'sw_model' => ['type' => 'string'],
            'sw_serialNumber' => ['type' => 'string'],
            'sw_inventoryNumber' => ['type' => 'string'],
            'sw_office' => ['type' => 'string'],
            'sw_reg_center' => ['type' => 'string'],
            'sw_city' => ['type' => 'string'],
            'sw_address' => ['type' => 'string'],
            'phone_model' => ['type' => 'string'],
            'phone_dn' => ['type' => 'string'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }

    public static function findAllWithMappedDivision()
    {
        $pc = new Collection();
        foreach (self::findAll() as $item) {
            $divisionParts = [
                0 => '',
                1 => '',
                2 => '',
                3 => '',
                4 => '',
                5 => '',
                6 => '',
                7 => '',
                8 => '',
                9 => '',
            ];
            foreach (explode('\\', $item->division) as $key => $value) {
                $divisionParts[$key] = $value;
            }
            $item->divisionParts = $divisionParts;
            $pc->add($item);
        }
        return $pc;
    }

}
