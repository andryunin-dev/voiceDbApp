<?php

namespace App\Storage1CModels;

use App\Models\Office;
use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class Rooms1C
 * @package App\Storage1CModels
 *
 * @property string $roomsCode
 * @property string $address
 * @property Office $voiceOffice
 * @property Collection|InventoryItem1C[] $inventoryItems1C
 * @property Collection|Mol[] $mols
 */
class Rooms1C extends Model
{
    private const EMPTY = '';

    protected static $schema = [
        'table' => 'storage_1c.rooms1C',
        'columns' => [
            'roomsCode' => ['type' => 'string'],
            'address' => ['type' => 'text'],
        ],
        'relations' => [
            'voiceOffice' => ['type' => self::BELONGS_TO, 'model' => Office::class, 'by' => '__voice_office_id'],
            'inventoryItems1C' => ['type' => self::HAS_MANY, 'model' => InventoryItem1C::class, 'by' => '__rooms_1c_id'],
            'mols' => ['type' => self::MANY_TO_MANY, 'model' => Mol::class, 'pivot' => 'storage_1c.mol_rooms1C', 'this' => '__rooms_1c_id', 'that' => '__mol_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        if (!is_null($this->voiceOffice) && !($this->voiceOffice instanceof Office)) {
            throw new Exception('Not a valid Rooms1C\'s voiceOffice type');
        }

        $dyplicateByRoomsCode = self::findByColumn('roomsCode', $this->roomsCode);

        if (true === $this->isNew() && false !== $dyplicateByRoomsCode) {
            throw new Exception('A Rooms1C with this roomsCode exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByRoomsCode && $dyplicateByRoomsCode->getPk() != $this->getPk()) {
            throw new Exception('A Rooms1C with this roomsCode exists');
        }
        return true;
    }

    /**
     * @param string $roomsCode
     * @return bool
     * @throws Exception
     */
    protected function validateRoomsCode(string $roomsCode): bool
    {
        if (empty(trim($roomsCode)) && self::EMPTY != $roomsCode) {
            throw new Exception('Not a valid Rooms1C\'s roomsCode value');
        }
        return true;
    }

    /**
     * @param string $roomsCode
     * @return string
     */
    protected function sanitizeRoomsCode(string $roomsCode): string
    {
        return trim($roomsCode);
    }

    /**
     * @param string $address
     * @return bool
     * @throws Exception
     */
    protected function validateAddress(string $address): bool
    {
        if (empty(trim($address)) && self::EMPTY != $address) {
            throw new Exception('Not a valid Rooms1C\'s $address value');
        }
        return true;
    }

    /**
     * @param string $address
     * @return string
     */
    protected function sanitizeAddress(string $address): string
    {
        return trim($address);
    }

    /**
     * @return Rooms1C
     */
    public static function getEmptyInstance(): self
    {
        $rooms1C = self::findByColumn('roomsCode', self::EMPTY);
        if (false === $rooms1C) {
            $rooms1C = new self();
            $rooms1C->roomsCode = self::EMPTY;
            $rooms1C->address = self::EMPTY;
            $rooms1C->save();
        }
        return $rooms1C;
    }
}
