DROP VIEW IF EXISTS view.geo;

CREATE OR REPLACE VIEW view.geo AS
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
    JOIN geolocation.regions AS region ON region.__id = city.__region_id;