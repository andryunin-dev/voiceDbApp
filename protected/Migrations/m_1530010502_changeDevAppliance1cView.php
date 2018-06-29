<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1530010502_changeDevAppliance1cView
    extends Migration
{

    public function up()
    {
        $sql['drop_old_view.dev_appliance1c'] = 'DROP VIEW view.dev_appliance1c';
        $sql['create_view.dev_appliance1c'] = '
            CREATE OR REPLACE VIEW view.dev_appliance1c AS
            SELECT
                appliance1C."__id"                   AS "appliance1C_id",
                appliance."__id"                     AS "appliance_id",
                platformItem."serialNumber"          AS "appliance_serialNumber",
                inventoryItem1C."__id"               AS "invItem_id",
                inventoryItem1C."inventoryNumber"    AS "invItem_inventoryNumber",
                inventoryItem1C."serialNumber"       AS "invItem_serialNumber",
                inventoryItem1C."dateOfRegistration" AS "invItem_dateOfRegistration",
                inventoryItem1C."lastUpdate"         AS "invItem_lastUpdate",
                mol."__id"                           AS "mol_id",
                mol."fio"                            AS "mol_fio",
                mol."molTabNumber"                   AS "mol_tabNumber",
                nomenclature1C."__id"                AS "nomenclature1C_id",
                nomenclature1C."title"               AS "nomenclature1C_title",
                nomenclatureType."__id"              AS "nomenclatureType_id",
                nomenclatureType."type"              AS "nomenclatureType_type",
                category."__id"                      AS "invItemCategory_id",
                category."title"                     AS "invItemCategory_title",
                rooms1C."__id"                       AS "rooms1C_id",
                rooms1C."roomsCode"                  AS "rooms1C_roomsCode",
                rooms1C."address"                    AS "rooms1C_address",
                rooms1C."title"                      AS "rooms1C_title",
                office."__id"                        AS "office_id",
                office."lotusId"                     AS "office_lotusId"
            FROM "storage_1c"."appliances1C" AS appliance1C
            LEFT JOIN equipment."appliances" AS appliance ON appliance.__id = appliance1C.__voice_appliance_id
            LEFT JOIN equipment."platformItems" AS platformItem ON platformItem.__id = appliance.__platform_item_id
            LEFT JOIN "storage_1c"."inventoryItem1C" AS inventoryItem1C ON inventoryItem1C.__id = appliance1C.__inventory_item_id
            LEFT JOIN "storage_1c"."mols" AS mol ON mol.__id = inventoryItem1C.__mol_id
            LEFT JOIN "storage_1c"."nomenclature1C" AS nomenclature1C ON nomenclature1C.__id = inventoryItem1C.__nomenclature_id
            LEFT JOIN "storage_1c"."nomenclatureTypes" AS nomenclatureType ON nomenclatureType.__id = nomenclature1C.__type_id
            LEFT JOIN "storage_1c"."categories" AS category ON category.__id = inventoryItem1C.__category_id
            LEFT JOIN "storage_1c"."rooms1C" AS rooms1C ON rooms1C.__id = inventoryItem1C.__rooms_1c_id
            LEFT JOIN company."offices" AS office ON office.__id = rooms1C.__voice_office_id';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        //$this->setDb('phpUnitTest');
        //foreach ($sql as $key => $query) {
        //    if (true === $this->db->execute($query)) {
        //        echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
        //    }
        //}
    }

    public function down()
    {
        $sql['drop_view.dev_appliance1c'] = 'DROP VIEW view.dev_appliance1c';
        $sql['create_old_view.dev_appliance1c'] = '
            CREATE OR REPLACE VIEW view.dev_appliance1c AS
            SELECT
                appliance1C."__id"                   AS "appliance1C_id",
                appliance."__id"                     AS "appliance_id",
                platformItem."serialNumber"          AS "appliance_serialNumber",
                inventoryItem1C."__id"               AS "invItem_id",
                inventoryItem1C."inventoryNumber"    AS "invItem_inventoryNumber",
                inventoryItem1C."serialNumber"       AS "invItem_serialNumber",
                inventoryItem1C."dateOfRegistration" AS "invItem_dateOfRegistration",
                inventoryItem1C."lastUpdate"         AS "invItem_lastUpdate",
                mol."__id"                           AS "mol_id",
                mol."fio"                            AS "mol_fio",
                mol."molTabNumber"                   AS "mol_tabNumber",
                nomenclature1C."__id"                AS "nomenclature1C_id",
                nomenclature1C."title"               AS "nomenclature1C_title",
                nomenclatureType."__id"              AS "nomenclatureType_id",
                nomenclatureType."type"              AS "nomenclatureType_type",
                category."__id"                      AS "invItemCategory_id",
                category."title"                     AS "invItemCategory_title",
                rooms1C."__id"                       AS "rooms1C_id",
                rooms1C."roomsCode"                  AS "rooms1C_roomsCode",
                rooms1C."address"                    AS "rooms1C_address",
                rooms1C."title"                      AS "rooms1C_title",
                office."__id"                        AS "office_id",
                office."lotusId"                     AS "office_lotusId",
                roomsType."__id"                     AS "roomsTypes_id",
                roomsType."type"                     AS "roomsTypes_type",
                cities1C."__id"                      AS "city1C_id",
                cities1C."title"                     AS "city1C_title",
                regions1C."__id"                     AS "region1C_id",
                regions1C."title"                    AS "region1C_title"
            FROM "storage_1c"."appliances1C" AS appliance1C
            LEFT JOIN equipment."appliances" AS appliance ON appliance.__id = appliance1C.__voice_appliance_id
            LEFT JOIN equipment."platformItems" AS platformItem ON platformItem.__id = appliance.__platform_item_id
            LEFT JOIN "storage_1c"."inventoryItem1C" AS inventoryItem1C ON inventoryItem1C.__id = appliance1C.__inventory_item_id
            LEFT JOIN "storage_1c"."mols" AS mol ON mol.__id = inventoryItem1C.__mol_id
            LEFT JOIN "storage_1c"."nomenclature1C" AS nomenclature1C ON nomenclature1C.__id = inventoryItem1C.__nomenclature_id
            LEFT JOIN "storage_1c"."nomenclatureTypes" AS nomenclatureType ON nomenclatureType.__id = nomenclature1C.__type_id
            LEFT JOIN "storage_1c"."categories" AS category ON category.__id = inventoryItem1C.__category_id
            LEFT JOIN "storage_1c"."rooms1C" AS rooms1C ON rooms1C.__id = inventoryItem1C.__rooms_1c_id
            LEFT JOIN company."offices" AS office ON office.__id = rooms1C.__voice_office_id
            LEFT JOIN "storage_1c"."roomsTypes" AS roomsType ON roomsType.__id = rooms1C.__type_id
            LEFT JOIN "storage_1c"."cities1C" AS cities1C ON cities1C.__id = rooms1C.__city_1c_id
            LEFT JOIN "storage_1c"."regions1C" AS regions1C ON regions1C.__id = cities1C.__region_1c_id';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        //$this->setDb('phpUnitTest');
        //foreach ($sql as $key => $query) {
        //    if (true === $this->db->execute($query)) {
        //        echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
        //    }
        //}
    }
    
}
