<?php

namespace App\Storage1CModels;

use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class Region1C
 * @package App\Storage1CModels
 *
 * @property string $title
 * @property Collection|City1C[] $cities1C
 */
class Region1C extends Model
{
    private const EMPTY = '';

    protected static $schema = [
        'table' => 'storage_1c.regions1C',
        'columns' => [
            'title' => ['type' => 'string'],
        ],
        'relations' => [
            'cities1C' => ['type' => self::HAS_MANY, 'model' => City1C::class, 'by' => '__region_1c_id'],
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
            throw new Exception('A Region with this name exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByTitle && $dyplicateByTitle->getPk() != $this->getPk()) {
            throw new Exception('A Region with this name exists');
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
            throw new Exception('Not a valid Region\'s name value');
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
     * @return Region1C
     */
    public static function getEmptyInstance(): self
    {
        $region1C = self::findByColumn('title', self::EMPTY);
        if (false === $region1C) {
            $region1C = new self(['title' => self::EMPTY]);
            $region1C->save();
        }
        return $region1C;
    }
}
