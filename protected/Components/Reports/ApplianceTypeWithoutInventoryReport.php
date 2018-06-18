<?php

namespace App\Components\Reports;

use App\Models\ApplianceType;
use T4\Core\Collection;
use T4\Core\Std;
use T4\Mvc\Application;

class ApplianceTypeWithoutInventoryReport extends Std
{
    /**
     * @param array $appliancesTypes
     * @return Collection
     */
    public static function getAppliancesPercents(array $appliancesTypes)
    {
        $percens = new Collection();
        foreach ($appliancesTypes as $applianceType) {
            $percens->add(new self([
                'appType' => $applianceType,
                'percent' => self::getAppliancePercent($applianceType),
            ]));
        }
        return $percens;
    }

    /**
     * @return int|null
     */
    public static function getAppliancePercent(string $applianceType)
    {
        if (ApplianceType::PHONE == $applianceType) {
            return self::getPhonePercent();
        }

        $params = [':applianceType' => $applianceType];
        $sql = '
SELECT
  (sum(CASE WHEN appliance1c."invItem_inventoryNumber" ISNULL THEN 1 ELSE 0 END ) * 100 / count(appliance.appliance_id)) AS "percent"
FROM view.dev_geo AS appliance
  LEFT JOIN view.dev_appliance1c AS appliance1c ON appliance1c."appliance_id" = appliance."appliance_id"
WHERE appliance."appType" = :applianceType';

        $app = Application::instance();
        $connection = $app->db->default;
        $result = $connection->query($sql, $params)->fetchObject(self::class);
        return $result->percent;
    }

    /**
     * @return int|null
     */
    public static function getPhonePercent()
    {
        $params = [':applianceType' => ApplianceType::PHONE];
        $sql = '
SELECT
  (sum(CASE WHEN "appliance1c"."invItem_inventoryNumber" ISNULL THEN 1 ELSE 0 END ) * 100 / count(appliance.appliance_id)) AS percent
FROM view.dev_geo AS appliance
  LEFT JOIN view.dev_appliance1c AS "appliance1c" ON "appliance1c"."appliance_id" = appliance."appliance_id"
WHERE appliance."appType" = :applianceType AND  appliance."isHW" = TRUE';

        $app = Application::instance();
        $connection = $app->db->default;
        $result = $connection->query($sql, $params)->fetchObject(self::class);
        return $result->percent;
    }
}
