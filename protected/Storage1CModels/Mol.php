<?php

namespace App\Storage1CModels;

use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class Mol
 * @package App\Storage1CModels
 *
 * @property string $fio
 * @property int $molTabNumber
 * @property Collection|InventoryItem1C[] $inventoryItem1C
 * @property Collection|Rooms1C[] $rooms1C
 */
class Mol extends Model
{
    private const EMPTY = '';
    private const EMPTY_TAB_NUMBER = -1;

    protected static $schema = [
        'table' => 'storage_1c.mols',
        'columns' => [
            'fio' => ['type' => 'string'],
            'molTabNumber' => ['type' => 'int'],
        ],
        'relations' => [
            'inventoryItem1C' => ['type' => self::HAS_MANY, 'model' => InventoryItem1C::class, 'by' => '__mol_id'],
            'rooms1C' => ['type' => self::MANY_TO_MANY, 'model' => Rooms1C::class, 'pivot' => 'storage_1c.mol_rooms1C', 'this' => '__mol_id', 'that' => '__rooms_1c_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        $dyplicateByMolTabNumber = self::findByColumn('molTabNumber', $this->molTabNumber);

        if (true === $this->isNew() && false !== $dyplicateByMolTabNumber) {
            throw new Exception('A Mol with this tabNumber exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByMolTabNumber && $dyplicateByMolTabNumber->getPk() != $this->getPk()) {
            throw new Exception('A Mol with this tabNumber exists');
        }
        return true;
    }

    /**
     * @param string $molTabNumber
     * @return bool
     * @throws Exception
     */
    protected function validateMolTabNumber(string $molTabNumber): bool
    {
        if (empty(trim($molTabNumber))) {
            throw new Exception('Not a valid Mol\'s tabNumber value');
        }
        return true;
    }

    /**
     * @param string $molTabNumber
     * @return string
     */
    protected function sanitizeMolTabNumber(string $molTabNumber): string
    {
        return trim($molTabNumber);
    }

    /**
     * @param string $fio
     * @return bool
     * @throws Exception
     */
    protected function validateFio(string $fio): bool
    {
        if (empty(trim($fio)) && self::EMPTY != $fio) {
            throw new Exception('Not a valid Mol\'s fio value');
        }
        return true;
    }

    /**
     * @param string $fio
     * @return string
     */
    protected function sanitizeFio(string $fio): string
    {
        return trim($fio);
    }

    /**
     * @return Mol
     */
    public static function getEmptyInstance(): self
    {
        $mol = self::findByColumn('molTabNumber', self::EMPTY_TAB_NUMBER);
        if (false === $mol) {
            $mol = new self();
            $mol->molTabNumber = self::EMPTY_TAB_NUMBER;
            $mol->fio = self::EMPTY;
            $mol->save();
        }
        return $mol;
    }
}
