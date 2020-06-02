<?php
namespace App\Components\Swiitch;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\Office;
use T4\Core\Collection;
use T4\Dbal\Query;

class SwitchService
{
    private const SQL = [
        'liveSwitches' => '
            SELECT appliance.*
            FROM equipment.appliances appliance
            LEFT JOIN equipment."applianceTypes" appliance_type ON appliance.__type_id = appliance_type.__id
            WHERE appliance_type.type = :app_type
                AND ((date_part(\'epoch\' :: TEXT, age(now(), appliance."lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER < :app_lifetime',
        'liveSwitchesInOffice' => '
            SELECT appliance.*
            FROM equipment.appliances appliance
            LEFT JOIN equipment."applianceTypes" appliance_type ON appliance.__type_id = appliance_type.__id
            LEFT JOIN company.offices office ON appliance.__location_id = office.__id
            WHERE appliance_type.type = :app_type
                AND ((date_part(\'epoch\' :: TEXT, age(now(), appliance."lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER < :app_lifetime
                AND office."lotusId" = :lotus_id',
        'switchesAvailableForPollingCdpNeighbors' => '
            SELECT appliance.*
            FROM equipment.appliances appliance
                LEFT JOIN equipment."applianceTypes" appliance_type ON appliance.__type_id = appliance_type.__id
                LEFT JOIN equipment."platformItems" platform_item ON appliance.__platform_item_id = platform_item.__id
                LEFT JOIN equipment.platforms platform ON platform_item.__platform_id = platform.__id
            WHERE appliance_type.type = :app_type
                AND ((date_part(\'epoch\' :: TEXT, age(now(), appliance."lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER < :app_lifetime
                AND platform.title NOT IN (:pl_title1, :pl_title2, :pl_title3, :pl_title4, :pl_title5, :pl_title6, :pl_title7, :pl_title8)',
    ];

    /**
     * @param int $pk
     * @return Appliance|bool
     */
    public function switchWithPk(int $pk)
    {
        $appliance = Appliance::findByPK($pk);
        return (false != $appliance && $appliance->type->type == ApplianceType::SWITCH) ? $appliance : false;
    }

    /**
     * Valid lifetime switches
     * @return Collection of the Appliances
     */
    public function liveSwitches(): Collection
    {
        return Appliance::findAllByQuery(
            new Query(self::SQL['liveSwitches']),
            [
                ':app_type' => ApplianceType::SWITCH,
                ':app_lifetime' => Appliance::LIFETIME
            ]
        );
    }

    /**
     * Valid lifetime switches in the office
     * @param Office $office
     * @return Collection of the Appliances
     */
    public function liveSwitchesInOffice(Office $office): Collection
    {
        return Appliance::findAllByQuery(
            new Query(self::SQL['liveSwitchesInOffice']),
            [
                ':app_type' => ApplianceType::SWITCH,
                ':app_lifetime' => Appliance::LIFETIME,
                ':lotus_id' => $office->lotusId
            ]
        );
    }

    /**
     * Valid lifetime switches available for polling CDP neighbors
     * @return Collection of Appliances
     */
    public function switchesAvailableForPollingCdpNeighbors(): Collection
    {
        return Appliance::findAllByQuery(
            new Query(self::SQL['switchesAvailableForPollingCdpNeighbors']),
            [
                ':app_type' => ApplianceType::SWITCH,
                ':app_lifetime' => Appliance::LIFETIME,
                ':pl_title1' => 'WS-C4948',
                ':pl_title2' => 'WS-C4948-10GE',
                ':pl_title3' => 'WS-C4948E',
                ':pl_title4' => 'WS-C6509-E',
                ':pl_title5' => 'WS-C6513',
                ':pl_title6' => 'N2K-C2232PP',
                ':pl_title7' => 'N5K-C5548P',
                ':pl_title8' => 'WS-CBS3110G-S-I',
            ]
        );
    }
}
