<?php

namespace App\Storage1CModels;

use T4\Core\Collection;
use T4\Orm\Exception;
use T4\Orm\Model;

/**
 * Class City1C
 * @package App\Storage1CModels
 *
 * @property string $title
 * @property Region1C $region1C
 * @property Collection|Rooms1C[] $rooms1C
 */
class City1C extends Model
{
    private const EMPTY = '';

    protected static $schema = [
        'table' => 'storage_1c.cities1C',
        'columns' => [
            'title' => ['type' => 'string'],
        ],
        'relations' => [
            'region1C' => ['type' => self::BELONGS_TO, 'model' => Region1C::class, 'by' => '__region_1c_id'],
            'rooms1C' => ['type' => self::HAS_MANY, 'model' => Rooms1C::class, 'by' => '__city_1c_id'],
        ],
    ];


    /**
     * @return bool
     * @throws Exception
     */
    protected function validate(): bool
    {
        if (!($this->region1C instanceof Region1C)) {
            throw new Exception('Not a valid City1C\'s region1C type');
        }

        $dyplicateByTitle = self::findByColumn('title', $this->title);

        if (true === $this->isNew() && false !== $dyplicateByTitle) {
            throw new Exception('A City1C with this name exists');
        }
        if (false === $this->isNew() && false !== $dyplicateByTitle && $dyplicateByTitle->getPk() != $this->getPk()) {
            throw new Exception('A City1C with this name exists');
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
            throw new Exception('Not a valid City1C\'s name value');
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
     * @return City1C
     */
    public static function getEmptyInstance(): self
    {
        $city1C = self::findByColumn('title', self::EMPTY);
        if (false === $city1C) {
            $city1C = new self(['title' => self::EMPTY]);
            $city1C->save();
        }
        return $city1C;
    }
}
