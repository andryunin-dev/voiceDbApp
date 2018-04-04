<?php

namespace App\Storage1CModels;

use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class InventoryItemCategory
 * @package App\Storage1CModels
 *
 * @property string $title
 * @property Collection|InventoryItem1C[] $inventoryItems
 */
class InventoryItemCategory extends Model
{
    public const APPLIANCE = 'appliance';
    public const MODULE = 'module';
    private const AUTOMATICALLY_UNDEFINE = 'automaticallyUndefined';
    private const NOT_INTERESTED = 'notInterested';

    protected static $schema = [
        'table' => 'storage_1c.categories',
        'columns' => [
            'title' => ['type' => 'string'],
        ],
        'relations' => [
            'inventoryItems' => ['type' => self::HAS_MANY, 'model' => InventoryItem1C::class, 'by' => '__category_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        $dyplicateByTitle = self::findByColumn('title', $this->title);

        if (true === $this->isNew() && false !== $dyplicateByTitle) {
            throw new Exception('A InventoryItemCategory  with this title exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByTitle && $dyplicateByTitle->getPk() != $this->getPk()) {
            throw new Exception('A InventoryItemCategory  with this title exists');
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
        if (empty(trim($title))) {
            throw new Exception('Not a valid InventoryItemCategory\'s title value');
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
     * @return InventoryItemCategory
     */
    public static function getApplianceCategory(): self
    {
        $category = self::findByColumn('title', self::APPLIANCE);
        if (false === $category) {
            $category = new self(['title' => self::APPLIANCE]);
            $category->save();
        }
        return $category;
    }

    /**
     * @return InventoryItemCategory
     */
    public static function getModuleCategory(): self
    {
        $category = self::findByColumn('title', self::MODULE);
        if (false === $category) {
            $category = new self(['title' => self::MODULE]);
            $category->save();
        }
        return $category;
    }

    /**
     * @return InventoryItemCategory
     */
    public static function getAutomaticallyUndefineCategory(): self
    {
        $category = self::findByColumn('title', self::AUTOMATICALLY_UNDEFINE);
        if (false === $category) {
            $category = new self(['title' => self::AUTOMATICALLY_UNDEFINE]);
            $category->save();
        }
        return $category;
    }

    /**
     * @return InventoryItemCategory
     */
    public static function getNotInterestedCategory(): self
    {
        $category = self::findByColumn('title', self::NOT_INTERESTED);
        if (false === $category) {
            $category = new self(['title' => self::NOT_INTERESTED]);
            $category->save();
        }
        return $category;
    }
}
