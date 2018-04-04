<?php

namespace App\Storage1CModels;

use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class NomenclatureType
 * @package App\Storage1CModels
 *
 * @property string $type
 * @property Collection|Nomenclature1C[] $nomenclature
 */
class NomenclatureType extends Model
{
    private const EMPTY = '';

    protected static $schema = [
        'table' => 'storage_1c.nomenclatureTypes',
        'columns' => [
            'type' => ['type' => 'string'],
        ],
        'relations' => [
            'nomenclature' => ['type' => self::HAS_MANY, 'model' => Nomenclature1C::class, 'by' => '__type_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        $dyplicateByType = self::findByColumn('type', $this->type);

        if (true === $this->isNew() && false !== $dyplicateByType) {
            throw new Exception('A NomenclatureType  with this type exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByType && $dyplicateByType->getPk() != $this->getPk()) {
            throw new Exception('A NomenclatureType  with this type exists');
        }
        return true;
    }

    /**
     * @param string $type
     * @return bool
     * @throws Exception
     */
    protected function validateType(string $type): bool
    {
        if (empty(trim($type)) && self::EMPTY != $type) {
            throw new Exception('Not a valid Nomenclature\'s type value');
        }
        return true;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function sanitizeType(string $type): string
    {
        return trim($type);
    }

    /**
     * @return NomenclatureType
     */
    public static function getEmptyType(): self
    {
        $nomenclatureType = self::findByColumn('type', self::EMPTY);
        if (false === $nomenclatureType) {
            $nomenclatureType = new self(['type' => self::EMPTY]);
            $nomenclatureType->save();
        }
        return $nomenclatureType;
    }
}
