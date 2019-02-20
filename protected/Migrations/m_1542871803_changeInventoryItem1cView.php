<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542871803_changeInventoryItem1cView
    extends Migration
{

    public function up()
    {
        $sql['drop_old_view.inventory_item1c'] = 'DROP VIEW view.inventory_item1c';
        $sql['create_view.inventory_item1c'] = '
            CREATE OR REPLACE VIEW view.inventory_item1c AS
            SELECT
                inventoryItem1C."__id"                  AS "invItem_id",
                inventoryItem1C."inventoryNumber"       AS "invItem_inventoryNumber",
                inventoryItem1C."serialNumber"          AS "invItem_serialNumber",
                inventoryItem1C."dateOfRegistration"    AS "invItem_dateOfRegistration",
                inventoryItem1C."lastUpdate"            AS "invItem_lastUpdate",
                mol."__id"                              AS "mol_id",
                mol."fio"                               AS "mol_fio",
                mol."molTabNumber"                      AS "mol_tabNumber",
                nomenclature1C."__id"                   AS "nomenclature1C_id",
                nomenclature1C."title"                  AS "nomenclature1C_title",
                nomenclature1C."nomenclatureId"         AS "nomenclature1C_nomenclatureId",
                nomenclatureType."__id"                 AS "nomenclatureType_id",
                nomenclatureType."type"                 AS "nomenclatureType_type",
                category."__id"                         AS "invItemCategory_id",
                category."title"                        AS "invItemCategory_title",
                room1C."__id"                           AS "rooms1C_id",
                room1C."roomsCode"                      AS "rooms1C_roomsCode",
                room1C."address"                        AS "rooms1C_address",
                room1C."title"                          AS "rooms1C_title",
                office."__id"                           AS "office_id",
                office."lotusId"                        AS "office_lotusId"
            FROM "storage_1c"."inventoryItem1C" AS inventoryItem1C
            LEFT JOIN "storage_1c"."mols" AS mol ON mol.__id = inventoryItem1C.__mol_id
            LEFT JOIN "storage_1c"."nomenclature1C" AS nomenclature1C ON nomenclature1C.__id = inventoryItem1C.__nomenclature_id
            LEFT JOIN "storage_1c"."nomenclatureTypes" AS nomenclatureType ON nomenclatureType.__id = nomenclature1C.__type_id
            LEFT JOIN "storage_1c"."categories" AS category ON category.__id = inventoryItem1C.__category_id
            LEFT JOIN "storage_1c"."rooms1C" AS room1C ON room1C.__id = inventoryItem1C.__rooms_1c_id
            LEFT JOIN company."offices" AS office ON office.__id = room1C.__voice_office_id';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop_view.inventory_item1c'] = 'DROP VIEW IF EXISTS view.inventory_item1c';
        $sql['create_old_view.inventory_item1c'] = '
            CREATE OR REPLACE VIEW view.inventory_item1c AS
            SELECT
                inventoryItem1C."__id"                  AS "invItem_id",
                inventoryItem1C."inventoryNumber"       AS "invItem_inventoryNumber",
                inventoryItem1C."serialNumber"          AS "invItem_serialNumber",
                inventoryItem1C."dateOfRegistration"    AS "invItem_dateOfRegistration",
                inventoryItem1C."lastUpdate"            AS "invItem_lastUpdate",
                mol."__id"                              AS "mol_id",
                mol."fio"                               AS "mol_fio",
                mol."molTabNumber"                      AS "mol_tabNumber",
                nomenclature1C."__id"                   AS "nomenclature1C_id",
                nomenclature1C."title"                  AS "nomenclature1C_title",
                nomenclatureType."__id"                 AS "nomenclatureType_id",
                nomenclatureType."type"                 AS "nomenclatureType_type",
                category."__id"                         AS "invItemCategory_id",
                category."title"                        AS "invItemCategory_title",
                room1C."__id"                           AS "rooms1C_id",
                room1C."roomsCode"                      AS "rooms1C_roomsCode",
                room1C."address"                        AS "rooms1C_address",
                room1C."title"                          AS "rooms1C_title",
                office."__id"                           AS "office_id",
                office."lotusId"                        AS "office_lotusId"
            FROM "storage_1c"."inventoryItem1C" AS inventoryItem1C
            LEFT JOIN "storage_1c"."mols" AS mol ON mol.__id = inventoryItem1C.__mol_id
            LEFT JOIN "storage_1c"."nomenclature1C" AS nomenclature1C ON nomenclature1C.__id = inventoryItem1C.__nomenclature_id
            LEFT JOIN "storage_1c"."nomenclatureTypes" AS nomenclatureType ON nomenclatureType.__id = nomenclature1C.__type_id
            LEFT JOIN "storage_1c"."categories" AS category ON category.__id = inventoryItem1C.__category_id
            LEFT JOIN "storage_1c"."rooms1C" AS room1C ON room1C.__id = inventoryItem1C.__rooms_1c_id
            LEFT JOIN company."offices" AS office ON office.__id = room1C.__voice_office_id';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
