<?php


namespace App\ViewModels;


use T4\Core\Std;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class GeoDevStat
 * @package App\ViewModels
 *
 * @property string $office_id
 * @property string $office
 * @property int $lotusId
 * @property string $comment
 * @property Std $details
 * @property int $people
 * @property int $officeStatus_id
 * @property string $officeStatus
 * @property string $address
 * @property int $city_id
 * @property string $city
 * @property string $region
 * @property int $region_id
 * @property string $regCenter
 * @property Std[] $devStatistics
 */
class GeoDevStat extends Model
{
    use ViewHelperTrait;
    use DbaTrait;

    protected static $schema = [
        'table' => 'view.geo_devStat',
        'columns' => [
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'office' => ['type' => 'string'],
            'lotusId' => ['type' => 'int'],
            'comment' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'people' => ['type' => 'int'],
            'officeStatus_id' => ['type' => 'int', 'length' => 'big'],
            'officeStatus' => ['type' => 'string'],
            'address' => ['type' => 'string'],
            'city_id' => ['type' => 'int', 'length' => 'big'],
            'city' => ['type' => 'string'],
            'region_id' => ['type' => 'int', 'length' => 'big'],
            'region' => ['type' => 'string'],
            'regCenter' => ['type' => 'string'],
            'devStatistics' => ['type' => 'json']
        ]
    ];

    public static $columnMap = [];

    protected static $sortOrders = [
        'default' => 'region, city, office',
        'region' => 'region, city, office',
        'city' => 'city, office',
        'office' => 'office',
    ];

    protected function beforeSave()
    {
        return false;
    }

    public static function countPeople($query)
    {
        $peopleColumn = 'peoples';
        $peopleQuery = clone $query;
        $peopleQuery->select('sum(people) AS peoples');
        $result = self::findByQuery($peopleQuery)->$peopleColumn;
        return $result;
    }
}