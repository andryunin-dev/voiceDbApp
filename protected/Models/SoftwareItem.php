<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class SoftwareItem
 * @package App\Models
 *
 * @property string $version
 * @property string $details
 * @property string $comment
 *
 * @property Software $software
 * @property Collection|Appliance[] $appliances
 */
class SoftwareItem extends Model
{
    protected static $schema = [
        'table' => 'equipment.softwareItems',
        'columns' => [
            'version' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'software' => ['type' => self::BELONGS_TO, 'model' => Software::class],
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class, 'by' => '__software_item_id']
        ]
    ];

    public function validateVersion($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое значение ver. ПО');
        }
        return true;

    }

    public function validate()
    {
        if (false === $this->software) {
            return false;
        }
        return true;
    }

    public static function findBySoftwareVersion(Software $software, string $version)
    {
        $query = (new Query())
            ->select()
            ->from(self::getTableName())
            ->where('version = :version AND "__software_id" = :__software_id')
            ->params([':version' => $version, ':__software_id' => $software->getPk()]);

        return self::findByQuery($query);
    }
}