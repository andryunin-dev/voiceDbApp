<?php

namespace App\Components\Statistics;


use T4\Core\Collection;
use T4\Core\Std;
use T4\Mvc\Application;
use T4\Orm\Model;

/**
 * Class ApplianceStatistic
 * @package App\Components\Statistics
 *
 * @property string $platform_id
 * @property string $platformTitle
 * @property string $platformVendor
 * @property int $total
 * @property int $active
 * @property int $notActive
 * @property int $active_inUse
 * @property int $active_notInUse
 * @property int $inUse
 * @property int $notInUse
 */
class PlatformStatistic extends Std
{
    protected static $age = 25;
    protected static $order = '"platformTitle", "platformVendor"';
    public function __construct($data = null)
    {
        parent::__construct($data);
    }

    protected function getAge()
    {
        return self::$age;
    }

    public static function findAll($order = null, $age = null)
    {
        self::$age = $age ?? self::$age;
        $sql = '
            SELECT devs.platform_id AS platform_id, devs."platformTitle" AS "platformTitle", devs."platformVendor" AS "platformVendor",
                count(devs.appliance_id) AS total,
                sum(CASE WHEN devs."appAge" < :max_age THEN 1 ELSE 0 END ) AS active,
                sum(CASE WHEN devs."appAge" >= :max_age OR devs."appAge" ISNULL THEN 1 ELSE 0 END ) AS "notActive",
                sum(CASE WHEN devs."appAge" < :max_age AND devs."appInUse" THEN 1 ELSE 0 END ) AS "active_inUse",
                sum(CASE WHEN devs."appAge" < :max_age AND NOT devs."appInUse" THEN 1 ELSE 0 END ) AS "active_notInUse",
                sum(CASE WHEN devs."appInUse" THEN 1 ELSE 0 END ) AS "inUse",
                sum(CASE WHEN NOT devs."appInUse" THEN 1 ELSE 0 END ) AS "notInUse"
            FROM view.geo_dev AS devs WHERE devs.platform_id NOTNULL 
            GROUP BY devs.platform_id ,devs."platformTitle", devs."platformVendor"
            ORDER BY ' . self::$order;
        $app = Application::instance();
        $con = $app->db->default;
        $res = $con->query($sql, [':max_age' => 25])->fetchAllObjects(self::class);
        return new Collection($res);
    }
}