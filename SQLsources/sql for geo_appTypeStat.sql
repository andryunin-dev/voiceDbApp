SELECT
    region.title      AS region,
    region.__id       AS region_id,
    city.__id         AS city_id,
    city.title        AS city,
    offices.__id      AS office_id,
    address.address   AS "officeAddress",
    offices.title     AS office,
    offices."lotusId" AS "lotusId",
    offices.comment   AS "officeComment",
    offices.details   AS "officeDetails",
    "appTypes".__id   AS "appType_id",
    "appTypes".type   AS "appType",
    count(appliances.__id) AS "appTypeCounter"

FROM company.offices AS offices
    JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
    JOIN geolocation.cities AS city ON city.__id = address.__city_id
    JOIN geolocation.regions AS region ON region.__id = city.__region_id
    LEFT JOIN equipment.appliances AS appliances ON offices.__id = appliances.__location_id
    LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
GROUP BY region, region_id, city_id, city,  office_id, "officeAddress", office, "lotusId", "officeComment", "officeDetails", "appType_id", "appType"
ORDER BY office;
