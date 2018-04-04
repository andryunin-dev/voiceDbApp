<?php

namespace App\Storage1CModels;

use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class RoomsType
 * @package App\Storage1CModels
 *
 * @property string $type
 * @property Collection|Rooms1C[] $rooms
 */
class RoomsType extends Model
{
    private const EMPTY = '';
    private const OFFICE = 'office';
    private const STOREHOUSE = 'storehouse';

    protected static $schema = [
        'table' => 'storage_1c.roomsTypes',
        'columns' => [
            'type' => ['type' => 'string'],
        ],
        'relations' => [
            'rooms' => ['type' => self::HAS_MANY, 'model' => Rooms1C::class, 'by' => '__type_id'],
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
            throw new Exception('A RoomsType  with this type exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByType && $dyplicateByType->getPk() != $this->getPk()) {
            throw new Exception('A RoomsType  with this type exists');
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
            throw new Exception('Not a valid Room\'s type value');
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
     * @return RoomsType
     */
    public static function getEmptyInstance(): self
    {
        $roomsType = self::findByColumn('type', self::EMPTY);
        if (false === $roomsType) {
            $roomsType = new self(['type' => self::EMPTY]);
            $roomsType->save();
        }
        return $roomsType;
    }

    /**
     * @return RoomsType
     */
    public static function getOfficeType(): self
    {
        $roomsType = self::findByColumn('type', self::OFFICE);
        if (false === $roomsType) {
            $roomsType = new self(['type' => self::OFFICE]);
            $roomsType->save();
        }
        return $roomsType;
    }

    /**
     * @return RoomsType
     */
    public static function getStorehouseType(): self
    {
        $roomsType = self::findByColumn('type', self::STOREHOUSE);
        if (false === $roomsType) {
            $roomsType = new self(['type' => self::STOREHOUSE]);
            $roomsType->save();
        }
        return $roomsType;
    }
}
