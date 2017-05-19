<?php

namespace App\Models;

use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Cluster
 * @package App\Models
 *
 * @property string $title
 * @property string $details
 * @property string $comment
 *
 * @property Collection|Appliance[] $appliances
 */
class Cluster extends Model
{
    protected static $schema = [
        'table' => 'equipment.clusters',
        'columns' => [
            'title' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'appliances' => ['type' => self::HAS_MANY, 'model' => Appliance::class]
        ]
    ];

    public function validateTitle($val)
    {
        if (empty(trim($val))) {
            throw new Exception('Пустое название кластера');
        }

        return true;
    }

    public function validate()
    {
        $cluster = Cluster::findByColumn('title', $this->title);

        if (true === $this->isNew && ($cluster instanceof Cluster)) {
            throw new Exception('Такой кластер уже существует');
        }

        if (true === $this->isUpdated && ($cluster instanceof Cluster) && ($cluster->getPk() != $this->getPk())) {
            throw new Exception('Такой кластер уже существует');
        }

        return true;
    }
}
