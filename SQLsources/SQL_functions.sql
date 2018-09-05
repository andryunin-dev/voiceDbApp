-- create function for search locations via network id
DROP FUNCTION IF EXISTS network_locations(bigint);

CREATE OR REPLACE FUNCTION network_locations(IN netId BIGINT, OUT locations jsonb) AS $$
BEGIN
  locations := (
    SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
    FROM company.offices AS offices
      JOIN equipment.appliances ON offices.__id = appliances.__location_id
      JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
      JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
    WHERE net.__id = netId
    );
end;
$$
LANGUAGE plpgsql;
-- usage example
SELECT __id, address, network_locations(__id) AS locations  FROM network.networks ORDER BY address;