<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1567061172_changePhoneSourceView
    extends Migration
{

    public function up()
    {
        $sql['drop old view'] = '
            DROP VIEW IF EXISTS view.consolidation_excel_table_src
        ';
        $sql['change view'] = '
        create view view.consolidation_excel_table_src as
  WITH "lotusUsers" AS (
      SELECT t.name         AS "userName",
             t.surname      AS "userSurname",
             t.patronymic   AS "userPatronimic",
             t.work_phone   AS "userWorkPhone",
             t.work_email   AS "userEmail",
             t.persons_code AS "userTabNumber"
      FROM view.lotus_db_phone_book t
  ), "nomenclatureMap" AS (
      SELECT t.nomenclature_id AS "nomenclatureId",
             t.nomenclature    AS map_nomenclature,
             t.platform        AS platform_1c,
             t."listNumber"    AS listnumber_1c
      FROM mapping.nomenclature t
  ), "invUser" AS (
      SELECT t.inventory_number     AS "invNumber",
             t.type_of_nomenclature AS "nomenclatureType_1c",
             t.inventory_user       AS "invUserFio",
             CASE
               WHEN (t.inventory_user_tab_number = \'\' :: citext) THEN NULL :: bigint
               ELSE (t.inventory_user_tab_number) :: bigint
                 END                AS "invUserTabNumber",
             lus."userEmail"
      FROM (storage_1c.foreign_1c t
          LEFT JOIN "lotusUsers" lus ON ((
        CASE
        WHEN (t.inventory_user_tab_number = \'\' :: citext)
          THEN NULL :: bigint
        ELSE (t.inventory_user_tab_number) :: bigint
        END = lus."userTabNumber")))
  ), "invItem" AS (
      SELECT "invItems".__id                          AS "invItem_id",
             ("invItems"."inventoryNumber") :: bigint AS "invNumber",
             "invItems"."serialNumber"                AS "serialNumber_1c",
             "invItems"."dateOfRegistration"          AS "registartionDate_1c",
             "invItems"."lastUpdate"                  AS "lastUpdate_1c",
             categories.title                         AS category_1c,
             nom.title                                AS nomenclature_1c,
             nom."nomenclatureId",
             nom_types.type                           AS "nomenclatureType_1c",
             rooms."roomsCode"                        AS "roomCode_1c",
             rooms.title                              AS room_1c,
             rooms.address                            AS address_1c,
             mols.fio                                 AS "molFio_1c",
             (mols."molTabNumber") :: bigint          AS "molTabNumber_1c",
             lus."userEmail"                          AS "molEmail",
             nommap.listnumber_1c                     AS "listNumber_1c",
             nommap.map_nomenclature,
             nommap.platform_1c,
             "invUser"."invUserFio",
             "invUser"."invUserTabNumber",
             "invUser"."userEmail"
      FROM ((((((((storage_1c."inventoryItem1C" "invItems"
          JOIN storage_1c.categories categories ON (("invItems".__category_id = categories.__id)))
          JOIN storage_1c."nomenclature1C" nom ON (("invItems".__nomenclature_id = nom.__id)))
          JOIN storage_1c."nomenclatureTypes" nom_types ON ((nom.__type_id = nom_types.__id)))
          LEFT JOIN storage_1c."rooms1C" rooms ON (("invItems".__rooms_1c_id = rooms.__id)))
          LEFT JOIN storage_1c.mols mols ON (("invItems".__mol_id = mols.__id)))
          LEFT JOIN "lotusUsers" lus ON ((lus."userTabNumber" = mols."molTabNumber")))
          LEFT JOIN "invUser" ON ((("invItems"."inventoryNumber" = "invUser"."invNumber") AND
                                   (nom_types.type = "invUser"."nomenclatureType_1c"))))
          JOIN "nomenclatureMap" nommap ON ((nom."nomenclatureId" = nommap."nomenclatureId")))
  ), managementports AS (
      SELECT array_to_string(array_agg("dataPorts"."ipAddress"), \',\' :: text) AS "managementIP",
             "dataPorts".__appliance_id                                       AS dev_id
      FROM equipment."dataPorts"
      WHERE ("dataPorts"."isManagement" = true)
      GROUP BY "dataPorts".__appliance_id
  ), voicenomenclaturemap AS (
      SELECT nomenclature.platform_id  AS vmap_platform_id,
             nomenclature.platform     AS platform_voice,
             nomenclature."listNumber" AS "listNumber_voice"
      FROM mapping.nomenclature
      GROUP BY nomenclature.platform_id, nomenclature.platform, nomenclature."listNumber"
  ), voicedbdevs AS (
      SELECT devs.__id                                                                                             AS dev_id,
             devs.comment,
             plitems."serialNumber",
             pl.__id                                                                                               AS platform_id,
             ven.title                                                                                             AS vendor,
             pl.title                                                                                              AS platform,
             (((ven.title) :: text || \' \' :: text) || (pl.title) :: text)                                          AS vendor_platform,
             dev_types.type,
             dev_types.__id                                                                                        AS type_id,
             (devs.details ->> (\'hostname\' :: citext) :: text)                                                     AS hostname,
             offices."lotusId"                                                                                     AS "lotusId_voice",
             ((date_part(\'epoch\' :: text, age(now(), devs."lastUpdate")) /
               (3600) :: double precision)) :: integer                                                             AS dev_age,
             mports."managementIP",
             vnommap."listNumber_voice",
             vnommap.platform_voice,
             devs."lastUpdate"      AS "dev_lasUpdate"
      FROM ((((((((equipment.appliances devs
          JOIN equipment."platformItems" plitems ON ((devs.__platform_item_id = plitems.__id)))
          JOIN equipment.platforms pl ON ((plitems.__platform_id = pl.__id)))
          JOIN equipment.vendors ven ON ((devs.__vendor_id = ven.__id)))
          JOIN equipment."applianceTypes" dev_types ON ((devs.__type_id = dev_types.__id)))
          LEFT JOIN company.offices offices ON ((devs.__location_id = offices.__id)))
          LEFT JOIN managementports mports ON ((devs.__id = mports.dev_id)))
          JOIN voicenomenclaturemap vnommap ON ((pl.__id = vnommap.vmap_platform_id)))
          LEFT JOIN mapping.exceptions exceptions ON ((plitems."serialNumber" = exceptions.serial)))
      WHERE (exceptions.serial IS NULL)
  ), linkonec_to_voice AS (
      SELECT t.__voice_appliance_id AS dev_id, t.__inventory_item_id AS "invItem_id"
      FROM storage_1c."appliances1C" t
  ), phoneswitches AS (
      SELECT dp.__appliance_id       AS gw_dev_id,
             dp."ipAddress"          AS portip,
             devs.platform           AS gw_platform,
             "invItem_1"."invNumber" AS "gw_invNumber"
      FROM (((equipment."dataPorts" dp
          LEFT JOIN voicedbdevs devs ON ((dp.__appliance_id = devs.dev_id)))
          LEFT JOIN linkonec_to_voice linkto1c USING (dev_id))
          LEFT JOIN "invItem" "invItem_1" USING ("invItem_id"))
  ), phoneinfo AS (
      SELECT phi.__appliance_id                                AS dev_id,
             phi."alertingName",
             phi."cdpNeighborPort"                             AS "cdpPort",
             phi."cdpNeighborDeviceId",
             phi."cdpNeighborIP"                               AS "cdpIp",
             ((phi.prefix) :: text || (phi."phoneDN") :: text) AS "fullDN"
      FROM equipment."phoneInfo" phi
  ), locationmap_1c AS (
      SELECT t.lotus_id AS "lotusId_1c", t."flatCode" AS "roomCode_1c"
      FROM mapping."location1C_to_lotusId" t
  ), one_c_info AS (
      SELECT foreign_1c.inventory_number     AS inv_number,
             foreign_1c.type_of_nomenclature AS nomenclature_type,
             foreign_1c.status,
             foreign_1c.comment
      FROM storage_1c.foreign_1c
  )
  SELECT dev_id,
         "invItem_id",
         "invItem"."roomCode_1c",
         "invItem"."invNumber",
         "invItem"."serialNumber_1c",
         "invItem"."registartionDate_1c",
         "invItem"."lastUpdate_1c",
         "invItem".category_1c,
         "invItem".nomenclature_1c,
         "invItem"."nomenclatureId",
         "invItem"."nomenclatureType_1c",
         "invItem".room_1c,
         "invItem".address_1c,
         "invItem"."molFio_1c",
         "invItem"."molTabNumber_1c",
         "invItem"."molEmail",
         "invItem"."listNumber_1c",
         "invItem".map_nomenclature,
         "invItem".platform_1c,
         "invItem"."invUserFio",
         "invItem"."invUserTabNumber",
         "invItem"."userEmail",
         locationmap_1c."lotusId_1c",
         voicedbdevs.comment,
         voicedbdevs."serialNumber",
         voicedbdevs.platform_id,
         voicedbdevs.vendor,
         voicedbdevs.platform,
         voicedbdevs.vendor_platform,
         voicedbdevs.type,
         voicedbdevs.type_id,
         voicedbdevs.hostname,
         voicedbdevs."lotusId_voice",
         voicedbdevs.dev_age,
         voicedbdevs."managementIP",
         voicedbdevs."listNumber_voice",
         voicedbdevs.platform_voice,
         voicedbdevs."dev_lasUpdate",
         pi."alertingName",
         pi."cdpPort",
         pi."cdpNeighborDeviceId",
         pi."cdpIp",
         pi."fullDN",
         psw.gw_dev_id,
         psw.portip,
         psw.gw_platform,
         psw."gw_invNumber",
         one_c_info.status  AS status_1c,
         one_c_info.comment AS comment_1c
  FROM (((((("invItem"
      LEFT JOIN locationmap_1c USING ("roomCode_1c"))
      LEFT JOIN linkonec_to_voice link USING ("invItem_id"))
      FULL JOIN voicedbdevs USING (dev_id))
      LEFT JOIN phoneinfo pi USING (dev_id))
      LEFT JOIN phoneswitches psw ON ((pi."cdpIp" = psw.portip)))
      LEFT JOIN one_c_info ON (((one_c_info.inv_number = ("invItem"."invNumber") :: citext) AND
                                (one_c_info.nomenclature_type = "invItem"."nomenclatureType_1c"))))
        ';
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop old view'] = '
            DROP VIEW IF EXISTS view.consolidation_excel_table_src
        ';
        $sql['restore previous version'] = '
create view view.consolidation_excel_table_src as
  WITH "lotusUsers" AS (
      SELECT t.name         AS "userName",
             t.surname      AS "userSurname",
             t.patronymic   AS "userPatronimic",
             t.work_phone   AS "userWorkPhone",
             t.work_email   AS "userEmail",
             t.persons_code AS "userTabNumber"
      FROM view.lotus_db_phone_book t
  ), "nomenclatureMap" AS (
      SELECT t.nomenclature_id AS "nomenclatureId",
             t.nomenclature    AS map_nomenclature,
             t.platform        AS platform_1c,
             t."listNumber"    AS listnumber_1c
      FROM mapping.nomenclature t
  ), "invUser" AS (
      SELECT t.inventory_number     AS "invNumber",
             t.type_of_nomenclature AS "nomenclatureType_1c",
             t.inventory_user       AS "invUserFio",
             CASE
               WHEN (t.inventory_user_tab_number = \'\' :: citext) THEN NULL :: bigint
               ELSE (t.inventory_user_tab_number) :: bigint
                 END                AS "invUserTabNumber",
             lus."userEmail"
      FROM (storage_1c.foreign_1c t
          LEFT JOIN "lotusUsers" lus ON ((
        CASE
        WHEN (t.inventory_user_tab_number = \'\' :: citext)
          THEN NULL :: bigint
        ELSE (t.inventory_user_tab_number) :: bigint
        END = lus."userTabNumber")))
  ), "invItem" AS (
      SELECT "invItems".__id                          AS "invItem_id",
             ("invItems"."inventoryNumber") :: bigint AS "invNumber",
             "invItems"."serialNumber"                AS "serialNumber_1c",
             "invItems"."dateOfRegistration"          AS "registartionDate_1c",
             "invItems"."lastUpdate"                  AS "lastUpdate_1c",
             categories.title                         AS category_1c,
             nom.title                                AS nomenclature_1c,
             nom."nomenclatureId",
             nom_types.type                           AS "nomenclatureType_1c",
             rooms."roomsCode"                        AS "roomCode_1c",
             rooms.title                              AS room_1c,
             rooms.address                            AS address_1c,
             mols.fio                                 AS "molFio_1c",
             (mols."molTabNumber") :: bigint          AS "molTabNumber_1c",
             lus."userEmail"                          AS "molEmail",
             nommap.listnumber_1c                     AS "listNumber_1c",
             nommap.map_nomenclature,
             nommap.platform_1c,
             "invUser"."invUserFio",
             "invUser"."invUserTabNumber",
             "invUser"."userEmail"
      FROM ((((((((storage_1c."inventoryItem1C" "invItems"
          JOIN storage_1c.categories categories ON (("invItems".__category_id = categories.__id)))
          JOIN storage_1c."nomenclature1C" nom ON (("invItems".__nomenclature_id = nom.__id)))
          JOIN storage_1c."nomenclatureTypes" nom_types ON ((nom.__type_id = nom_types.__id)))
          LEFT JOIN storage_1c."rooms1C" rooms ON (("invItems".__rooms_1c_id = rooms.__id)))
          LEFT JOIN storage_1c.mols mols ON (("invItems".__mol_id = mols.__id)))
          LEFT JOIN "lotusUsers" lus ON ((lus."userTabNumber" = mols."molTabNumber")))
          LEFT JOIN "invUser" ON ((("invItems"."inventoryNumber" = "invUser"."invNumber") AND
                                   (nom_types.type = "invUser"."nomenclatureType_1c"))))
          JOIN "nomenclatureMap" nommap ON ((nom."nomenclatureId" = nommap."nomenclatureId")))
  ), managementports AS (
      SELECT array_to_string(array_agg("dataPorts"."ipAddress"), \',\' :: text) AS "managementIP",
             "dataPorts".__appliance_id                                       AS dev_id
      FROM equipment."dataPorts"
      WHERE ("dataPorts"."isManagement" = true)
      GROUP BY "dataPorts".__appliance_id
  ), voicenomenclaturemap AS (
      SELECT nomenclature.platform_id  AS vmap_platform_id,
             nomenclature.platform     AS platform_voice,
             nomenclature."listNumber" AS "listNumber_voice"
      FROM mapping.nomenclature
      GROUP BY nomenclature.platform_id, nomenclature.platform, nomenclature."listNumber"
  ), voicedbdevs AS (
      SELECT devs.__id                                                                                             AS dev_id,
             devs.comment,
             plitems."serialNumber",
             pl.__id                                                                                               AS platform_id,
             ven.title                                                                                             AS vendor,
             pl.title                                                                                              AS platform,
             (((ven.title) :: text || \' \' :: text) || (pl.title) :: text)                                          AS vendor_platform,
             dev_types.type,
             dev_types.__id                                                                                        AS type_id,
             (devs.details ->> (\'hostname\' :: citext) :: text)                                                     AS hostname,
             offices."lotusId"                                                                                     AS "lotusId_voice",
             ((date_part(\'epoch\' :: text, age(now(), devs."lastUpdate")) /
               (3600) :: double precision)) :: integer                                                             AS dev_age,
             mports."managementIP",
             vnommap."listNumber_voice",
             vnommap.platform_voice
      FROM ((((((((equipment.appliances devs
          JOIN equipment."platformItems" plitems ON ((devs.__platform_item_id = plitems.__id)))
          JOIN equipment.platforms pl ON ((plitems.__platform_id = pl.__id)))
          JOIN equipment.vendors ven ON ((devs.__vendor_id = ven.__id)))
          JOIN equipment."applianceTypes" dev_types ON ((devs.__type_id = dev_types.__id)))
          LEFT JOIN company.offices offices ON ((devs.__location_id = offices.__id)))
          LEFT JOIN managementports mports ON ((devs.__id = mports.dev_id)))
          JOIN voicenomenclaturemap vnommap ON ((pl.__id = vnommap.vmap_platform_id)))
          LEFT JOIN mapping.exceptions exceptions ON ((plitems."serialNumber" = exceptions.serial)))
      WHERE (exceptions.serial IS NULL)
  ), linkonec_to_voice AS (
      SELECT t.__voice_appliance_id AS dev_id, t.__inventory_item_id AS "invItem_id"
      FROM storage_1c."appliances1C" t
  ), phoneswitches AS (
      SELECT dp.__appliance_id       AS gw_dev_id,
             dp."ipAddress"          AS portip,
             devs.platform           AS gw_platform,
             "invItem_1"."invNumber" AS "gw_invNumber"
      FROM (((equipment."dataPorts" dp
          LEFT JOIN voicedbdevs devs ON ((dp.__appliance_id = devs.dev_id)))
          LEFT JOIN linkonec_to_voice linkto1c USING (dev_id))
          LEFT JOIN "invItem" "invItem_1" USING ("invItem_id"))
  ), phoneinfo AS (
      SELECT phi.__appliance_id                                AS dev_id,
             phi."alertingName",
             phi."cdpNeighborPort"                             AS "cdpPort",
             phi."cdpNeighborDeviceId",
             phi."cdpNeighborIP"                               AS "cdpIp",
             ((phi.prefix) :: text || (phi."phoneDN") :: text) AS "fullDN"
      FROM equipment."phoneInfo" phi
  ), locationmap_1c AS (
      SELECT t.lotus_id AS "lotusId_1c", t."flatCode" AS "roomCode_1c"
      FROM mapping."location1C_to_lotusId" t
  ), one_c_info AS (
      SELECT foreign_1c.inventory_number     AS inv_number,
             foreign_1c.type_of_nomenclature AS nomenclature_type,
             foreign_1c.status,
             foreign_1c.comment
      FROM storage_1c.foreign_1c
  )
  SELECT dev_id,
         "invItem_id",
         "invItem"."roomCode_1c",
         "invItem"."invNumber",
         "invItem"."serialNumber_1c",
         "invItem"."registartionDate_1c",
         "invItem"."lastUpdate_1c",
         "invItem".category_1c,
         "invItem".nomenclature_1c,
         "invItem"."nomenclatureId",
         "invItem"."nomenclatureType_1c",
         "invItem".room_1c,
         "invItem".address_1c,
         "invItem"."molFio_1c",
         "invItem"."molTabNumber_1c",
         "invItem"."molEmail",
         "invItem"."listNumber_1c",
         "invItem".map_nomenclature,
         "invItem".platform_1c,
         "invItem"."invUserFio",
         "invItem"."invUserTabNumber",
         "invItem"."userEmail",
         locationmap_1c."lotusId_1c",
         voicedbdevs.comment,
         voicedbdevs."serialNumber",
         voicedbdevs.platform_id,
         voicedbdevs.vendor,
         voicedbdevs.platform,
         voicedbdevs.vendor_platform,
         voicedbdevs.type,
         voicedbdevs.type_id,
         voicedbdevs.hostname,
         voicedbdevs."lotusId_voice",
         voicedbdevs.dev_age,
         voicedbdevs."managementIP",
         voicedbdevs."listNumber_voice",
         voicedbdevs.platform_voice,
         pi."alertingName",
         pi."cdpPort",
         pi."cdpNeighborDeviceId",
         pi."cdpIp",
         pi."fullDN",
         psw.gw_dev_id,
         psw.portip,
         psw.gw_platform,
         psw."gw_invNumber",
         one_c_info.status  AS status_1c,
         one_c_info.comment AS comment_1c
  FROM (((((("invItem"
      LEFT JOIN locationmap_1c USING ("roomCode_1c"))
      LEFT JOIN linkonec_to_voice link USING ("invItem_id"))
      FULL JOIN voicedbdevs USING (dev_id))
      LEFT JOIN phoneinfo pi USING (dev_id))
      LEFT JOIN phoneswitches psw ON ((pi."cdpIp" = psw.portip)))
      LEFT JOIN one_c_info ON (((one_c_info.inv_number = ("invItem"."invNumber") :: citext) AND
                                (one_c_info.nomenclature_type = "invItem"."nomenclatureType_1c"))))
        ';
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}