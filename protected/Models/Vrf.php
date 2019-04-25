<?php

namespace App\Models;

use App\Components\Ip;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Orm\Model;

/**
 * Class Vrf
 * @package App\Models
 *
 * @property string $name
 * @property string $rd
 * @property string $comment
 *
 * @property Collection|Network[] $networks
 */
class Vrf extends Model
{
    protected static $schema = [
        'table' => 'network.vrfs',
        'columns' => [
            'name' => ['type' => 'string'], //VRF name in lower case (unique)
            'rd' => ['type' => 'string'], //RD (i.e '123:12', '10.1.1.2:125')
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'networks' => ['type' => self::HAS_MANY, 'model' => Network::class, 'by' => '__vrf_id']
        ]
    ];

    const GLOBAL_VRF_NAME = 'global';
    const GLOBAL_VRF_RD = '0:0';
    const GLOBAL_VRF_COMMENT = 'global VRF';

    protected function validateName($val)
    {
        if (!is_string($val)) {
            throw new Exception('Недопустимое имя RD');
        }
        return true;
    }

    protected function sanitizeName($val)
    {
        return strtolower(trim($val));
    }

    /**
     * @param $val
     * @return bool
     * @throws Exception
     * examples valid formats RD - '123:12', '10.1.1.2:125'
     */
    protected function validateRd($val)
    {
        if (!is_string($val)) {
            throw new Exception('Недопустимый тип свойства RD');
        }
        $val = trim($val);
        $rdArray = explode(':', $val);
        foreach ($rdArray as $key => $rdValue) {
            $rdArray[$key] = trim($rdValue);
        }
        //RD must consist from 2 part
        if (2 != count($rdArray)) {
            throw new Exception('Неверный формат RD');
        }
        //check second part of RD (it must be integer string)
        $second = trim(array_pop($rdArray));
        if (0 == strlen($second)) {
            throw new Exception('Неверный формат RD');
        }
        if (!(is_numeric($second) && (int)$second == $second)) {
            throw new Exception('Неверный формат RD');
        }
        //check first part of RD (it must be not empty, integer or IP string)
        $first = trim(array_pop($rdArray));
        if (0 == strlen($first)) {
            throw new Exception('Неверный формат RD');
        }
        if (!(true === (new Ip($first, 1))->is_valid || $first == (int)$first)) {
            throw new Exception('Неверный формат RD');
        }
        return true;
    }

    protected function sanitizeRd($val)
    {
        $rdArray = explode(':', $val);
        foreach ($rdArray as $key => $rdValue) {
            $rdArray[$key] = trim($rdValue);
        }
        return implode(':', $rdArray);
    }



    protected function validate()
    {
        $fromDb = self::findByColumn('rd', $this->rd);
        if ($this->isNew && false !== $fromDb) {
            throw new Exception('VRF с данным RD уже существует');
        }
        if ($this->isUpdated && false !== $fromDb && $this->getPk() != $fromDb->getPk()) {
            throw new Exception('VRF с данным RD уже существует');
        }
        if (strtolower(self::GLOBAL_VRF_NAME) == strtolower($this->name) && self::GLOBAL_VRF_RD != $this->rd) {
            throw new Exception('Данное имя зарезервировано для Global VRF');
        }
        if (self::GLOBAL_VRF_RD == $this->rd && self::GLOBAL_VRF_NAME != strtolower($this->name)) {
            throw new Exception('Данное RD зарезервировано для Global VRF');
        }
        return true;
    }

    public function __toString()
    {
        return $this->name . ((self::GLOBAL_VRF_RD != $this->rd) ? '(' . $this->rd . ')' : '');
    }

    protected function beforeDelete()
    {
        if ($this->networks->count() > 0) {
            throw new Exception('Данный VRF используется.<br> Удаление невозможно.');
        }
        return parent::beforeDelete();
    }

    public static function instanceGlobalVrf()
    {
        $gVrf = self::findByColumn('name', self::GLOBAL_VRF_NAME);
        if (false === $gVrf) {
            $gVrf = (new self())
                ->fill([
                    'name' => self::GLOBAL_VRF_NAME,
                    'rd' => self::GLOBAL_VRF_RD,
                    'comment' => self::GLOBAL_VRF_COMMENT
                ])
                ->save();
        }
        return $gVrf;
    }

    public static function findAll($options = [])
    {
        $allowedSortFields = [
            'name',
            'rd'
        ];
        $directions = [
            'asc',
            'desc'
        ];
        $sortOrder = [];
        if (is_array($options)) {
            foreach ($options as $field => $direction) {
                if (
                    ! in_array(strtolower($field), $allowedSortFields) ||
                    ! in_array(strtolower($direction), $directions)
                ) {
                    continue;
                }
                $sortOrder[strtolower($field)] = strtolower($direction);
                unset($options[$field]);
            }
        }

        $vrfs = parent::findAll($options);
        if (empty($sortOrder)) {
            return $vrfs;
        }

        $vrfs = $vrfs->uasort(function (Vrf $vrf1, Vrf $vrf2) use (&$sortOrder) {
            $result = 1;
            foreach ($sortOrder as $field => $direction) {
                switch ($field) {
                    case 'name':
                        if (Vrf::GLOBAL_VRF_NAME == $vrf1->name && Vrf::GLOBAL_VRF_NAME != $vrf2->name) {
                            $result = -1;
                        } elseif (Vrf::GLOBAL_VRF_NAME != $vrf1->name && Vrf::GLOBAL_VRF_NAME == $vrf2->name) {
                            $result = 1;
                        } else {
                            $result = strnatcmp(strtolower($vrf1->name), strtolower($vrf2->name));
                        }
                        break;
                    case 'rd':
                        if (Vrf::GLOBAL_VRF_RD == $vrf1->rd && Vrf::GLOBAL_VRF_RD != $vrf2->rd) {
                            $result = -1;
                        } elseif (Vrf::GLOBAL_VRF_RD != $vrf1->rd && Vrf::GLOBAL_VRF_RD == $vrf2->rd) {
                            $result = 1;
                        } else {
                            $result = strnatcmp($vrf1->rd, $vrf2->rd);
                        }
                        break;
                }
                if (0 != $result) {
                    $result = ('asc' == $direction) ? $result : (-1) * $result;
                    break;
                }
            }
            return $result ?: 1;
        });
        return $vrfs;

    }
}