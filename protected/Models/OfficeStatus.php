<?php
/**
 * Created by PhpStorm.
 * User: rust
 * Date: 10.01.2017
 * Time: 16:35
 */

namespace App\Models;


use T4\Orm\Model;

/**
 * Class OfficeStatus
 * @package App\Models
 *
 * @property string $status Office status(opened, closed e.g.)
 */
class OfficeStatus extends Model
{
    protected static $schema = [
        'table' => 'company.officeStatuses',
        'columns' => [
            'status' => ['type' => 'string']
        ]
    ];
}