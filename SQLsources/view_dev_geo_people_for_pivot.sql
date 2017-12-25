DROP VIEW IF EXISTS view.dev_geo_people_1;

CREATE OR REPLACE VIEW view.dev_geo_people_1 AS
SELECT
  geo."regCenter" AS "regCenter",
  region,
  region_id,
  city,
  city_id,
  office,
  office_id,
  "lotusId",
  geo.people AS people,

  appliances.__id                                                         AS appliance_id,
  appliances."lastUpdate"                                                 AS "appLastUpdate",
  (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate")) / 3600) :: INT AS "appAge",
  appliances."inUse"                                                      AS "appInUse",
  "appTypes".__id                                                         AS "appType_id",
  "appTypes".type                                                         AS "appType",
  "platformVendor".__id                                                   AS "platformVendor_id",
  "platformVendor".title                                                  AS "platformVendor",
  "platformItem".__id                                                     AS "platformItem_id",
  platform.title                                                          AS "platformTitle",
  platform.__id                                                           AS "platform_id"
FROM equipment.appliances AS appliances
  LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
  LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
  LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
  LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
  LEFT JOIN (
              SELECT
                region.title      AS region,
                region.__id       AS region_id,
                city.title        AS city,
                city.__id         AS city_id,
                offices.title     AS office,
                offices.__id      AS office_id,
                offices."lotusId" AS "lotusId",
                "lotusData".employees AS people,
                CAST("lotusData".reg_center AS citext) AS "regCenter"

              FROM company.offices AS offices
                LEFT JOIN lotus.locations AS "lotusData" ON offices."lotusId" = "lotusData".lotus_id
                JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
                JOIN geolocation.cities AS city ON city.__id = address.__city_id
                JOIN geolocation.regions AS region ON region.__id = city.__region_id
            ) AS geo
    ON geo.office_id = appliances.__location_id;

SELECT * FROM view.dev_geo_people_1;
-- variant 2

CREATE OR REPLACE VIEW view.dev_geo_people_2 AS
  SELECT
    --   geo."regCenter" AS "regCenter",
    region.title      AS region,
    region.__id       AS region_id,
    city.title        AS city,
    city.__id         AS city_id,
    offices.title     AS office,
    offices.__id      AS office_id,
    offices."lotusId" AS "lotusId",
    --   geo.people AS people,

    appliances.__id                                                         AS appliance_id,
    appliances."lastUpdate"                                                 AS "appLastUpdate",
    (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate")) / 3600) :: INT AS "appAge",
    appliances."inUse"                                                      AS "appInUse",
    "appTypes".__id                                                         AS "appType_id",
    "appTypes".type                                                         AS "appType",
    "platformVendor".__id                                                   AS "platformVendor_id",
    "platformVendor".title                                                  AS "platformVendor",
    "platformItem".__id                                                     AS "platformItem_id",
    platform.title                                                          AS "platformTitle",
    platform.__id                                                           AS "platform_id"
  FROM equipment.appliances AS appliances
    LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
    LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
    LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
    LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
    LEFT JOIN company.offices AS offices ON appliances.__location_id = offices.__id
    JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
    JOIN geolocation.cities AS city ON city.__id = address.__city_id
    JOIN geolocation.regions AS region ON region.__id = city.__region_id
