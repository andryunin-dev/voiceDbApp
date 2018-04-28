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
SELECT * FROM root_ids_string();

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

-- GET NETWORK CHILDREN BY ID

DROP FUNCTION test_net_children(int);
SELECT 'test', test_net_children(1);

CREATE OR REPLACE FUNCTION test_net_children(IN netId INT, OUT nets text) AS $$
BEGIN
    nets := (SELECT string_agg(net_children.id, ',')
             FROM (
                      WITH all_net_children AS (
                          SELECT
                              n_table.__id    AS id,
                              n_table.address AS address
                          FROM testnetworks AS n_table
                          WHERE n_table.address << (SELECT address
                                                    FROM testnetworks AS n_table2
                                                    WHERE n_table2.__id = netId)
                      )
                      SELECT t1.id :: TEXT AS id
                      FROM all_net_children AS t1
                      WHERE
                          NOT EXISTS(
                              SELECT t1.address
                              FROM all_net_children AS t2
                              WHERE t2.address >> t1.address
                          ) AND
                          masklen(t1.address) != 32
                      ORDER BY t1.address
                  ) AS net_children);
END;
$$
LANGUAGE plpgsql;

-- production=======================
DROP FUNCTION net_children(int);
SELECT address, net_children(__id) FROM network.networks WHERE __id =  4261;
SELECT address, net_children(__id) FROM network.networks LIMIT 1000;

CREATE OR REPLACE FUNCTION net_children(IN netId INT, OUT nets text) AS $$
BEGIN
    nets := (SELECT string_agg(net_children.id, ',')
             FROM (
                      WITH all_net_children AS (
                          SELECT
                              t0.__id    AS id,
                              t0.address AS address
                          FROM network.networks AS t0
                          WHERE t0.address << (SELECT address
                                               FROM network.networks AS t1
                                               WHERE t1.__id = netId)
                      )
                      SELECT t0.id :: TEXT AS id
                      FROM all_net_children AS t0
                      WHERE
                          NOT EXISTS(
                              SELECT t0.address
                              FROM all_net_children AS t1
                              WHERE t1.address >> t0.address
                          ) AND
                          masklen(t0.address) != 32
                      ORDER BY t0.address
                  ) AS net_children);
END;
$$
LANGUAGE plpgsql;


-- GET HOST CHILDREN BY ID

DROP FUNCTION test_host_children(int);
SELECT 'test', test_host_children(1);
SELECT 'test', address, test_host_children(__id), test_net_children(__id) FROM testnetworks ORDER BY address;

CREATE OR REPLACE FUNCTION test_host_children(IN netId INT, OUT hosts text) AS $$
BEGIN
    hosts := (SELECT string_agg(net_children.id::text, ',')
              FROM (
                       WITH all_net_children AS (
                           SELECT __id AS id, address AS address
                           FROM testnetworks
                           WHERE address << (SELECT address
                                             FROM testnetworks AS t1
                                             WHERE t1.__id = netId)
                       )
                       SELECT __id AS id, ipaddress AS address FROM testhosts
                       WHERE __network_id = netId
                       UNION
                       SELECT
                           all_32host_children.__id      AS id,
                           all_32host_children.ipaddress AS address
                       FROM
                           testhosts AS all_32host_children
                           JOIN
                           (
                               SELECT id, address FROM all_net_children AS t1
                               WHERE NOT EXISTS(
                                   SELECT address FROM all_net_children AS t2
                                   WHERE t2.address >> t1.address)
                                     AND masklen(address) = 32
                           ) AS all_32net_children
                               ON __network_id = all_32net_children.id
                       ORDER BY address ASC
                   ) AS net_children);
END;
$$
LANGUAGE plpgsql;

-- ==========production

DROP FUNCTION host_children(int);
SELECT 'production', host_children(4621);
SELECT 'production', address, host_children(__id), net_children(__id) FROM network.networks ORDER BY address;

