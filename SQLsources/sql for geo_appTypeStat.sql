CREATE OR REPLACE VIEW view.geo_people AS
    SELECT
        CAST("lotusLoc".reg_center AS citext) AS "regCenter",
        region.title AS region,
        region.__id AS region_id,
        city.title AS city,
        city.__id AS city_id,
        offices.title AS office,
        offices.__id AS office_id,
        offices."lotusId" AS "lotusId",
        offices.comment AS "officeComment",
        offices.details AS "officeDetails",
        address.address AS "officeAddress",
        sum("lotusLoc".employees) AS people,

        "appTypes".__id   AS "appType_id",
        "appTypes".type   AS "appType",
        count(appliances.__id) AS "appTypeCounter"

    FROM company.offices AS offices
        JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
        JOIN geolocation.cities AS city ON city.__id = address.__city_id
        JOIN geolocation.regions AS region ON region.__id = city.__region_id
        LEFT JOIN lotus.locations AS "lotusLoc" ON offices."lotusId" = "lotusLoc".lotus_id
        LEFT JOIN equipment.appliances AS appliances ON offices.__id = appliances.__location_id
        LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
    GROUP BY  "lotusLoc".reg_center, region.title, region.__id, city.__id, city.title,  offices.title, offices.__id, offices."lotusId", offices.comment, offices.details, address.address, "appTypes".__id, "appTypes".type
    ORDER BY office;

-- ==========
SELECT
    "appTypes".type AS "appType",
    platforms.__id AS platform_id,
    cast(vendors.title || ' ' || platforms.title AS citext) AS "platformTitle",
    count(appliances.__id) AS "appTypeQuantity"
FROM equipment.appliances AS appliances
    JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
    JOIN equipment."platformItems" AS "platformItems" ON appliances.__platform_item_id = "platformItems".__id
    JOIN equipment.platforms AS platforms ON "platformItems".__platform_id = platforms.__id
    JOIN equipment.vendors AS vendors ON platforms.__vendor_id = vendors.__id
WHERE appliances.__location_id = 352
GROUP BY "appType", "appTypes"."sortOrder", platform_id, "platformTitle"
ORDER BY "appTypes"."sortOrder", "platformTitle"