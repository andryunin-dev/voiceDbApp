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
 * @property Collection|InventoryItem1C[] $inventoryItems1C
 */
class Nomenclature1C extends Model
{
    private const EMPTY = '';

    protected static $schema = [
        'table' => 'storage_1c.nomenclature1C',
        'columns' => [
            'title' => ['type' => 'text'],
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

        $dyplicateByTitle = self::findByColumn('title', $this->title);

        if (true === $this->isNew() && false !== $dyplicateByTitle) {
            throw new Exception('A Nomenclature1C with this title exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByTitle && $dyplicateByTitle->getPk() != $this->getPk()) {
            throw new Exception('A Nomenclature1C with this title exists');
        }
        return true;
    }

    /**
     * @param string $title
     * @return bool
     * @throws Exception
     */
    protected function validateTitle(string $title): bool
    {
        if (empty(trim($title)) && self::EMPTY != $title) {
            throw new Exception('Not a valid Nomenclature1C\'s title value');
        }
        return true;
    }

    /**
     * @param string $title
     * @return string
     */
    protected function sanitizeTitle(string $title): string
    {
        return trim($title);
    }

    /**
     * @return Nomenclature1C
     */
    public static function getEmptyInstance(): self
    {
        $nomenclature1C = self::findByColumn('title', self::EMPTY);
        if (false === $nomenclature1C) {
            $nomenclature1C = new self();
            $nomenclature1C->title = self::EMPTY;
            $nomenclature1C->type = NomenclatureType::getEmptyType();
            $nomenclature1C->save();
        }
        return $nomenclature1C;
    }
}
