-- ========net_ancestors
-- function for finding all ancestor for network by id
DROP FUNCTION IF EXISTS net_ancestors;
CREATE OR REPLACE FUNCTION net_ancestors(IN netId INT, OUT ancestors text) AS $$
BEGIN
  ancestors := (SELECT string_agg(ancestors.id::text, ',')
               FROM (
                    SELECT __id id
                    FROM network.networks
                    WHERE address >> (SELECT address FROM network.networks WHERE __id = netId)
                    ORDER BY address ASC
                   )AS ancestors);

END;
$$
LANGUAGE plpgsql;

SELECT __id, address, net_ancestors(__id) FROM network.networks
WHERE __id = 4039;

-- ========host_ancestors
-- function for finding all ancestor for host by id
DROP FUNCTION IF EXISTS host_ancestors;
CREATE OR REPLACE FUNCTION host_ancestors(IN hostId INT, OUT ancestors text) AS $$
BEGIN
  ancestors := (SELECT string_agg(ancestors.id::text, ',')
                FROM (
                     SELECT __id id
                     FROM network.networks
                     WHERE address >> (SELECT "ipAddress" FROM equipment."dataPorts" WHERE __id = hostId)
                ORDER BY address ASC
               )AS ancestors);

END;
$$
LANGUAGE plpgsql;

SELECT __id, "ipAddress", host_ancestors(__id) FROM equipment."dataPorts"
WHERE __id = 5228;