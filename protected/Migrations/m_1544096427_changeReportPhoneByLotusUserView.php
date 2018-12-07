<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1544096427_changeReportPhoneByLotusUserView
    extends Migration
{

    public function up()
    {
        $targetView = 'view.report_phone_by_lotus_user';

        $sql['drop_old__view.report_phone_by_lotus_user '] = 'DROP VIEW IF EXISTS '.$targetView;
        $sql['create_view.report_phone_by_lotus_user '] = '
            CREATE VIEW '.$targetView.' AS
                WITH
                    phone AS (SELECT __id, __appliance_id, model, (prefix || "phoneDN") AS dn, "alertingName", "cdpNeighborPort", "cdpNeighborIP" FROM equipment."phoneInfo"),

                    appliance AS (
                        WITH
                            appliance AS (
                                SELECT 
                                    __id, 
                                    __platform_item_id, 
                                    __location_id,
                                    (CASE
                                        WHEN
                                            (((date_part(\'epoch\' :: TEXT, age(now(), "lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER) < 73
                                        THEN
                                            \'да\'
                                        ELSE
                                            \'нет\'
                                    END) AS "isActive"
                                FROM equipment.appliances
                            ),
                            "dataPort" AS (SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" WHERE "isManagement" IS TRUE),
                
                            "platformItem" AS (
                                WITH
                                platform AS (
                                    WITH
                                        vendor AS (SELECT __id, title FROM equipment.vendors)
                                    SELECT
                                        platform.__id,
                                        platform.title,
                                        vendor.title AS vendor
                                    FROM equipment.platforms AS platform
                                    LEFT JOIN vendor ON platform.__vendor_id = vendor.__id
                                )
                                SELECT
                                    "platformItems".__id,
                                    "platformItems"."serialNumber",
                                    platform.__id AS platform_id,
                                    platform.title AS platform,
                                    platform.vendor AS vendor
                                FROM equipment."platformItems" AS "platformItems"
                                LEFT JOIN platform ON "platformItems".__platform_id = platform.__id
                            ),
                
                            office AS (
                                WITH
                                    address AS (
                                    WITH
                                        city AS (
                                        WITH
                                            region AS (SELECT __id, title FROM geolocation.regions)
                                        SELECT
                                            city.__id,
                                            region.__id AS region_id,
                                            region.title AS region
                                        FROM geolocation.cities AS city
                                        LEFT JOIN region ON city.__region_id = region.__id
                                      )
                                    SELECT
                                        address.__id,
                                        city.region_id,
                                        city.region
                                    FROM geolocation.addresses AS address
                                    LEFT JOIN city ON address.__city_id = city.__id
                                  )
                                SELECT
                                    office.__id,
                                    office.title,
                                    address.region_id,
                                    address.region
                                FROM company.offices AS office
                                LEFT JOIN address ON office.__address_id = address.__id
                            ),
                
                            "appliance1c" AS (
                                WITH
                                    "inventoryItem" AS (
                                       WITH
                                           "foreigh1c" AS (SELECT inventory_number, inventory_user, type_of_nomenclature FROM storage_1c.foreign_1c),
                                           "nomenclature1C" AS (
                                               WITH
                                                   "nomenclatureType" AS (SELECT * FROM storage_1c."nomenclatureTypes")
                                               SELECT
                                                   "nomenclature1C".__id,
                                                   "nomenclatureType".type
                                               FROM storage_1c."nomenclature1C" AS "nomenclature1C"
                                               LEFT JOIN "nomenclatureType" ON "nomenclature1C".__type_id = "nomenclatureType".__id
                                           )
                                       SELECT
                                           "inventoryItem1C".__id,
                                           "inventoryItem1C"."inventoryNumber",
                                           "inventoryItem1C".__mol_id,
                                           foreigh1c.inventory_user
                                       FROM storage_1c."inventoryItem1C" AS "inventoryItem1C"
                                       LEFT JOIN "nomenclature1C" ON "inventoryItem1C".__nomenclature_id = "nomenclature1C".__id
                                       LEFT JOIN "foreigh1c" ON "inventoryItem1C"."inventoryNumber" = "foreigh1c".inventory_number AND "foreigh1c".type_of_nomenclature = "nomenclature1C".type
                                    ),
                                    "mol" AS (SELECT __id, fio FROM storage_1c.mols)
                                SELECT
                                    "appliances1C".__voice_appliance_id,
                                    "inventoryItem"."inventoryNumber",
                                    "inventoryItem"."inventory_user",
                                    mol.fio AS mol
                                FROM storage_1c."appliances1C" AS "appliances1C"
                                LEFT JOIN "inventoryItem" ON "appliances1C".__inventory_item_id = "inventoryItem".__id
                                LEFT JOIN mol ON "inventoryItem".__mol_id = mol.__id
                            )
                
                        SELECT
                            appliance.__id,
                            "platformItem"."serialNumber",
                            "platformItem".platform_id,
                            "platformItem".platform,
                            "platformItem".vendor,
                            "dataPort"."ipAddress",
                            appliance1c."inventoryNumber",
                            appliance1c.inventory_user,
                            appliance1c.mol,
                            office.region_id,
                            office.region,
                            office.__id AS office_id,
                            office.title AS office,
                            appliance."isActive"
                        FROM appliance
                        LEFT JOIN "platformItem" ON appliance.__platform_item_id = "platformItem".__id
                        LEFT JOIN "dataPort" ON "dataPort".__appliance_id = appliance.__id
                        LEFT JOIN office ON appliance.__location_id = office.__id
                        LEFT JOIN appliance1c ON appliance1c.__voice_appliance_id = appliance.__id
                    ),

                    switch AS (
                        SELECT
                            (appliance.vendor || \' \' || appliance.platform) AS platform,
                            appliance."ipAddress",
                            appliance."inventoryNumber"
                        FROM appliance
                    ),
                
                    "phoneBook" AS (
                        WITH
                            position AS (
                                WITH
                                    position AS (SELECT DISTINCT position FROM view.lotus_db_phone_book ORDER BY position)
            
                                SELECT
                                    row_number() OVER (ORDER BY position) AS __id,
                                    position AS title
                                FROM position
                            )

                        SELECT
                             (surname || \' \' || name || \' \' || patronymic) AS name,
                             position.__id AS position_id,
                             position.title AS position,
                             division,
                             work_phone,
                             mobile_phone,
                             work_email
                        FROM view.lotus_db_phone_book AS lotus_db_phone_book
                        LEFT JOIN position ON lotus_db_phone_book.position = position.title
                    )

                SELECT
                    cast(appliance.region_id AS citext),
                    appliance.region,
                    cast(appliance.office_id AS citext),
                    appliance.office,
                    appliance."isActive",
                    cast(appliance.platform_id AS citext),
                    phone.model,
                    phone.dn,
                    phone."alertingName",
                    host(appliance."ipAddress") AS "ipAddress",
                    appliance."serialNumber",
                    appliance."inventoryNumber",
                    appliance.mol,
                    appliance.inventory_user AS "inventoryUser",
                    "phoneBook".name AS "lotusUser",
                    "phoneBook".position_id "lotusUserPosition_id",
                    "phoneBook".position "lotusUserPosition",
                    "phoneBook".division "lotusUserDivision",
                    "phoneBook".mobile_phone AS "lotusUserMobilePhone",
                    "phoneBook".work_email AS "lotusUserWorkEmail",
                    switch.platform AS "switchPlatform",
                    host(phone."cdpNeighborIP") AS "switchIp",
                    phone."cdpNeighborPort" AS "switchPort",
                    switch."inventoryNumber" AS "switchInventoryNumber"
                FROM phone
                LEFT JOIN "phoneBook" ON phone.dn = "phoneBook".work_phone
                LEFT JOIN switch ON switch."ipAddress" = phone."cdpNeighborIP"
                LEFT JOIN appliance ON phone.__appliance_id = appliance.__id
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $targetView = 'view.report_phone_by_lotus_user';

        $sql['drop__view.report_phone_by_lotus_user '] = 'DROP VIEW IF EXISTS '.$targetView;
        $sql['create_old_view.report_phone_by_lotus_user '] = '
            CREATE VIEW '.$targetView.' AS
                WITH
                    phone AS (SELECT __id, __appliance_id, model, (prefix || "phoneDN") AS dn, "alertingName", "cdpNeighborPort", "cdpNeighborIP" FROM equipment."phoneInfo"),

                    appliance AS (
                        WITH
                            appliance AS (
                                SELECT 
                                    __id, 
                                    __platform_item_id, 
                                    __location_id,
                                    (CASE
                                        WHEN
                                            (((date_part(\'epoch\' :: TEXT, age(now(), "lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER) < 73
                                        THEN
                                            \'да\'
                                        ELSE
                                            \'нет\'
                                    END) AS "isActive"
                                FROM equipment.appliances
                            ),
                            "dataPort" AS (SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" WHERE "isManagement" IS TRUE),
                
                            "platformItem" AS (
                                WITH
                                platform AS (
                                    WITH
                                        vendor AS (SELECT __id, title FROM equipment.vendors)
                                    SELECT
                                        platform.__id,
                                        platform.title,
                                        vendor.title AS vendor
                                    FROM equipment.platforms AS platform
                                    LEFT JOIN vendor ON platform.__vendor_id = vendor.__id
                                )
                                SELECT
                                    "platformItems".__id,
                                    "platformItems"."serialNumber",
                                    platform.title AS platform,
                                    platform.vendor AS vendor
                                FROM equipment."platformItems" AS "platformItems"
                                LEFT JOIN platform ON "platformItems".__platform_id = platform.__id
                            ),
                
                            office AS (
                                WITH
                                    address AS (
                                    WITH
                                        city AS (
                                        WITH
                                            region AS (SELECT __id, title FROM geolocation.regions)
                                        SELECT
                                            city.__id,
                                            region.title AS region
                                        FROM geolocation.cities AS city
                                        LEFT JOIN region ON city.__region_id = region.__id
                                      )
                                    SELECT
                                        address.__id,
                                        city.region
                                    FROM geolocation.addresses AS address
                                    LEFT JOIN city ON address.__city_id = city.__id
                                  )
                                SELECT
                                    office.__id,
                                    office.title,
                                    address.region
                                FROM company.offices AS office
                                LEFT JOIN address ON office.__address_id = address.__id
                            ),
                
                            "appliance1c" AS (
                                WITH
                                    "inventoryItem" AS (
                                       WITH
                                           "foreigh1c" AS (SELECT inventory_number, inventory_user, type_of_nomenclature FROM storage_1c.foreign_1c),
                                           "nomenclature1C" AS (
                                               WITH
                                                   "nomenclatureType" AS (SELECT * FROM storage_1c."nomenclatureTypes")
                                               SELECT
                                                   "nomenclature1C".__id,
                                                   "nomenclatureType".type
                                               FROM storage_1c."nomenclature1C" AS "nomenclature1C"
                                               LEFT JOIN "nomenclatureType" ON "nomenclature1C".__type_id = "nomenclatureType".__id
                                           )
                                       SELECT
                                           "inventoryItem1C".__id,
                                           "inventoryItem1C"."inventoryNumber",
                                           "inventoryItem1C".__mol_id,
                                           foreigh1c.inventory_user
                                       FROM storage_1c."inventoryItem1C" AS "inventoryItem1C"
                                       LEFT JOIN "nomenclature1C" ON "inventoryItem1C".__nomenclature_id = "nomenclature1C".__id
                                       LEFT JOIN "foreigh1c" ON "inventoryItem1C"."inventoryNumber" = "foreigh1c".inventory_number AND "foreigh1c".type_of_nomenclature = "nomenclature1C".type
                                    ),
                                    "mol" AS (SELECT __id, fio FROM storage_1c.mols)
                                SELECT
                                    "appliances1C".__voice_appliance_id,
                                    "inventoryItem"."inventoryNumber",
                                    "inventoryItem"."inventory_user",
                                    mol.fio AS mol
                                FROM storage_1c."appliances1C" AS "appliances1C"
                                LEFT JOIN "inventoryItem" ON "appliances1C".__inventory_item_id = "inventoryItem".__id
                                LEFT JOIN mol ON "inventoryItem".__mol_id = mol.__id
                            )
                
                        SELECT
                            appliance.__id,
                            "platformItem"."serialNumber",
                            "platformItem".platform,
                            "platformItem".vendor,
                            "dataPort"."ipAddress",
                            appliance1c."inventoryNumber",
                            appliance1c.inventory_user,
                            appliance1c.mol,
                            office.region,
                            office.title AS office,
                            appliance."isActive"
                        FROM appliance
                        LEFT JOIN "platformItem" ON appliance.__platform_item_id = "platformItem".__id
                        LEFT JOIN "dataPort" ON "dataPort".__appliance_id = appliance.__id
                        LEFT JOIN office ON appliance.__location_id = office.__id
                        LEFT JOIN appliance1c ON appliance1c.__voice_appliance_id = appliance.__id
                    ),

                    switch AS (
                        SELECT
                            (appliance.vendor || \' \' || appliance.platform) AS platform,
                            appliance."ipAddress",
                            appliance."inventoryNumber"
                        FROM appliance
                    ),
                
                    "phoneBook" AS (
                        SELECT
                            (surname || \' \' || name || \' \' || patronymic) AS name,
                            position,
                            division,
                            work_phone,
                            mobile_phone,
                            work_email
                        FROM view.lotus_db_phone_book
                    )

                SELECT
                    appliance.region,
                    appliance.office,
                    appliance."isActive",
                    phone.model,
                    phone.dn,
                    phone."alertingName",
                    host(appliance."ipAddress") AS "ipAddress",
                    appliance."serialNumber",
                    appliance."inventoryNumber",
                    appliance.mol,
                    appliance.inventory_user AS "inventoryUser",
                    "phoneBook".name AS "lotusUser",
                    "phoneBook".position "lotusUserPosition",
                    "phoneBook".division "lotusUserDivision",
                    "phoneBook".mobile_phone AS "lotusUserMobilePhone",
                    "phoneBook".work_email AS "lotusUserWorkEmail",
                    switch.platform AS "switchPlatform",
                    host(phone."cdpNeighborIP") AS "switchIp",
                    phone."cdpNeighborPort" AS "switchPort",
                    switch."inventoryNumber" AS "switchInventoryNumber"
                FROM phone
                LEFT JOIN "phoneBook" ON phone.dn = "phoneBook".work_phone
                LEFT JOIN switch ON switch."ipAddress" = phone."cdpNeighborIP"
                LEFT JOIN appliance ON phone.__appliance_id = appliance.__id
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
