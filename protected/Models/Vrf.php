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
            'name' => ['type' => 'string'],
            'rd' => ['type' => 'string'],
            'comment' => ['type' => 'string']
        ],
        'relations' => [
            'networks' => ['type' => self::HAS_MANY, 'model' => Network::class, 'by' => '__vrf_id']
        ]
    ];

    const GLOBAL_VRF_NAME = 'global';
    const GLOBAL_VRF_RD = '0:0';
    const GLOBAL_VRF_COMMENT = 'global VRF';

    public static function findGlobalVrf()
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

    protected function validateName($val)
    {
        if (!is_string($val)) {
            throw new Exception('Недопустимое имя RD');
        }
        return true;
    }

    protected function sanitizeName($val)
    {
        return trim($val);
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
        return trim($val);
    }



    protected function validate()
    {
        if (false !== self::findByColumn('rd', $this->rd)) {
            throw new Exception('VRF с данным RD уже существует');
        }
        if (false !== self::findByColumn('name', $this->name)) {
            throw new Exception('VRF с именем "' . $this->name . '" уже существует');
        }
        if (strtolower(self::GLOBAL_VRF_NAME) == $this->name && self::GLOBAL_VRF_RD != $this->rd) {
            throw new Exception('Данное имя зарезервировано для Global VRF');
        }
        if (self::GLOBAL_VRF_RD == $this->rd && self::GLOBAL_VRF_NAME != strtolower($this->name)) {
            throw new Exception('Данное RD зарезервировано для Global VRF');
        }
        return true;
    }
}