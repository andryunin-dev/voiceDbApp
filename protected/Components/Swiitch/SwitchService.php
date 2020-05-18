<?php
namespace App\Components\Swiitch;

use App\Models\Appliance;
use App\Models\ApplianceType;
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
                AND ((date_part(\'epoch\' :: TEXT, age(now(), appliance."lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER < :lifetime',
        'liveSwitchesInOffice' => '
            SELECT appliance.*
            FROM equipment.appliances appliance
            LEFT JOIN equipment."applianceTypes" appliance_type ON appliance.__type_id = appliance_type.__id
            LEFT JOIN company.offices office ON appliance.__location_id = office.__id
            WHERE appliance_type.type = :app_type
                AND ((date_part(\'epoch\' :: TEXT, age(now(), appliance."lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER < :lifetime
                AND office."lotusId" = :lotus_id',
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
     * @return Collection
     */
    public function liveSwitches(): Collection
    {
        return Appliance::findAllByQuery(
            new Query(self::SQL['liveSwitches']),
            [
                ':app_type' => ApplianceType::SWITCH,
                ':lifetime' => Appliance::LIFETIME
            ]
        );
    }

    /**
     * Valid lifetime switches in the office
     * @param int $lotusId office ID
     * @return Collection
     */
    public function liveSwitchesInOffice(int $lotusId): Collection
    {
        return Appliance::findAllByQuery(
            new Query(self::SQL['liveSwitchesInOffice']),
            [
                ':app_type' => ApplianceType::SWITCH,
                ':lifetime' => Appliance::LIFETIME,
                ':lotus_id' => $lotusId
            ]
        );
    }
}
