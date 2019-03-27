<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1553686561_changeDevModuleProtGeoView
    extends Migration
{

    public function up()
    {
        $targetView = 'view.dev_module_port_geo';
    
        $sql['drop old view.dev_module_port_geo'] = 'DROP VIEW IF EXISTS view.dev_module_port_geo';
        $sql['create view.dev_module_port_geo'] =
            'CREATE VIEW view.dev_module_port_geo as
  WITH "invUser" AS (
      SELECT t.inventory_number          AS "invNumber",
             t.type_of_nomenclature      AS "nomenclatureType_1c",
             t.inventory_user            AS "invUserFio",
             t.inventory_user_tab_number AS "invUserTabNumber"
      FROM storage_1c.foreign_1c t
  )
  SELECT geo.region,
         geo.region_id,
         geo.city,
         geo.city_id,
         geo.office,
         geo.office_id,
         geo."lotusId",
         geo."officeAddress",
         geo."officeComment",
         geo."officeDetails",
         appliances.__id                                                                                             AS appliance_id,
         appliances."lastUpdate"                                                                                     AS "appLastUpdate",
         ((date_part(\'epoch\' :: text, age(now(), appliances."lastUpdate")) /
           (3600) :: double precision)) :: integer                                                                   AS "appAge",
         appliances."inUse"                                                                                          AS "appInUse",
         ((appliances.details ->> \'hostname\' :: text)) :: citext                                                     AS hostname,
         CASE
           WHEN (("phoneInfo".prefix IS NOT NULL) OR ("phoneInfo"."phoneDN" IS NOT NULL)) THEN ((
             ((((appliances.details ->> \'hostname\' :: text) || \',(DN \' :: text) || ("phoneInfo".prefix) :: text) ||
              ("phoneInfo"."phoneDN") :: text) || \')\' :: text)) :: citext
           ELSE ((appliances.details ->> \'hostname\' :: text)) :: citext
             END                                                                                                     AS hostname_dn,
         appliances.details                                                                                          AS "appDetails",
         CASE
           WHEN (((appliances.details #> \'{site}\' :: text []) IS NULL) OR (appliances.details @>
                                                                           \'{"site": {"row": "", "rack": "", "unit": "", "floor": "", "rackSide": ""}}\' :: jsonb))
                   THEN \'нет\' :: text
           ELSE \'есть\' :: text
             END                                                                                                     AS "appRoomLocation",
         appliances.comment                                                                                          AS "appComment",
         "appTypes".__id                                                                                             AS "appType_id",
         "appTypes".type                                                                                             AS "appType",
         "appTypes"."sortOrder"                                                                                      AS "appSortOrder",
         clusters.__id                                                                                               AS cluster_id,
         clusters.title                                                                                              AS "clusterTitle",
         clusters.details                                                                                            AS "clusterDetails",
         clusters.comment                                                                                            AS "clusterComment",
         "platformVendor".__id                                                                                       AS "platformVendor_id",
         "platformVendor".title                                                                                      AS "platformVendor",
         "platformItem".__id                                                                                         AS "platformItem_id",
         platform.title                                                                                              AS "platformTitle",
         platform.details                                                                                            AS "platformDetails",
         platform.__id                                                                                               AS platform_id,
         platform."isHW",
         "platformItem"."serialNumber"                                                                               AS "platformSerial",
         "softwareVendor".__id                                                                                       AS "softwareVendor_id",
         "softwareVendor".title                                                                                      AS "softwareVendor",
         "softwareItem".__id                                                                                         AS "softwareItem_id",
         software.__id                                                                                               AS software_id,
         software.title                                                                                              AS "softwareTitle",
         "softwareItem".version                                                                                      AS "softwareVersion",
         CASE
           WHEN (COALESCE("softwareItem".version, \'\' :: citext) = \'\' :: citext) THEN software.title
           ELSE ((((software.title) :: text || \' \' :: text) || ("softwareItem".version) :: text)) :: citext
             END                                                                                                     AS "softwareAndVersion",
         (SELECT array_to_json(array_agg(to_jsonb(t.*))) AS array_to_json
          FROM (SELECT "moduleItems".__appliance_id              AS appliance_id,
                       "moduleItems".__id                        AS "moduleItem_id",
                       "moduleItems"."serialNumber",
                       "moduleItems"."inventoryNumber",
                       "moduleItems".details,
                       "moduleItems".comment,
                       "moduleItems"."inUse",
                       "moduleItems"."notFound",
                       "moduleItems"."lastUpdate",
                       ((date_part(\'epoch\' :: text, age(now(), "moduleItems"."lastUpdate")) /
                         (3600) :: double precision)) :: integer AS "moduleItemAge",
                       modules.__id                              AS module_id,
                       modules.title,
                       modules.description
                FROM (equipment."moduleItems" "moduleItems"
                    JOIN equipment.modules modules ON (("moduleItems".__module_id = modules.__id)))
                WHERE (appliances.__id = "moduleItems".__appliance_id)
                ORDER BY modules.__id) t)                                                                            AS "moduleInfo",
         ((SELECT string_agg((minfo_t.module_title) :: text, \',\' :: text) AS string_agg
           FROM (SELECT modules.title AS module_title
                 FROM (equipment."moduleItems" "moduleItems"
                     JOIN equipment.modules modules ON (("moduleItems".__module_id = modules.__id)))
                 WHERE (appliances.__id = "moduleItems".__appliance_id)) minfo_t)) :: citext                         AS modules_list,
         (SELECT array_to_json(array_agg(to_jsonb(t.*))) AS array_to_json
          FROM (SELECT "dPorts".__appliance_id AS appliance_id,
                       "dPorts".__network_id   AS network_id,
                       "dPorts"."ipAddress",
                       "dPorts".masklen,
                       "dPorts"."macAddress",
                       "dPorts".details,
                       "dPorts".comment,
                       "dPorts"."isManagement",
                       "dPorts".__type_port_id AS "portType_id",
                       "dPortTypes".type       AS "portType"
                FROM (equipment."dataPorts" "dPorts"
                    JOIN equipment."dataPortTypes" "dPortTypes" ON (("dPorts".__type_port_id = "dPortTypes".__id)))
                WHERE (appliances.__id = "dPorts".__appliance_id)
                ORDER BY "dPorts"."ipAddress") t)                                                                    AS "portInfo",
         (SELECT "dPorts"."ipAddress"
          FROM equipment."dataPorts" "dPorts"
          WHERE ((appliances.__id = "dPorts".__appliance_id) AND ("dPorts"."isManagement" = true))
          ORDER BY "dPorts"."ipAddress"
          LIMIT 1)                                                                                                   AS "managementIp",
         (SELECT string_agg(ip_t.ip, \',\')::citext AS string_agg
          FROM (SELECT ((host("dPorts"."ipAddress") || \'/\' :: text) || coalesce("dPorts".masklen::text, \'\')) AS ip
                FROM equipment."dataPorts" "dPorts"
                WHERE (appliances.__id = "dPorts".__appliance_id)) ip_t)                                             AS "ipAddresses",
         "inventoryItem1C"."inventoryNumber",
         mols1c.fio                                                                                                  AS "responsiblePerson",
         "devCallsStats".last_call_day,
         "devCallsStats".d0_calls_amount,
         "devCallsStats".m0_calls_amount,
         "devCallsStats".m1_calls_amount,
         "devCallsStats".m2_calls_amount,
         "invUser"."invUserFio",
         "invUser"."invUserTabNumber"
  FROM (((((((((((((((((equipment.appliances appliances
      LEFT JOIN equipment."applianceTypes" "appTypes" ON ((appliances.__type_id = "appTypes".__id)))
      LEFT JOIN equipment."platformItems" "platformItem" ON ((appliances.__platform_item_id = "platformItem".__id)))
      LEFT JOIN equipment.platforms platform ON (("platformItem".__platform_id = platform.__id)))
      LEFT JOIN equipment.vendors "platformVendor" ON ((platform.__vendor_id = "platformVendor".__id)))
      LEFT JOIN equipment."softwareItems" "softwareItem" ON ((appliances.__software_item_id = "softwareItem".__id)))
      LEFT JOIN equipment.software software ON (("softwareItem".__software_id = software.__id)))
      LEFT JOIN equipment.vendors "softwareVendor" ON ((software.__vendor_id = "softwareVendor".__id)))
      LEFT JOIN equipment.clusters clusters ON ((appliances.__cluster_id = clusters.__id)))
      LEFT JOIN equipment."phoneInfo" "phoneInfo" ON (("phoneInfo".__appliance_id = appliances.__id)))
      LEFT JOIN storage_1c."appliances1C" appliances1c ON ((appliances.__id = appliances1c.__voice_appliance_id)))
      LEFT JOIN storage_1c."inventoryItem1C" "inventoryItem1C" ON ((appliances1c.__inventory_item_id =
                                                                    "inventoryItem1C".__id)))
      LEFT JOIN storage_1c.mols mols1c ON (("inventoryItem1C".__mol_id = mols1c.__id)))
      LEFT JOIN storage_1c."nomenclature1C" "nomenclature1C" ON (("nomenclature1C".__id =
                                                                  "inventoryItem1C".__nomenclature_id)))
      LEFT JOIN storage_1c."nomenclatureTypes" "nomenclatureTypes" ON (("nomenclatureTypes".__id =
                                                                        "nomenclature1C".__type_id)))
      LEFT JOIN (SELECT region.title    AS region,
                        region.__id     AS region_id,
                        city.title      AS city,
                        city.__id       AS city_id,
                        offices.title   AS office,
                        offices.__id    AS office_id,
                        offices."lotusId",
                        offices.comment AS "officeComment",
                        offices.details AS "officeDetails",
                        address.address AS "officeAddress"
                 FROM (((company.offices offices
                     JOIN geolocation.addresses address ON ((address.__id = offices.__address_id)))
                     JOIN geolocation.cities city ON ((city.__id = address.__city_id)))
                     JOIN geolocation.regions region ON ((region.__id = city.__region_id)))) geo ON ((geo.office_id =
                                                                                                      appliances.__location_id)))
      LEFT JOIN view.dev_calls_stats "devCallsStats" ON (("devCallsStats".appliance_id = appliances.__id)))
      LEFT JOIN "invUser" ON ((("invUser"."invNumber" = "inventoryItem1C"."inventoryNumber") AND
                               ("invUser"."nomenclatureType_1c" = "nomenclatureTypes".type))))'
        ;
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $targetView = 'view.dev_module_port_geo';
    
        $sql['drop old '.$targetView] = 'DROP VIEW IF EXISTS '.$targetView;
        $sql['create '.$targetView] =
            'CREATE VIEW '.$targetView.' AS
WITH
     "invUser" AS (
    SELECT t.inventory_number "invNumber",
           t.type_of_nomenclature "nomenclatureType_1c",
           t.inventory_user "invUserFio",
           t.inventory_user_tab_number "invUserTabNumber"
    FROM storage_1c.foreign_1c t
    )

SELECT
       region,
       region_id,
       city,
       city_id,
       office,
       office_id,
       "lotusId",
       "officeAddress",
       "officeComment",
       "officeDetails",
       appliances.__id AS appliance_id,
       appliances."lastUpdate" AS "appLastUpdate",
       (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
       appliances."inUse" AS "appInUse",
       CAST(appliances.details::jsonb->>\'hostname\' AS citext) AS hostname,
       CASE WHEN ("phoneInfo".prefix IS NOT NULL) OR ("phoneInfo"."phoneDN" IS NOT NULL )
                 THEN
           CAST(appliances.details::jsonb->>\'hostname\'||
                \',(DN \' ||
                "phoneInfo".prefix ||
                "phoneInfo"."phoneDN" ||
                \')\' AS citext)
            ELSE
           CAST(appliances.details::jsonb->>\'hostname\' AS citext)
           END
    AS hostname_dn,
       appliances.details AS "appDetails",

       CASE WHEN
           (appliances.details::jsonb#>\'{site}\' IS NULL) OR
           (appliances.details::jsonb @> \'{"site": {"row": "", "rack": "", "unit": "", "floor": "", "rackSide": ""}}\'::jsonb)
                 THEN \'нет\'
            ELSE \'есть\'
           END AS "appRoomLocation",

       appliances."comment" AS "appComment",
       "appTypes".__id AS "appType_id",
       "appTypes".type AS "appType",
       "appTypes"."sortOrder" AS "appSortOrder",
       clusters.__id AS cluster_id,
       clusters.title AS "clusterTitle",
       clusters.details AS "clusterDetails",
       clusters.comment AS "clusterComment",
       "platformVendor".__id AS "platformVendor_id",
       "platformVendor".title AS "platformVendor",
       "platformItem".__id AS "platformItem_id",
       platform.title AS "platformTitle",
       platform.details AS "platformDetails",
       platform.__id AS "platform_id",
       platform."isHW" AS "isHW",
       "platformItem"."serialNumber" AS "platformSerial",
       "softwareVendor".__id AS "softwareVendor_id",
       "softwareVendor".title AS "softwareVendor",
       "softwareItem".__id AS "softwareItem_id",
       software.__id AS "software_id",
       software.title AS "softwareTitle",
       "softwareItem".version AS "softwareVersion",
       CASE WHEN COALESCE("softwareItem".version, \'\') = \'\'
                 THEN
           CAST(software.title AS citext)
            ELSE
           CAST(software.title || \' \' || "softwareItem".version AS citext)
           END
    AS "softwareAndVersion",

       ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                                                           (SELECT
                                                                   "moduleItems".__appliance_id AS appliance_id,
                                                                   "moduleItems".__id AS "moduleItem_id",
                                                                   "moduleItems"."serialNumber" AS "serialNumber",
                                                                   "moduleItems"."inventoryNumber" AS "inventoryNumber",
                                                                   "moduleItems"."details" AS "details",
                                                                   "moduleItems"."comment" AS "comment",
                                                                   "moduleItems"."inUse" AS "inUse",
                                                                   "moduleItems"."notFound" AS "notFound",
                                                                   "moduleItems"."lastUpdate" AS "lastUpdate",
                                                                   (EXTRACT(EPOCH FROM age(now(), "moduleItems"."lastUpdate"))/3600)::INT AS "moduleItemAge",
                                                                   "modules".__id AS "module_id",
                                                                   "modules".title AS "title",
                                                                   "modules".description AS "description"
                                                            FROM equipment."moduleItems" AS "moduleItems"
                                                                   JOIN equipment.modules AS modules ON "moduleItems".__module_id = modules.__id
                                                            WHERE appliances.__id = "moduleItems".__appliance_id
                                                            ORDER BY module_id) AS t
       ) AS "moduleInfo",
       (SELECT string_agg(minfo_t.module_title, \',\') FROM (
            SELECT "modules".title module_title FROM equipment."moduleItems" AS "moduleItems"
              JOIN equipment.modules AS modules ON "moduleItems".__module_id = modules.__id
              WHERE appliances.__id = "moduleItems".__appliance_id) AS minfo_t
       )::citext AS "modules_list",
       ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                                                           (SELECT
                                                                   "dPorts".__appliance_id AS appliance_id,
                                                                   "dPorts".__network_id AS network_id,
                                                                   "dPorts"."ipAddress" AS "ipAddress",
                                                                   "dPorts"."masklen" AS "masklen",
                                                                   "dPorts"."macAddress" AS "macAddress",
                                                                   "dPorts"."details" AS "details",
                                                                   "dPorts"."comment" AS "comment",
                                                                   "dPorts"."isManagement" AS "isManagement",
                                                                   "dPorts"."__type_port_id" AS "portType_id",
                                                                   "dPortTypes".type AS "portType"
                                                            FROM equipment."dataPorts" AS "dPorts"
                                                                   JOIN equipment."dataPortTypes" AS "dPortTypes" ON "dPorts".__type_port_id = "dPortTypes".__id
                                                            WHERE appliances.__id = "dPorts".__appliance_id
                                                            ORDER BY "ipAddress") AS t
       ) AS "portInfo",
       (SELECT "ipAddress" FROM equipment."dataPorts" AS "dPorts"
        WHERE appliances.__id = "dPorts".__appliance_id AND "isManagement" = TRUE
        ORDER BY "ipAddress" LIMIT 1
       ) AS "managementIp",
       (SELECT string_agg(ip_t.ip::text, \',\') FROM (SELECT host("ipAddress")::text || \'/\' || masklen ip FROM equipment."dataPorts" AS "dPorts"
                                                    WHERE appliances.__id = "dPorts".__appliance_id) AS ip_t
       ) AS "ipAddresses",

       "inventoryItem1C"."inventoryNumber" AS "inventoryNumber",
       mols1C.fio AS "responsiblePerson",
       "devCallsStats".last_call_day,
       "devCallsStats".d0_calls_amount,
       "devCallsStats".m0_calls_amount,
       "devCallsStats".m1_calls_amount,
       "devCallsStats".m2_calls_amount,
       "invUser"."invUserFio",
       "invUser"."invUserTabNumber"

FROM equipment.appliances AS appliances
       LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
       LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
       LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
       LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id

       LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
       LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
       LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
       LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id

       LEFT JOIN equipment."phoneInfo" AS "phoneInfo" ON "phoneInfo".__appliance_id = appliances.__id

       LEFT JOIN storage_1c."appliances1C" AS appliances1C ON appliances.__id = appliances1C.__voice_appliance_id
       LEFT JOIN storage_1c."inventoryItem1C" AS "inventoryItem1C" ON appliances1C.__inventory_item_id = "inventoryItem1C".__id
       LEFT JOIN storage_1c.mols AS mols1C ON "inventoryItem1C".__mol_id = mols1C.__id
       LEFT JOIN storage_1c."nomenclature1C" AS "nomenclature1C" ON "nomenclature1C".__id = "inventoryItem1C".__nomenclature_id
       LEFT JOIN storage_1c."nomenclatureTypes" AS "nomenclatureTypes" ON "nomenclatureTypes".__id = "nomenclature1C".__type_id

       LEFT JOIN (
                 SELECT
                        region.title AS region,
                        region.__id AS region_id,
                        city.title AS city,
                        city.__id AS city_id,
                        offices.title AS office,
                        offices.__id AS office_id,
                        offices."lotusId" AS "lotusId",
                        offices.comment AS "officeComment",
                        offices.details AS "officeDetails",
                        address.address AS "officeAddress"

                 FROM company.offices AS offices
                        JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
                        JOIN geolocation.cities AS city ON city.__id = address.__city_id
                        JOIN geolocation.regions AS region ON region.__id = city.__region_id
                 ) AS geo
         ON geo.office_id = appliances.__location_id

       LEFT JOIN view.dev_calls_stats AS "devCallsStats" ON "devCallsStats".appliance_id = appliances.__id
       LEFT JOIN "invUser" ON "invUser"."invNumber" = "inventoryItem1C"."inventoryNumber" AND "invUser"."nomenclatureType_1c" = "nomenclatureTypes".type'
        ;
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}