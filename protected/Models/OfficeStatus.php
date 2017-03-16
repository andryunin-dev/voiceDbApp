<?php
/**
 * Created by PhpStorm.
 * User: rust
 * Date: 10.01.2017
 * Time: 16:35
 */

namespace App\Models;


use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class OfficeStatus
 * @package App\Models
 *
 * @property string $title Office status(opened, closed e.g.)
 *
 * @property Collection|Office[] $offices
 */
class OfficeStatus extends Model
{
    protected static $schema = [
        'table' => 'company.officeStatuses',
        'columns' => [
            'title' => ['type' => 'string']
        ],
        'relations' => [
            'offices' => ['type' => self::HAS_MANY, 'model' => Office::class, 'by' => '__office_status_id']
        ]
    ];

    protected function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название статуса');
        }
        return true;
    }

    protected function sanitizeTitle($val)
    {
        return trim($val);
    }

    protected function validate()
    {
        if (false !== OfficeStatus::findByColumn('title', $this->title)) {

            throw new Exception('Такой статус уже существует');
        }
        return true;
    }

}