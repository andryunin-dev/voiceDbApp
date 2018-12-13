<?php

namespace App\Storage1CModels;

use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class Nomenclature1C
 * @package App\Storage1CModels
 *
 * @property string $title
 * @property NomenclatureType $type
 * @property string $nomenclatureId
 * @property Collection|InventoryItem1C[] $inventoryItems1C
 */
class Nomenclature1C extends Model
{

    protected static $schema = [
        'table' => 'storage_1c.nomenclature1C',
        'columns' => [
            'title' => ['type' => 'text'],
            'nomenclatureId' => ['type' => 'text'],
        ],
        'relations' => [
            'type' => ['type' => self::BELONGS_TO, 'model' => NomenclatureType::class, 'by' => '__type_id'],
            'inventoryItems1C' => ['type' => self::HAS_MANY, 'model' => InventoryItem1C::class, 'by' => '__nomenclature_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        if (!($this->type instanceof NomenclatureType)) {
            throw new Exception('Not a valid Nomenclature1C\'s type value');
        }
    }
}
