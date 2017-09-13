<?php

namespace App\Models;

use App\ViewModels\DevModulePortGeo;
use T4\Core\Collection;
use T4\Core\ISingleton;
use T4\Core\TSingleton;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class LotusLocation
 * @package App\Models
 *
 * @property int lotus_id
 * @property string title
 * @property string reg_center
 * @property int reg_center_id
 * @property string region
 * @property int region_id
 * @property string city
 * @property int city_id
 * @property string address
 * @property string status
 * @property int employees
 */
class LotusLocation extends Model
{
    const CONNECTION_NAME = 'lotusData';

    protected static $schema = [
        'table' => 'locations',
        'columns' => [
            'lotus_id' => ['type' => 'int'],
            'title' => ['type' => 'string'],
            'reg_center' => ['type' => 'string'],
            'reg_center_id' => ['type' => 'int'],
            'region' => ['type' => 'string'],
            'region_id' => ['type' => 'int'],
            'city' => ['type' => 'string'],
            'city_id' => ['type' => 'int'],
            'address' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'employees' => ['type' => 'int'],
        ]
    ];

    /**
     * @var Collection $allLocations
     */
    private static $allLocations = [];
    private static $lotusIdToEmployees = [];
    private static $peopleCounter = [];

    public static function peopleCountByLotusId($lotusId, $refresh = false)
    {
        if (empty(self::$lotusIdToEmployees) || $refresh) {
            self::setConnection(self::CONNECTION_NAME);
            self::$allLocations = self::findAll();
            foreach (self::$allLocations as $office) {
                self::$lotusIdToEmployees[$office->lotus_id] = $office->employees;
            }
        }
        return (key_exists((int)$lotusId, self::$lotusIdToEmployees)) ? self::$lotusIdToEmployees[(int)$lotusId] : false;
    }
    public static function employeesByLotusId($lotusId, $refresh = false)
    {
        if (empty(self::$allLocations) || $refresh) {
            self::setConnection(self::CONNECTION_NAME);
            self::$allLocations = self::findAll();
        }
        if (empty(self::$lotusIdToEmployees) || $refresh) {
            foreach (self::$allLocations as $office) {
                self::$lotusIdToEmployees[$office->lotus_id] = $office->employees;
            }
        }
        return (key_exists((int)$lotusId, self::$lotusIdToEmployees)) ? self::$lotusIdToEmployees[(int)$lotusId] : false;
    }

    public static function toLotusIdArray()
    {
        if (empty(self::$allLocations)) {
            self::setConnection(self::CONNECTION_NAME);
            self::$allLocations = self::findAll();
        }
        $res = [];
        foreach (self::$allLocations as $office) {
            $res[$office->lotus_id] = $office->employees;
        }
        return $res;
    }

    public static function countPeoples(array $Lotus_id = [])
    {
        if (empty($Lotus_id)) {
            $res = 0;
        } else {
            $query = (new Query())
                ->select('sum(employees)')
                ->from(self::getTableName())
                ->where('lotus_id IN (' . implode(',', $Lotus_id) . ')');
            self::setConnection(self::CONNECTION_NAME);
            $res = self::getDbConnection()->query($query)->fetchScalar();
        }
        return $res;

    }

}