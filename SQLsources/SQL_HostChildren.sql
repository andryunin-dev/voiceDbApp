-- GET HOST CHILDREN BY ID
SELECT string_agg(host_children.id::text, ',')
FROM (
       WITH all_net_children AS (
           SELECT __id AS id, address AS address
           FROM testnetworks
           WHERE address << (SELECT address
                             FROM testnetworks AS t1
                             WHERE t1.__id = 1)
       )
       SELECT __id AS id, ipaddress AS address FROM testhosts
       WHERE __network_id = 1
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
     ) AS host_children;



DROP FUNCTION IF EXISTS test_host_children(int);
SELECT 'test', test_host_children(1);
SELECT 'test', address, test_host_children(__id), test_net_children(__id) FROM testnetworks ORDER BY address;

CREATE OR REPLACE FUNCTION test_host_children(IN netId INT, OUT hosts text) AS $$
BEGIN
  hosts := (SELECT string_agg(host_children.id::text, ',')
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
                 ) AS host_children);
END;
$$
LANGUAGE plpgsql;

-- ==========production
EXPLAIN ANALYSE
SELECT string_agg(host_children.id::text, ',')
FROM (
       WITH all_net_children AS (
           SELECT __id AS id, address AS address
           FROM network.networks
           WHERE address << (SELECT address
                             FROM network.networks AS t1
                             WHERE t1.__id = 2319)
       )
       SELECT __id AS id, "ipAddress" AS address FROM equipment."dataPorts"
       WHERE __network_id = 2319
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
     ) AS host_children;


DROP FUNCTION IF EXISTS host_children(int);
SELECT 'production', host_children(4621);
SELECT 'production', __id, address, host_children(__id), net_children(__id) FROM network.networks ORDER BY address;

CREATE OR REPLACE FUNCTION host_children(IN netId INT, OUT hosts text) AS $$
BEGIN
  hosts := (SELECT string_agg(host_children.id::text, ',')
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
                 ) AS host_children);
END;
$$
LANGUAGE plpgsql;
