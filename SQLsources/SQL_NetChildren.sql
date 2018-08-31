-- GET NETWORK CHILDREN BY ID
SELECT string_agg(net_children.id::TEXT, ',')
FROM (
       WITH all_net_children AS (
           SELECT
             n_table.__id    AS id,
             n_table.address AS address
           FROM testnetworks AS n_table
           WHERE n_table.address << (SELECT address
                                     FROM testnetworks AS n_table2
                                     WHERE n_table2.__id = 1)
       )
       SELECT t1.id AS id
       FROM all_net_children AS t1
       WHERE
         NOT EXISTS(
             SELECT t1.address
             FROM all_net_children AS t2
             WHERE t2.address >> t1.address
         ) AND
         masklen(t1.address) != 32
       ORDER BY t1.address
     ) AS net_children;


DROP FUNCTION IF EXISTS test_net_children(int);
SELECT 'test', test_net_children(1);

CREATE OR REPLACE FUNCTION test_net_children(IN netId INT, OUT nets text) AS $$
BEGIN
  nets := (SELECT string_agg(net_children.id::TEXT, ',')
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
                  SELECT t1.id AS id
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
EXPLAIN ANALYSE
SELECT string_agg(net_children.id::TEXT, ',')
FROM (
       WITH all_net_children AS (
           SELECT
             t0.__id    AS id,
             t0.address AS address
           FROM network.networks AS t0
           WHERE t0.address << (SELECT address
                                FROM network.networks AS t1
                                WHERE t1.__id = 4261)
       )
       SELECT t0.id AS id
       FROM all_net_children AS t0
       WHERE
         NOT EXISTS(
             SELECT t0.address
             FROM all_net_children AS t1
             WHERE t1.address >> t0.address
         ) AND
         masklen(t0.address) != 32
       ORDER BY t0.address
     ) AS net_children;



DROP FUNCTION IF EXISTS net_children(int);
SELECT address, net_children(__id) FROM network.networks WHERE __id =  4261;
SELECT address, net_children(__id) FROM network.networks LIMIT 1000;

CREATE OR REPLACE FUNCTION net_children(IN netId INT, OUT nets text) AS $$
BEGIN
  nets := (SELECT string_agg(net_children.id::TEXT, ',')
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
                  SELECT t0.id AS id
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
