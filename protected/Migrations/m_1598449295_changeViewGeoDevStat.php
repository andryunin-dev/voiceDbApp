<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1598449295_changeViewGeoDevStat
    extends Migration
{

    public function up()
    {
        $sql['drop old view'] = '
            DROP VIEW IF EXISTS view."geo_devStat"
        ';
        $sql['change_view'] = '
        CREATE OR REPLACE VIEW view."geo_devStat"
        as
        SELECT offices.__id                               AS office_id,
       offices.title                              AS office,
       offices."lotusId",
       offices.comment,
       offices.details,
       offices."isCCO",
       CASE offices."isCCO" WHEN TRUE THEN \'да\' ELSE \'нет\' END AS "isCCO_txt",
       "lotusData".employees                      AS people,
       "officeStatuses".__id                      AS "officeStatus_id",
       "officeStatuses".title                     AS "officeStatus",
       addresses.address,
       cities.__id                                AS city_id,
       cities.title                               AS city,
       regions.__id                               AS region_id,
       regions.title                              AS region,
       "lotusData".reg_center::citext             AS "regCenter",
       (SELECT jsonb_agg(to_jsonb(t.*)) AS jsonb_agg
        FROM (SELECT "appTypes".type                                                                               AS "appType",
                     "appTypes".__id                                                                               AS "appType_id",
                     count(appliances.__id)                                                                        AS "totalQty",
                     sum(
                             CASE
                                 WHEN (date_part(\'epoch\'::text, age(now(), appliances."lastUpdate")) /
                                       3600)::integer < 73 THEN 1
                                 ELSE 0
                                 END)                                                                              AS "activeQty",
                     (SELECT jsonb_agg(to_jsonb(t_1.*)) AS jsonb_agg
                      FROM (SELECT platforms.__id                                                        AS platform_id,
                                   ((vendors.title::text || \' \'::text) || platforms.title::text)::citext AS "platformTitle",
                                   count(platforms.__id)                                                 AS "totalQty",
                                   sum(
                                           CASE
                                               WHEN (date_part(\'epoch\'::text, age(now(), appliances_loc."lastUpdate")) /
                                                     3600::double precision)::integer < 73 THEN 1
                                               ELSE 0
                                               END)                                                      AS "activeQty"
                            FROM equipment.appliances appliances_loc
                                     JOIN equipment."applianceTypes" "appTypes_l2"
                                          ON appliances_loc.__type_id = "appTypes".__id
                                     JOIN equipment."platformItems" "platformItems"
                                          ON appliances_loc.__platform_item_id = "platformItems".__id
                                     JOIN equipment.platforms platforms
                                          ON "platformItems".__platform_id = platforms.__id
                                     JOIN equipment.vendors vendors ON platforms.__vendor_id = vendors.__id
                            WHERE appliances_loc.__location_id = appliances.__location_id
                              AND "appTypes_l2".__id = "appTypes".__id
                            GROUP BY platforms.__id,
                                     (((vendors.title::text || \' \'::text) || platforms.title::text)::citext)
                            ORDER BY (((vendors.title::text || \' \'::text) || platforms.title::text)::citext)) t_1) AS "platformStat"
              FROM equipment.appliances appliances
                       JOIN equipment."applianceTypes" "appTypes" ON appliances.__type_id = "appTypes".__id
              WHERE appliances.__location_id = offices.__id
              GROUP BY appliances.__location_id, "appTypes".type, "appTypes".__id, "appTypes"."sortOrder"
              ORDER BY "appTypes"."sortOrder") t) AS "devStatistics"
FROM company.offices offices
         LEFT JOIN lotus.locations "lotusData" ON offices."lotusId" = "lotusData".lotus_id
         LEFT JOIN company."officeStatuses" "officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
         LEFT JOIN geolocation.addresses addresses ON offices.__address_id = addresses.__id
         JOIN geolocation.cities cities ON addresses.__city_id = cities.__id
         JOIN geolocation.regions regions ON cities.__region_id = regions.__id
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
        $sql['drop old view'] = '
            DROP VIEW IF EXISTS view."geo_devStat"
        ';
        $sql['change_view'] = '
        CREATE OR REPLACE VIEW view."geo_devStat"
        as
        SELECT offices.__id                               AS office_id,
       offices.title                              AS office,
       offices."lotusId",
       offices.comment,
       offices.details,
       "lotusData".employees                      AS people,
       "officeStatuses".__id                      AS "officeStatus_id",
       "officeStatuses".title                     AS "officeStatus",
       addresses.address,
       cities.__id                                AS city_id,
       cities.title                               AS city,
       regions.__id                               AS region_id,
       regions.title                              AS region,
       "lotusData".reg_center::citext             AS "regCenter",
       (SELECT jsonb_agg(to_jsonb(t.*)) AS jsonb_agg
        FROM (SELECT "appTypes".type                                                                               AS "appType",
                     "appTypes".__id                                                                               AS "appType_id",
                     count(appliances.__id)                                                                        AS "totalQty",
                     sum(
                             CASE
                                 WHEN (date_part(\'epoch\'::text, age(now(), appliances."lastUpdate")) /
                                       3600)::integer < 73 THEN 1
                                 ELSE 0
                                 END)                                                                              AS "activeQty",
                     (SELECT jsonb_agg(to_jsonb(t_1.*)) AS jsonb_agg
                      FROM (SELECT platforms.__id                                                        AS platform_id,
                                   ((vendors.title::text || \' \'::text) || platforms.title::text)::citext AS "platformTitle",
                                   count(platforms.__id)                                                 AS "totalQty",
                                   sum(
                                           CASE
                                               WHEN (date_part(\'epoch\'::text, age(now(), appliances_loc."lastUpdate")) /
                                                     3600::double precision)::integer < 73 THEN 1
                                               ELSE 0
                                               END)                                                      AS "activeQty"
                            FROM equipment.appliances appliances_loc
                                     JOIN equipment."applianceTypes" "appTypes_l2"
                                          ON appliances_loc.__type_id = "appTypes".__id
                                     JOIN equipment."platformItems" "platformItems"
                                          ON appliances_loc.__platform_item_id = "platformItems".__id
                                     JOIN equipment.platforms platforms
                                          ON "platformItems".__platform_id = platforms.__id
                                     JOIN equipment.vendors vendors ON platforms.__vendor_id = vendors.__id
                            WHERE appliances_loc.__location_id = appliances.__location_id
                              AND "appTypes_l2".__id = "appTypes".__id
                            GROUP BY platforms.__id,
                                     (((vendors.title::text || \' \'::text) || platforms.title::text)::citext)
                            ORDER BY (((vendors.title::text || \' \'::text) || platforms.title::text)::citext)) t_1) AS "platformStat"
              FROM equipment.appliances appliances
                       JOIN equipment."applianceTypes" "appTypes" ON appliances.__type_id = "appTypes".__id
              WHERE appliances.__location_id = offices.__id
              GROUP BY appliances.__location_id, "appTypes".type, "appTypes".__id, "appTypes"."sortOrder"
              ORDER BY "appTypes"."sortOrder") t) AS "devStatistics"
FROM company.offices offices
         LEFT JOIN lotus.locations "lotusData" ON offices."lotusId" = "lotusData".lotus_id
         LEFT JOIN company."officeStatuses" "officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
         LEFT JOIN geolocation.addresses addresses ON offices.__address_id = addresses.__id
         JOIN geolocation.cities cities ON addresses.__city_id = cities.__id
         JOIN geolocation.regions regions ON cities.__region_id = regions.__id
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

    }
    
}