CREATE OR REPLACE FUNCTION host_children(IN netId INT, OUT hosts text) AS $$
BEGIN
    hosts := (SELECT string_agg(net_children.id::text, ',')
              FROM (
                       WITH all_net_children AS (
                           SELECT __id AS id, address AS address
                           FROM network.networks
                           WHERE address << (SELECT address
                                             FROM network.networks AS t1
                                             WHERE t1.__id = netId)
                       )
                       SELECT __id AS id, "ipAddress" AS address FROM equipment."dataPorts"
                       WHERE __network_id = netId
                       UNION
                       SELECT
                           all_32host_children.__id      AS id,
                           all_32host_children."ipAddress" AS address
                       FROM
                           equipment."dataPorts" AS all_32host_children
                           JOIN
                           (
                               SELECT id, address FROM all_net_children AS t1
                               WHERE NOT EXISTS(
                                   SELECT address FROM all_net_children AS t2
                                   WHERE t2.address >> t1.address)
                                     AND masklen(address) = 32
                           ) AS all_32net_children
                               ON __network_id = all_32net_children.id
                       ORDER BY address ASC
                   ) AS net_children);
END;
$$
LANGUAGE plpgsql;

-- =================
-- GET NET AND HOST CHILDREN (id) BY NETWORK ID

-- =============test
SELECT
  address,
  (
    SELECT string_agg(net_t1.id :: TEXT, ',')
    FROM (
           WITH all_net_children AS (
               SELECT __id AS id, address AS address
               FROM testnetworks
               WHERE address << net_table.address
           )
           SELECT id, address
           FROM all_net_children AS t1
           WHERE
             NOT EXISTS(
                 SELECT address FROM all_net_children AS t2
                 WHERE t2.address >> t1.address
             ) AND
             masklen(address) != 32
           ORDER BY t1.address
         ) AS net_t1
  ) AS net_children,
  (
    SELECT string_agg(host_t1.id :: TEXT, ',')
    FROM (
           WITH all_net_children AS (
               SELECT __id AS id, address AS address
               FROM testnetworks
               WHERE address << net_table.address
           )
           SELECT __id AS id, ipaddress AS address FROM testhosts
           WHERE __network_id = net_table.__id
           UNION
           SELECT
             all_32host_children.__id      AS id,
             all_32host_children.ipaddress AS address
           FROM
             testhosts AS all_32host_children
             JOIN
             (
               SELECT id, address FROM all_net_children AS t1
               WHERE NOT EXISTS(
                   SELECT address FROM all_net_children AS t2
                   WHERE t2.address >> t1.address)
                     AND masklen(address) = 32
             ) AS all_32net_children
               ON __network_id = all_32net_children.id
           ORDER BY address ASC
         ) AS host_t1
  ) AS host

FROM testnetworks AS net_table
  JOIN (SELECT unnest(ARRAY [1,11,12]) AS src_address) AS src_tb ON src_tb.src_address = net_table.__id;
-- function for test=====================
DROP FUNCTION network_info_test(ids INT [] );
SELECT * FROM network_info_test(ARRAY [1,11,12]);

CREATE OR REPLACE FUNCTION network_info_test(ids INT [])
  RETURNS TABLE(address INET, net_children text, host_children text) AS $$
