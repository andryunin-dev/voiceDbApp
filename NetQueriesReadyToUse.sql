-- GET ROOT NETWORKS AND HOSTS
-- ==========test
WITH all_roots AS (
    SELECT __id AS id, address AS net_address FROM testnetworks AS net_table WHERE
      NOT EXISTS(SELECT address from testnetworks AS net_table2 WHERE net_table2.address >> net_table.address)
    ORDER BY address
)
SELECT
  (SELECT string_agg(all_roots.id ::text, ',') FROM  all_roots
  WHERE masklen(net_address) != 32) AS "rootNetIds",
--   select all root hosts that have 32 mask
  (
    SELECT string_agg("rootHosts32".id::text, ',') FROM
    (
      SELECT __id AS id, host_table.ipaddress AS host_address FROM testhosts AS host_table
        JOIN
        (
          SELECT * FROM all_roots
          WHERE masklen(net_address) = 32
        ) AS all_32_net_root
        ON host_table.__network_id = all_32_net_root.id
    ) AS "rootHosts32"
  ) AS rootHostIds;

DROP FUNCTION test_root_ids();
CREATE OR REPLACE FUNCTION test_root_ids() RETURNS TABLE("netId" text, "hostId" text) AS $$
BEGIN
  RETURN QUERY WITH all_roots AS (
      SELECT __id AS id, address AS net_address FROM testnetworks AS net_table WHERE
        NOT EXISTS(SELECT address from testnetworks AS net_table2 WHERE net_table2.address >> net_table.address)
      ORDER BY address
  )
  SELECT
    (SELECT string_agg(all_roots.id ::text, ',') FROM  all_roots
    WHERE masklen(net_address) != 32) AS "rootNetIds",
    --   select all root hosts that have 32 mask
    (
      SELECT string_agg("rootHosts32".id::text, ',') FROM
        (
          SELECT __id AS id, host_table.ipaddress AS host_address FROM testhosts AS host_table
            JOIN
            (
              SELECT * FROM all_roots
              WHERE masklen(net_address) = 32
            ) AS all_32_net_root
              ON host_table.__network_id = all_32_net_root.id
        ) AS "rootHosts32"
    ) AS rootHostIds;
END
$$ LANGUAGE plpgsql;

SELECT * FROM test_root_ids();


-- ===========production
EXPLAIN ANALYSE
WITH all_roots AS (
    SELECT __id AS id, address AS net_address FROM network.networks AS net_table WHERE
      NOT EXISTS(SELECT address from network.networks AS net_table2 WHERE net_table2.address >> net_table.address)
    ORDER BY address
)
SELECT
  (SELECT string_agg(all_roots.id ::text, ',') FROM  all_roots
  WHERE masklen(net_address) != 32) AS "netId",
  --   select all root hosts that have 32 mask
  (
    SELECT string_agg("rootHosts32".id::text, ',') FROM
      (
        SELECT __id AS id, host_table."ipAddress" AS host_address FROM equipment."dataPorts" AS host_table
          JOIN
          (
            SELECT * FROM all_roots
            WHERE masklen(net_address) = 32
          ) AS all_32_net_root
            ON host_table.__network_id = all_32_net_root.id
      ) AS "rootHosts32"
  ) AS "hostId";


DROP FUNCTION root_ids_string();
CREATE OR REPLACE FUNCTION root_ids_string() RETURNS TABLE("nets" text, "hosts" text) AS $$
BEGIN
  RETURN QUERY WITH all_roots AS (
      SELECT __id AS id, address AS net_address FROM network.networks AS net_table WHERE
        NOT EXISTS(SELECT address from network.networks AS net_table2 WHERE net_table2.address >> net_table.address)
      ORDER BY address
  )
  SELECT
    (SELECT string_agg(all_roots.id ::text, ',') FROM  all_roots
    WHERE masklen(net_address) != 32) AS "netId",
    --   select all root hosts that have 32 mask
    (
      SELECT string_agg("rootHosts32".id::text, ',') FROM
        (
          SELECT __id AS id, host_table."ipAddress" AS host_address FROM equipment."dataPorts" AS host_table
            JOIN
            (
              SELECT * FROM all_roots
              WHERE masklen(net_address) = 32
            ) AS all_32_net_root
              ON host_table.__network_id = all_32_net_root.id
        ) AS "rootHosts32"
    ) AS "hostId";
END
$$ LANGUAGE plpgsql;

SELECT * FROM root_ids_string();


