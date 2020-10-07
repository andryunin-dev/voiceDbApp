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
    private const OPEN = 'открыт';

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
        $officeStatusFromDb = OfficeStatus::findByColumn('title', $this->title);
        if (false !== $officeStatusFromDb && ($this->isNew() || $this->getPk() != $officeStatusFromDb->getPk())) {
            throw new Exception('Такой статус офиса уже существует');
        }
        return true;
    }

    /**
     * @return OfficeStatus
     * @throws \T4\Core\MultiException
     */
    public static function openInstance(): OfficeStatus
    {
        $officeStatus = OfficeStatus::findByColumn('title', self::OPEN);
        if (false === $officeStatus) {
            $officeStatus = (new OfficeStatus())->fill(['title' => self::OPEN])->save();
        }
        return $officeStatus;
    }
}
