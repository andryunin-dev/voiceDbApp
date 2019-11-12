<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1573539637_changeReportPhoneByLotusUserView
    extends Migration
{

    public function up()
    {
        $sql['drop old view.report_phone_by_lotus_user '] = 'DROP VIEW IF EXISTS view.report_phone_by_lotus_user';
        $sql['create view.report_phone_by_lotus_user '] = '
            CREATE VIEW view.report_phone_by_lotus_user AS    
                WITH phone AS ( SELECT (prefix || "phoneDN") AS dn, "alertingName", "cdpNeighborIP", "cdpNeighborPort", __appliance_id FROM equipment."phoneInfo" ),
                     appliance AS ( WITH platform_item AS ( SELECT __id, __platform_id, "serialNumber" FROM equipment."platformItems" ),
                                         platform AS ( SELECT __id, title FROM equipment.platforms ),
                                         dataport AS ( SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" WHERE "isManagement" IS TRUE ),
                                         office AS ( SELECT __id, title, __address_id FROM company.offices ),
                                         address AS ( SELECT __id, __city_id FROM geolocation.addresses ),
                                         city AS ( SELECT __id, __region_id FROM  geolocation.cities ),
                                         region AS ( SELECT * FROM  geolocation.regions),
                                         appliance_1c AS ( SELECT * FROM storage_1c."appliances1C"),
                                         inventory_item_1c AS ( SELECT __id, "inventoryNumber", __nomenclature_id FROM storage_1c."inventoryItem1C" ),
                                         nomenclature_1c AS ( SELECT * FROM storage_1c."nomenclature1C" ),
                                         nomenclature_type_1c AS ( SELECT * FROM storage_1c."nomenclatureTypes"),
                                         foreing_data_1c AS ( SELECT mol, inventory_user, inventory_number, type_of_nomenclature FROM storage_1c.foreign_1c )
                                    SELECT appliance.__id,
                                           CASE WHEN (((date_part(\'epoch\'::TEXT, age(now(), appliance."lastUpdate")) / (3600)::DOUBLE PRECISION))::INTEGER < 73)
                                                    THEN \'да\' ELSE \'нет\' END AS "isActive",
                                           appliance."lastUpdate",
                                           platform_item."serialNumber",
                                           platform.__id AS platform_id,
                                           platform.title AS model,
                                           dataport."ipAddress",
                                           region.__id AS region_id,
                                           region.title AS region,
                                           office.__id AS office_id,
                                           office.title AS office,
                                           inventory_item_1c."inventoryNumber",
                                           foreing_data_1c.mol,
                                           foreing_data_1c.inventory_user
                                    FROM equipment.appliances AS appliance
                                    LEFT JOIN platform_item ON appliance.__platform_item_id = platform_item.__id
                                    LEFT JOIN platform ON platform_item.__platform_id = platform.__id
                                    LEFT JOIN dataport ON appliance.__id = dataport.__appliance_id
                                    LEFT JOIN office ON appliance.__location_id = office.__id
                                    LEFT JOIN address ON office.__address_id = address.__id
                                    LEFT JOIN city ON address.__city_id = city.__id
                                    LEFT JOIN region ON city.__region_id = region.__id
                                    LEFT JOIN appliance_1c ON appliance.__id = appliance_1c.__voice_appliance_id
                                    LEFT JOIN inventory_item_1c ON appliance_1c.__inventory_item_id = inventory_item_1c.__id
                                    LEFT JOIN nomenclature_1c ON inventory_item_1c.__nomenclature_id = nomenclature_1c.__id
                                    LEFT JOIN nomenclature_type_1c ON nomenclature_1c.__type_id = nomenclature_type_1c.__id
                                    LEFT JOIN foreing_data_1c ON inventory_item_1c."inventoryNumber" = foreing_data_1c.inventory_number AND lower(nomenclature_type_1c.type) = lower(foreing_data_1c.type_of_nomenclature) ),
                     switch AS ( WITH dataport AS ( SELECT * FROM equipment."dataPorts" WHERE "isManagement" IS TRUE ),
                                      platform_item AS ( SELECT * FROM equipment."platformItems" ),
                                      platform AS ( SELECT * FROM equipment.platforms),
                                      appliance_1c AS ( SELECT * FROM storage_1c."appliances1C" ),
                                      inventory_item_1c AS ( SELECT * FROM storage_1c."inventoryItem1C")
                                 SELECT dataport."ipAddress",
                                        platform.title AS platform,
                                        inventory_item_1c."inventoryNumber"
                                 FROM equipment.appliances AS appliance
                                 LEFT JOIN dataport ON appliance.__id = dataport.__appliance_id
                                 LEFT JOIN platform_item ON appliance.__platform_item_id = platform_item.__id
                                 LEFT JOIN platform ON platform_item.__platform_id = platform.__id
                                 LEFT JOIN appliance_1c ON appliance.__id = appliance_1c.__voice_appliance_id
                                 LEFT JOIN inventory_item_1c ON appliance_1c.__inventory_item_id = inventory_item_1c.__id ),
                     phone_employee AS ( SELECT trim(regexp_split_to_table(work_phone, \',\')) AS dn, persons_code
                                         FROM view.lotus_employees
                                         WHERE work_phone IS NOT NULL AND work_phone NOT LIKE (\'\') ),
                     employee AS ( WITH positions AS ( WITH pos AS ( SELECT DISTINCT position FROM view.lotus_employees ORDER BY position )
                                                       SELECT position, row_number() OVER (ORDER BY position) AS __id
                                                       FROM pos )
                                   SELECT (surname || \' \' || name || \' \' || patronymic) AS "lotusUser",
                                          positions.__id AS "lotusUserPosition_id",
                                          employee.position AS "lotusUserPosition",
                                          division AS "lotusUserDivision",
                                          mobile_phone AS "lotusUserMobilePhone",
                                          work_email AS "lotusUserWorkEmail",
                                          persons_code
                                   FROM view.lotus_employees AS employee
                                   LEFT JOIN positions ON employee.position = positions.position
                                   WHERE work_phone IS NOT NULL AND work_phone NOT LIKE (\'\') )
                SELECT phone.dn::citext,
                       phone."alertingName",
                       (CASE WHEN (((date_part(\'epoch\'::TEXT, age(now(), appliance."lastUpdate")) / (3600)::DOUBLE PRECISION))::INTEGER < 73)
                                THEN \'да\' ELSE \'нет\' END)::citext AS "isActive",
                       appliance."lastUpdate",
                       appliance."serialNumber",
                       appliance.platform_id,
                       appliance.model,
                       appliance."ipAddress"::citext,
                       appliance.region_id,
                       appliance.region,
                       appliance.office_id,
                       appliance.office,
                       appliance."inventoryNumber",
                       appliance.mol,
                       appliance.inventory_user as "inventoryUser",
                       switch.platform AS "switchPlatform",
                       phone."cdpNeighborIP"::citext AS "switchIp",
                       phone."cdpNeighborPort" AS "switchPort",
                       switch."inventoryNumber" AS "switchInventoryNumber",
                       employee."lotusUser",
                       employee."lotusUserPosition_id",
                       employee."lotusUserPosition",
                       employee."lotusUserDivision",
                       employee."lotusUserMobilePhone",
                       employee."lotusUserWorkEmail"
                FROM phone
                LEFT JOIN appliance ON phone.__appliance_id = appliance.__id
                LEFT JOIN switch ON phone."cdpNeighborIP" = switch."ipAddress"
                LEFT JOIN phone_employee ON phone.dn = phone_employee.dn
                LEFT JOIN employee ON phone_employee.persons_code = employee.persons_code
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
        $sql['drop view.report_phone_by_lotus_user '] = 'DROP VIEW IF EXISTS view.report_phone_by_lotus_user';
        $sql['create old view.report_phone_by_lotus_user '] = '
            CREATE VIEW view.report_phone_by_lotus_user AS
                WITH
                    phone AS (SELECT __id, __appliance_id, model, (prefix || "phoneDN") AS dn, "alertingName", "cdpNeighborPort", "cdpNeighborIP" FROM equipment."phoneInfo"),

                    appliance AS (
                        WITH
                            appliance AS (
                                SELECT 
                                    __id, 
                                    __platform_item_id, 
                                    __location_id,
                                    "lastUpdate",
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
                            appliance."isActive",
                            appliance."lastUpdate"
                        FROM appliance
                        LEFT JOIN "platformItem" ON appliance.__platform_item_id = "platformItem".__id
                        LEFT JOIN "dataPort" ON "dataPort".__appliance_id = appliance.__id
                        LEFT JOIN office ON appliance.__location_id = office.__id
                        LEFT JOIN appliance1c ON appliance1c.__voice_appliance_id = appliance.__id
                    ),

                    switch AS (
                        SELECT
                            appliance.platform,
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
                    appliance.region_id,
                    appliance.region,
                    appliance.office_id,
                    appliance.office,
                    cast(appliance."isActive" AS citext),
                    appliance."lastUpdate",
                    appliance.platform_id,
                    phone.model,
                    cast(phone.dn AS citext),
                    phone."alertingName",
                    cast(host(appliance."ipAddress") AS citext) AS "ipAddress",
                    appliance."serialNumber",
                    appliance."inventoryNumber",
                    appliance.mol,
                    cast(appliance.inventory_user AS citext) AS "inventoryUser",
                    cast("phoneBook".name AS citext) AS "lotusUser",
                    "phoneBook".position_id "lotusUserPosition_id",
                    cast("phoneBook".position  AS citext) AS "lotusUserPosition",
                    cast("phoneBook".division  AS citext) AS "lotusUserDivision",
                    "phoneBook".mobile_phone AS "lotusUserMobilePhone",
                    "phoneBook".work_email AS "lotusUserWorkEmail",
                    cast(switch.platform AS citext) AS "switchPlatform",
                    cast(host(phone."cdpNeighborIP") AS citext) AS "switchIp",
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
