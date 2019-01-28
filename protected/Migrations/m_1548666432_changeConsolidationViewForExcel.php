<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1548666432_changeConsolidationViewForExcel
    extends Migration
{
    public function up()
    {
        $sql['drop view'] = 'DROP VIEW IF EXISTS view.consolidation_excel_table_src';
        $sql['create table'] = '
        CREATE VIEW view.consolidation_excel_table_src AS
WITH
     "lotusUsers" AS (
    SELECT t.name "userName",
           t.surname "userSurname",
           t.patronymic "userPatronimic",
           t.work_phone "userWorkPhone",
           t.work_email "userEmail",
           t.persons_code "userTabNumber"
    FROM view.lotus_db_phone_book t
    ),
     "nomenclatureMap" AS (
    SELECT t.nomenclature_id "nomenclatureId",
           t.nomenclature map_nomenclature,
           t.platform platform_1c,
           t."listNumber" listNumber_1c
        --             t.platform_id map_platform_id,
        --             t.platform map_platform
    FROM mapping.nomenclature t
    ),
     "invUser" AS (
    SELECT t.inventory_number "invNumber",
           t.type_of_nomenclature "nomenclatureType_1c",
           t.inventory_user "invUserFio",
           CASE WHEN t.inventory_user_tab_number = \'\' THEN null ELSE t.inventory_user_tab_number::bigint END "invUserTabNumber",
           lus."userEmail" "userEmail"
    FROM storage_1c.foreign_1c t
     LEFT JOIN "lotusUsers" lus ON CASE WHEN t.inventory_user_tab_number = \'\' THEN null ELSE t.inventory_user_tab_number::bigint END = lus."userTabNumber"
    ),

     "invItem" AS (
    SELECT "invItems".__id "invItem_id",
           "invItems"."inventoryNumber"::bigint "invNumber",
           "invItems"."serialNumber" "serialNumber_1c",
           "invItems"."dateOfRegistration" "registartionDate_1c",
           "invItems"."lastUpdate" "lastUpdate_1c",
           categories.title "category_1c",
           nom.title "nomenclature_1c",
           nom."nomenclatureId" "nomenclatureId",
           nom_types.type "nomenclatureType_1c",
           rooms."roomsCode" "roomCode_1c",
           rooms.title "room_1c",
           rooms.address "address_1c",
           mols.fio "molFio_1c",
           mols."molTabNumber"::bigint "molTabNumber_1c",
           lus."userEmail" "molEmail",
           nomMap.listNumber_1c "listNumber_1c",
           nomMap.map_nomenclature "map_nomenclature",
           nomMap.platform_1c "platform_1c",
           "invUser"."invUserFio",
           "invUser"."invUserTabNumber",
           "invUser"."userEmail"
    FROM storage_1c."inventoryItem1C" "invItems"
           JOIN storage_1c.categories categories ON "invItems".__category_id = categories.__id
           JOIN storage_1c."nomenclature1C" nom ON "invItems".__nomenclature_id = nom.__id
           JOIN storage_1c."nomenclatureTypes" nom_types ON nom.__type_id = nom_types.__id
           LEFT JOIN storage_1c."rooms1C" rooms ON "invItems".__rooms_1c_id = rooms.__id
           LEFT JOIN storage_1c.mols mols ON "invItems".__mol_id = mols.__id
           LEFT JOIN "lotusUsers" lus ON lus."userTabNumber" = mols."molTabNumber"
           LEFT JOIN "invUser" ON "invItems"."inventoryNumber" = "invUser"."invNumber" AND nom_types.type = "invUser"."nomenclatureType_1c"
           JOIN "nomenclatureMap" nomMap ON nom."nomenclatureId" = nomMap."nomenclatureId"
    ),
     managementPorts AS (
    SELECT array_to_string(array_agg("ipAddress"), \',\') "managementIP", __appliance_id "dev_id" FROM equipment."dataPorts" WHERE "isManagement" = TRUE
    GROUP BY dev_id
    ),
     voiceNomenclatureMap AS (
    SELECT platform_id vmap_platform_id,
           platform platform_voice,
           "listNumber" "listNumber_voice"
    FROM mapping.nomenclature
    GROUP BY platform_id, platform, "listNumber"
    ),
     voiceDbDevs AS (
    SELECT
           devs.__id "dev_id",
           devs.comment "comment",
           plitems."serialNumber" "serialNumber",
           pl.__id "platform_id",
           ven.title "vendor",
           pl.title "platform",
           ven.title || \' \' || pl.title "vendor_platform",
           dev_types.type "type",
           dev_types.__id type_id,
           devs.details->>\'hostname\'::citext "hostname",
           offices."lotusId" "lotusId_voice",
           ((date_part(\'epoch\' :: text, age(now(), devs."lastUpdate")) /
             (3600) :: double precision)) :: integer "dev_age",
           mports."managementIP",
           vnomMap."listNumber_voice" "listNumber_voice",
           vnomMap.platform_voice "platform_voice"
    FROM
         equipment.appliances devs
           JOIN equipment."platformItems" plitems ON devs.__platform_item_id = plitems.__id
           JOIN equipment.platforms pl ON plitems.__platform_id = pl.__id
           JOIN equipment.vendors ven ON devs.__vendor_id = ven.__id
           JOIN equipment."applianceTypes" dev_types ON devs.__type_id = dev_types.__id
           LEFT JOIN company.offices offices ON devs.__location_id = offices.__id
           LEFT JOIN managementPorts mports ON devs.__id = mports.dev_id
           JOIN voiceNomenclatureMap vnomMap ON pl.__id = vnomMap.vmap_platform_id
           LEFT JOIN mapping.exceptions exceptions ON plitems."serialNumber" = exceptions.serial
    WHERE exceptions.serial ISNULL
    ),
     linkOneC_to_voice AS (
    SELECT t.__voice_appliance_id "dev_id",
           t.__inventory_item_id "invItem_id"
    FROM storage_1c."appliances1C" t
    ),
     phoneSwitches AS (
    SELECT dp.__appliance_id gw_dev_id,
           dp."ipAddress" portIp,
           devs.platform gw_platform,
           "invItem"."invNumber" "gw_invNumber"
    FROM equipment."dataPorts" dp
           LEFT JOIN voiceDbDevs devs ON dp.__appliance_id = devs.dev_id
           LEFT JOIN linkOneC_to_voice linkTo1c USING (dev_id)
           LEFT JOIN "invItem" USING ("invItem_id")
    ),
     phoneInfo AS (
    SELECT
           __appliance_id dev_id,
           "alertingName",
           "cdpNeighborPort" "cdpPort",
           "cdpNeighborDeviceId",
           "cdpNeighborIP" "cdpIp",
           prefix || "phoneDN" "fullDN"
    FROM equipment."phoneInfo" phi
    ),
    -- flatCode <-> lotusId
     locationMap_1c AS (
    SELECT t.lotus_id "lotusId_1c",
           t."flatCode" "roomCode_1c"
    FROM mapping."location1C_to_lotusId" t
    )

SELECT *
FROM "invItem"
       LEFT JOIN locationMap_1c USING ("roomCode_1c")
       LEFT JOIN linkOneC_to_voice link USING ("invItem_id")
         FULL JOIN voiceDbDevs USING (dev_id)
       LEFT JOIN phoneInfo pi USING (dev_id)
       LEFT JOIN phoneSwitches psw ON pi."cdpIp" = psw.portIp
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
        $sql['drop view'] = 'DROP VIEW IF EXISTS view.consolidation_excel_table_src';
        $sql['create table'] = '
        CREATE VIEW view.consolidation_excel_table_src AS
WITH
    "nomenclatureMap" AS (
      SELECT t.nomenclature_id "nomenclatureId",
             t.nomenclature map_nomenclature,
             t.platform platform_1c,
             t."listNumber" listNumber_1c
          --             t.platform_id map_platform_id,
          --             t.platform map_platform
      FROM mapping.nomenclature t
    ),
    "invUser" AS (
    SELECT t.inventory_number "invNumber",
           t.type_of_nomenclature "nomenclatureType_1c",
           t.inventory_user "invUserFio",
           t.inventory_user_tab_number "invUserTabNumber"
    FROM storage_1c.foreign_1c t
    ),

    "invItem" AS (
    SELECT "invItems".__id "invItem_id",
           "invItems"."inventoryNumber"::bigint "invNumber",
           "invItems"."serialNumber" "serialNumber_1c",
           "invItems"."dateOfRegistration" "registartionDate_1c",
           "invItems"."lastUpdate" "lastUpdate_1c",
           categories.title "category_1c",
           nom.title "nomenclature_1c",
           nom."nomenclatureId" "nomenclatureId",
           nom_types.type "nomenclatureType_1c",
           rooms."roomsCode" "roomCode_1c",
           rooms.title "room_1c",
           rooms.address "address_1c",
           mols.fio "molFio_1c",
           mols."molTabNumber" "molTabNumber_1c",
           nomMap.listNumber_1c "listNumber_1c",
           nomMap.map_nomenclature "map_nomenclature",
           nomMap.platform_1c "platform_1c",
           "invUser"."invUserFio",
           "invUser"."invUserTabNumber"
    FROM storage_1c."inventoryItem1C" "invItems"
           JOIN storage_1c.categories categories ON "invItems".__category_id = categories.__id
           JOIN storage_1c."nomenclature1C" nom ON "invItems".__nomenclature_id = nom.__id
           JOIN storage_1c."nomenclatureTypes" nom_types ON nom.__type_id = nom_types.__id
           LEFT JOIN storage_1c."rooms1C" rooms ON "invItems".__rooms_1c_id = rooms.__id
           LEFT JOIN storage_1c.mols mols ON "invItems".__mol_id = mols.__id
           LEFT JOIN "invUser" ON "invItems"."inventoryNumber" = "invUser"."invNumber" AND nom_types.type = "invUser"."nomenclatureType_1c"
           JOIN "nomenclatureMap" nomMap ON nom."nomenclatureId" = nomMap."nomenclatureId"
    ),
    managementPorts AS (
      SELECT array_to_string(array_agg("ipAddress"), \',\') "managementIP", __appliance_id "dev_id" FROM equipment."dataPorts" WHERE "isManagement" = TRUE
      GROUP BY dev_id
    ),
    voiceNomenclatureMap AS (
      SELECT platform_id vmap_platform_id,
             platform platform_voice,
             "listNumber" "listNumber_voice"
      FROM mapping.nomenclature
      GROUP BY platform_id, platform, "listNumber"
    ),
    voiceDbDevs AS (
     SELECT
            devs.__id "dev_id",
            devs.comment "comment",
            plitems."serialNumber" "serialNumber",
            pl.__id "platform_id",
            ven.title "vendor",
            pl.title "platform",
            ven.title || \' \' || pl.title "vendor_platform",
            dev_types.type "type",
            dev_types.__id type_id,
            devs.details->>\'hostname\'::citext "hostname",
            offices."lotusId" "lotusId_voice",
            ((date_part(\'epoch\' :: text, age(now(), devs."lastUpdate")) /
              (3600) :: double precision)) :: integer "dev_age",
            mports."managementIP",
            vnomMap."listNumber_voice" "listNumber_voice",
            vnomMap.platform_voice "platform_voice"
     FROM
          equipment.appliances devs
     JOIN equipment."platformItems" plitems ON devs.__platform_item_id = plitems.__id
     JOIN equipment.platforms pl ON plitems.__platform_id = pl.__id
     JOIN equipment.vendors ven ON devs.__vendor_id = ven.__id
     JOIN equipment."applianceTypes" dev_types ON devs.__type_id = dev_types.__id
     LEFT JOIN company.offices offices ON devs.__location_id = offices.__id
     LEFT JOIN managementPorts mports ON devs.__id = mports.dev_id
     JOIN voiceNomenclatureMap vnomMap ON pl.__id = vnomMap.vmap_platform_id
     LEFT JOIN mapping.exceptions exceptions ON plitems."serialNumber" = exceptions.serial
     WHERE exceptions.serial ISNULL
    ),
    linkOneC_to_voice AS (
      SELECT t.__voice_appliance_id "dev_id",
             t.__inventory_item_id "invItem_id"
      FROM storage_1c."appliances1C" t
    ),
    phoneSwitches AS (
    SELECT dp.__appliance_id gw_dev_id,
           dp."ipAddress" portIp,
           devs.platform gw_platform,
           "invItem"."invNumber" "gw_invNumber"
    FROM equipment."dataPorts" dp
    LEFT JOIN voiceDbDevs devs ON dp.__appliance_id = devs.dev_id
    LEFT JOIN linkOneC_to_voice linkTo1c USING (dev_id)
    LEFT JOIN "invItem" USING ("invItem_id")
    ),
    phoneInfo AS (
      SELECT
        __appliance_id dev_id,
        "alertingName",
        "cdpNeighborPort" "cdpPort",
        "cdpNeighborDeviceId",
        "cdpNeighborIP" "cdpIp",
        prefix || "phoneDN" "fullDN"
      FROM equipment."phoneInfo" phi
    ),
    -- flatCode <-> lotusId
    locationMap_1c AS (
         SELECT t.lotus_id "lotusId_1c",
                t."flatCode" "roomCode_1c"
         FROM mapping."location1C_to_lotusId" t
        )

SELECT *
FROM "invItem"
LEFT JOIN locationMap_1c USING ("roomCode_1c")
LEFT JOIN linkOneC_to_voice link USING ("invItem_id")
FULL JOIN voiceDbDevs USING (dev_id)
LEFT JOIN phoneInfo pi USING (dev_id)
LEFT JOIN phoneSwitches psw ON pi."cdpIp" = psw.portIp
        ';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}