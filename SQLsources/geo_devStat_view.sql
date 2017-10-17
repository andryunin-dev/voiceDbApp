SELECT
    offices.__id AS office_id,
    offices.title AS office,
    offices."lotusId" AS "lotusId",
    offices.comment AS "comment",
    offices.details AS "details",
    "lotusData".employees AS people,
    "officeStatuses".__id AS "officeStatus_id",
    "officeStatuses".title AS "officeStatus",
    addresses.address AS address,
    cities.__id AS city_id,
    cities.title AS city,
    regions.__id AS region_id,
    regions.title AS region,
    CAST("lotusData".reg_center AS citext) AS "regCenter",

    (SELECT jsonb_agg((to_jsonb(t))) FROM (
        SELECT
            "appTypes".type AS "appType",
            "appTypes".__id AS "appType_id",
            count(appliances.__id) AS "appTypeQuantity",
            (SELECT jsonb_agg((to_jsonb(t))) FROM (
                  SELECT
                      platforms.__id AS platform_id,
                      cast(vendors.title || ' ' || platforms.title AS citext) AS "platformTitle",
                      count(platforms.__id)
                  FROM equipment.appliances AS appliances_loc
                      JOIN equipment."applianceTypes" AS "appTypes_l2" ON appliances_loc.__type_id = "appTypes".__id
                      JOIN equipment."platformItems" AS "platformItems" ON appliances_loc.__platform_item_id = "platformItems".__id
                      JOIN equipment.platforms AS platforms ON "platformItems".__platform_id = platforms.__id
                      JOIN equipment.vendors AS vendors ON platforms.__vendor_id = vendors.__id
                  WHERE appliances_loc.__location_id = appliances.__location_id AND "appTypes_l2".__id = "appTypes".__id
                  GROUP BY platform_id, "platformTitle"
                                                      ORDER BY "platformTitle"
            ) AS t) AS "platformStat"
        FROM equipment.appliances AS appliances
            JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
        WHERE appliances.__location_id = offices.__id
        GROUP BY appliances.__location_id, "appType", "appType_id", "appTypes"."sortOrder"
        ORDER BY "appTypes"."sortOrder"
    ) AS t) AS "devStatistics"
FROM company.offices AS offices
    LEFT JOIN lotus.locations AS "lotusData" ON offices."lotusId" = "lotusData".lotus_id
    LEFT JOIN company."officeStatuses" AS "officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
    LEFT JOIN geolocation.addresses  AS addresses ON offices.__address_id = addresses.__id
    JOIN geolocation.cities AS cities ON addresses.__city_id = cities.__id
    JOIN geolocation.regions AS regions ON cities.__region_id = regions.__id;

-- вариант с подсчетом всего/активных
SELECT
    offices.__id AS office_id,
    offices.title AS office,
    offices."lotusId" AS "lotusId",
    "lotusData".employees AS people,
    "officeStatuses".__id AS "officeStatus_id",
    "officeStatuses".title AS "officeStatus",
    addresses.address AS address,
    cities.__id AS city_id,
    cities.title AS city,
    regions.__id AS region_id,
    regions.title AS region,
    CAST("lotusData".reg_center AS citext) AS "regCenter",

    (SELECT jsonb_agg((to_jsonb(t))) FROM (
        SELECT
            "appTypes".type AS "appType",
            "appTypes".__id AS "appType_id",
            count(appliances.__id) AS "totalQty",
            sum(CASE WHEN ((EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate")) / 3600)::INT < 73) THEN 1 ELSE 0 END ) AS "activeQty",
            (SELECT jsonb_agg((to_jsonb(t))) FROM (
            SELECT
                platforms.__id AS platform_id,
                cast(vendors.title || ' ' || platforms.title AS citext) AS "platformTitle",
                count(platforms.__id) AS "totalQty",
                sum(CASE WHEN ((EXTRACT(EPOCH FROM age(now(), appliances_loc."lastUpdate")) / 3600)::INT < 73) THEN 1 ELSE 0 END ) AS "activeQty"
            FROM equipment.appliances AS appliances_loc
                JOIN equipment."applianceTypes" AS "appTypes_l2" ON appliances_loc.__type_id = "appTypes".__id
                JOIN equipment."platformItems" AS "platformItems" ON appliances_loc.__platform_item_id = "platformItems".__id
                JOIN equipment.platforms AS platforms ON "platformItems".__platform_id = platforms.__id
                JOIN equipment.vendors AS vendors ON platforms.__vendor_id = vendors.__id
            WHERE appliances_loc.__location_id = appliances.__location_id AND "appTypes_l2".__id = "appTypes".__id
            GROUP BY platform_id, "platformTitle"
            ORDER BY "platformTitle"
        ) AS t) AS "platformStat"
        FROM equipment.appliances AS appliances
            JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
        WHERE appliances.__location_id = offices.__id
        GROUP BY appliances.__location_id, "appType", "appType_id", "appTypes"."sortOrder"
                                              ORDER BY "appTypes"."sortOrder"
    ) AS t) AS "devStatistics"
FROM company.offices AS offices
    LEFT JOIN lotus.locations AS "lotusData" ON offices."lotusId" = "lotusData".lotus_id
    LEFT JOIN company."officeStatuses" AS "officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
    LEFT JOIN geolocation.addresses  AS addresses ON offices.__address_id = addresses.__id
    JOIN geolocation.cities AS cities ON addresses.__city_id = cities.__id
    JOIN geolocation.regions AS regions ON cities.__region_id = regions.__id
WHERE offices.__id = 227;



