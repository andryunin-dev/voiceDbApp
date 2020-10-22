<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1602847260_createPhoneRecorderView
    extends Migration
{

    public function up()
    {
        $viewTitle = 'view.phone_recorder';
        $phonePrefix = '558';

        $sql['create view phone_recorder'] = '
            CREATE VIEW ' . $viewTitle . ' AS
            WITH appliance AS (
                WITH
                    appliance1c AS (
                        SELECT
                            appliance1c.__voice_appliance_id,
                            inventory_item."inventoryNumber"
                        FROM storage_1c."appliances1C" appliance1c
                        LEFT JOIN storage_1c."inventoryItem1C" inventory_item ON inventory_item.__id = appliance1c.__inventory_item_id
                    ),
                    location AS (
                        SELECT
                            office.__id AS office_id,
                            office.title AS office_title,
                            city.title AS city_title
                        FROM company.offices office
                        LEFT JOIN geolocation.addresses address ON office.__address_id = address.__id
                        LEFT JOIN geolocation.cities city ON address.__city_id = city.__id
                    )
                SELECT
                    appliance.__id,
                    location.city_title,
                    location.office_title,
                    dataport."ipAddress",
                    appliance1c."inventoryNumber",
                    appliance."lastUpdate",
                    (date_part(\'epoch\'::TEXT, age(now(), appliance."lastUpdate")) / 3600::DOUBLE PRECISION)::INTEGER AS "appAge"
                FROM equipment.appliances appliance
                LEFT JOIN location ON appliance.__location_id = location.office_id
                LEFT JOIN equipment."dataPorts" dataport ON appliance.__id = dataport.__appliance_id AND dataport."isManagement" IS TRUE
                LEFT JOIN appliance1c ON appliance.__id = appliance1c.__voice_appliance_id
            )
            SELECT
                appliance.city_title AS city,
                appliance.office_title AS office,
                phone.name AS "phoneName",
                phone.model AS "phoneModel",
                phone_with_recorder.recorder,
                concat(phone.prefix, phone."phoneDN")::citext AS "phoneDN",
                concat(phone.prefix, \'-\', phone."phoneDN")::citext AS "displayedDN",
                appliance."ipAddress",
                appliance."lastUpdate",
                appliance."appAge"
            FROM equipment."phoneInfo" phone
            LEFT JOIN appliance ON phone.__appliance_id = appliance.__id
            LEFT JOIN verint558."infoKDVerintChannel" phone_with_recorder ON phone.prefix = \'' . $phonePrefix . '\' AND phone."phoneDN" = phone_with_recorder.dn
        ';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $viewTitle = 'view.phone_recorder';

        $sql['drop view phone_recorder'] = 'DROP VIEW IF EXISTS ' . $viewTitle;

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

}