BEGIN
  RETURN QUERY SELECT
                 net_table.address,
                 (
                   SELECT string_agg(net_t1.id :: TEXT, ',')
                   FROM (
                          WITH all_net_children AS (
                              SELECT n_table.__id AS id, n_table.address AS address
                              FROM testnetworks AS n_table
                              WHERE n_table.address << net_table.address
                          )
                          SELECT t1.id, t1.address
                          FROM all_net_children AS t1
                          WHERE
                            NOT EXISTS(
                                SELECT t1.address FROM all_net_children AS t2
                                WHERE t2.address >> t1.address
                            ) AND
                            masklen(t1.address) != 32
                          ORDER BY t1.address
                        ) AS net_t1
                 ) AS net_children,
                 (
                   SELECT string_agg(host_t1.id :: TEXT, ',')
                   FROM (
                          WITH all_net_children AS (
                              SELECT n_table2.__id AS id, n_table2.address AS address
                              FROM testnetworks AS n_table2
                              WHERE n_table2.address << net_table.address
                          )
                          SELECT h_table.__id AS id, h_table.ipaddress AS address FROM testhosts AS h_table
                          WHERE h_table.__network_id = net_table.__id
                          UNION
                          SELECT
                            all_32host_children.__id      AS id,
                            all_32host_children.ipaddress AS address
                          FROM
                            testhosts AS all_32host_children
                            JOIN
                            (
                              SELECT t1.id, t1.address FROM all_net_children AS t1
                              WHERE NOT EXISTS(
                                  SELECT t2.address FROM all_net_children AS t2
                                  WHERE t2.address >> t1.address)
                                    AND masklen(t1.address) = 32
                            ) AS all_32net_children
                              ON all_32host_children.__network_id = all_32net_children.id
                          ORDER BY address ASC
                        ) AS host_t1
                 ) AS host_children

               FROM testnetworks AS net_table
                 JOIN (SELECT unnest(ids) AS src_address) AS src_tb ON src_tb.src_address = net_table.__id;
END;
$$ LANGUAGE plpgsql;

-- ===production================
DROP FUNCTION network_info(ids INT [] );
SELECT * FROM network_info(ARRAY [4039,2995,3274,3146,4093,4094]);

CREATE OR REPLACE FUNCTION network_info(ids INT [])
  RETURNS TABLE(id int, address cidr, net_children text, host_children text) AS $$
BEGIN
  RETURN QUERY SELECT
                 net_table.__id AS id,
                 net_table.address,
                 (
                   SELECT string_agg(net_t1.id :: TEXT, ',')
                   FROM (
                          WITH all_net_children AS (
                              SELECT n_table.__id AS id, n_table.address AS address
                              FROM network.networks AS n_table
                              WHERE n_table.address << net_table.address
                          )
                          SELECT t1.id, t1.address
                          FROM all_net_children AS t1
                          WHERE
                            NOT EXISTS(
                                SELECT t1.address FROM all_net_children AS t2
                                WHERE t2.address >> t1.address
                            ) AND
                            masklen(t1.address) != 32
                          ORDER BY t1.address
                        ) AS net_t1
                 ) AS net_children,
                 (
                   SELECT string_agg(host_t1.id :: TEXT, ',')
                   FROM (
                          WITH all_net_children AS (
                              SELECT n_table2.__id AS id, n_table2.address AS address
                              FROM network.networks AS n_table2
                              WHERE n_table2.address << net_table.address
                          )
                          SELECT h_table.__id AS id, h_table."ipAddress" AS address FROM equipment."dataPorts" AS h_table
                          WHERE h_table.__network_id = net_table.__id
                          UNION
                          SELECT
                            all_32host_children.__id      AS id,
                            all_32host_children."ipAddress" AS address
                          FROM
                            equipment."dataPorts" AS all_32host_children
                            JOIN
                            (
                              SELECT t1.id, t1.address FROM all_net_children AS t1
                              WHERE NOT EXISTS(
                                  SELECT t2.address FROM all_net_children AS t2
                                  WHERE t2.address >> t1.address)
                                    AND masklen(t1.address) = 32
                            ) AS all_32net_children
                              ON all_32host_children.__network_id = all_32net_children.id
                          ORDER BY address ASC
                        ) AS host_t1
                 ) AS host_children

               FROM network.networks AS net_table
                 JOIN (SELECT unnest(ids) AS src_address) AS src_tb ON src_tb.src_address = net_table.__id;
END;
$$ LANGUAGE plpgsql;



