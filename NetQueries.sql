-- get hosts for subnet
SELECT h_address FROM testhosts WHERE network(h_address) = '10.0.0.0/16';
-- =========================================
-- GET FULL PATH to root subnetwork
-- host address
SELECT net_address FROM testnetworks WHERE
  net_address >>= network('10.0.0.1/16') AND
  net_address != '10.0.0.1/16'
ORDER BY net_address;

-- subnet address
SELECT net_address FROM testnetworks WHERE
  net_address >>= network('10.0.0.0/16') AND
  net_address != '10.0.0.0/16'
ORDER BY net_address;

-- concatenated into string
-- example with host address
SELECT string_agg(t.net_address::text, ',') AS children FROM (
  SELECT net_address FROM testnetworks WHERE
    net_address >>= network('10.0.0.1/16') AND
    net_address != '10.0.0.1/16'
  ORDER BY net_address
) AS t;

-- example with subnet address
SELECT string_agg(t.net_address::text, ',') AS children FROM (
  SELECT net_address FROM testnetworks WHERE
    net_address >>= network('10.0.0.0/16') AND
    net_address != '10.0.0.0/16'
  ORDER BY net_address
) AS t;

-- ===================================================
-- GET DIRECT PARENT

SELECT max(net_address) FROM testnetworks WHERE
  net_address >>= '10.0.0.0/16' AND
  net_address != '10.0.0.0/16';

SELECT max(net_address) FROM testnetworks WHERE
  net_address >>= '10.0.0.1/16' AND
  net_address != '10.0.0.1/16';

-- get all root subnetworks
SELECT net_address FROM testnetworks AS net1 WHERE
    NOT EXISTS(SELECT net_address from testnetworks AS net2 WHERE net2.net_address >> net1.net_address)
ORDER BY net_address;



-- ========================================
-- select direct descendants for one address
SELECT __id, net_address,
  (
    SELECT string_agg(t_net.__id::text, ',') FROM (
                                                    WITH all_children AS (
                                                        SELECT __id, net_address FROM testnetworks WHERE
                                                          net_address << t0.net_address
                                                    )
                                                    SELECT __id, net_address FROM all_children AS t1 WHERE
                                                      NOT EXISTS(SELECT net_address FROM all_children AS t2 WHERE t2.net_address >> t1.net_address)
                                                    ORDER BY net_address
                                                  ) AS t_net
  ) AS net_children,
  (
    SELECT string_agg(host_t.__id::text, ',')
    FROM (
           SELECT __id, h_address, abbrev("h_address")::text, abbrev("h_address") || '/' || coalesce(maskl, 32) FROM
             (
               SELECT * FROM testhosts
               WHERE '10.3.0.0/24'::inet >>= "h_address"
             ) AS t
           WHERE (SELECT max(net_address) FROM testnetworks WHERE
             net_address >>= (abbrev("h_address") || '/' || coalesce(maskl, 32))::inet AND
             net_address != (abbrev("h_address") || '/' || coalesce(maskl, 32))::inet) = '10.3.0.0/24'::inet
         ) AS host_t
  ) AS host_children
FROM testnetworks AS t0
  INNER JOIN (
               -- select '11.0.0.0/8'::inet as src_address
               select '10.3.0.0/24'::inet as src_address
               -- union select '10.0.0.0/8'::inet
               -- union select '10.0.0.0/16'::inet
               -- union select '10.0.0.0/9'::inet
               --     union select '12.0.0.0/16'::inet
             ) as subtable ON subtable.src_address = t0.net_address;











WITH all_children AS (
    SELECT net_address FROM testnetworks WHERE
      net_address << '11.0.0.0/8'
)
SELECT net_address FROM all_children AS t1 WHERE
  NOT EXISTS(
      SELECT net_address FROM all_children AS t2 WHERE t2.net_address >> t1.net_address
  );

-- select addresses and its descendants concatenated in a string
SELECT __id, net_address, (
  SELECT string_agg(t.net_address::text, ',') AS children FROM (
    WITH all_children AS (
        SELECT net_address FROM testnetworks WHERE
          net_address << t0.net_address
    )
    SELECT net_address FROM all_children AS t1 WHERE
      NOT EXISTS(SELECT net_address FROM all_children AS t2 WHERE t2.net_address >> t1.net_address)
    ORDER BY net_address
  ) AS t
)
FROM testnetworks AS t0 WHERE net_address IN ('11.0.0.0/8', '10.0.0.0/8');





-- CHECK IT WITH HOST IP AND SUBNET IP!!!!!
-- select direct descendants for address
SELECT address FROM
(SELECT address FROM testip WHERE
    address << '11.0.0.0/8') AS t1 WHERE
    NOT EXISTS(SELECT address FROM (SELECT address FROM testip WHERE
        address << '11.0.0.0/8') as t2 WHERE t2.address >> t1.address) ORDER BY address;

-- get addresses and direct descendants that are concatenated into a string for all addresses
SELECT __id, address, (
    SELECT string_agg(t.address::text, ',') AS children FROM (
         SELECT address FROM
             (SELECT address FROM testip WHERE
                 address << t0.address) AS t1 WHERE
             NOT EXISTS(SELECT address FROM (SELECT address FROM testip WHERE
                 address << t0.address) as t2 WHERE t2.address >> t1.address) ORDER BY address
    ) AS t
)
FROM testip AS t0 WHERE address IN ('11.0.0.0/8', '10.0.0.0/8');

-- it's the same variant but using alternate method instead IN
SELECT __id, address, (
    SELECT string_agg(t.address::text, ',') AS children FROM (
                                                                 SELECT address FROM
                                                                     (SELECT address FROM testip WHERE
                                                                         address << t0.address) AS t1 WHERE
                                                                     NOT EXISTS(SELECT address FROM (SELECT address FROM testip WHERE
                                                                         address << t0.address) as t2 WHERE t2.address >> t1.address) ORDER BY address
                                                             ) AS t
)
FROM testip AS t0
    INNER JOIN (
                   select '11.0.0.0/8'::inet as src_address
                   union all select '10.0.0.0/8'::inet
               ) as subtable ON subtable.src_address = t0.address;

--variant for replace IN operator with better performance
SELECT address FROM testip
    INNER JOIN (
                   select '11.0.0.0/8'::inet as src_address
                   union all select '10.0.0.0/8'::inet
               ) as subtable ON subtable.src_address = testip.address;
--
SELECT string_agg(t.address::text, ',') FROM (SELECT address FROM testip WHERE
    address >> '10.0.0.0/16' ORDER BY address) AS t;


SELECT string_agg(t.address::text, ',') AS children FROM (
       SELECT address FROM
           (SELECT address FROM testip WHERE
               address << :address) AS t1 WHERE
           NOT EXISTS(SELECT address FROM (SELECT address FROM testip WHERE
               address << :address) as t2 WHERE t2.address >> t1.address) ORDER BY address
) AS t;

SELECT string_agg(t.address::text, ',') FROM (
       SELECT address FROM
           (SELECT address FROM testip WHERE
               address << :address) AS t1 WHERE
           NOT EXISTS(SELECT address FROM (SELECT address FROM testip WHERE
               address << :address) as t2 WHERE t2.address >> t1.address) ORDER BY address
) AS t;

SELECT address, (
    SELECT string_agg(t.address::text, ',') AS children FROM (
        SELECT address FROM
            (SELECT address FROM testip WHERE
                address << t0.address) AS t1 WHERE
                NOT EXISTS(SELECT address FROM (SELECT address FROM testip WHERE
                address << t0.address) as t2 WHERE t2.address >> t1.address) ORDER BY address
            ) AS t
)
FROM testip AS t0 WHERE address IN ('11.0.0.0/8', '10.0.0.0/8');


select '11.0.0.0/8' as bar
union all select '10.0.0.0/8';

--  test test
EXPLAIN ANALYSE
SELECT __id, address,
  (
    SELECT string_agg(t_net.__id::text, ',') FROM (
      WITH all_children AS (
          SELECT __id, address FROM network.networks WHERE
            address << t0.address
      )
      SELECT __id, address FROM all_children AS t1 WHERE
        NOT EXISTS(SELECT address FROM all_children AS t2 WHERE t2.address >> t1.address)
      ORDER BY address
    ) AS t_net
  ) AS net_children,
  (
    SELECT string_agg(host_t.__id::text, ',')
    FROM (
           SELECT __id FROM
             (
               SELECT * FROM equipment."dataPorts"
               WHERE t0.address >>= "ipAddress"
             ) AS t
           WHERE (SELECT max(address) FROM network.networks WHERE
             address >>= (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet AND
             address != (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet) = t0.address
         ) AS host_t
  ) AS host_children
FROM network.networks AS t0
INNER JOIN (
-- select '11.0.0.0/8'::inet as src_address
select '10.1.6.32/29'::inet as src_address
-- union select '10.0.0.0/8'::inet
-- union select '10.0.0.0/16'::inet
-- union select '10.0.0.0/9'::inet
--     union select '12.0.0.0/16'::inet
) as subtable ON subtable.src_address = t0.address;

SELECT __id, h_address, abbrev(h_address)::text, abbrev(h_address) || '/' || coalesce(maskl, 32) FROM testhosts
WHERE (abbrev(h_address) || '/' || coalesce(maskl, 32))::inet <<= '10.3.0.0/16'::inet;
-- ===================================
EXPLAIN ANALYSE
SELECT __id, h_address, abbrev(h_address)::text, abbrev(h_address) || '/' || coalesce(maskl, 32) FROM testhosts
WHERE (SELECT max(net_address) FROM testnetworks WHERE
  net_address >>= (abbrev(h_address) || '/' || coalesce(maskl, 32))::inet AND
  net_address != (abbrev(h_address) || '/' || coalesce(maskl, 32))::inet) = '10.3.0.0/24'::inet;

EXPLAIN ANALYSE
SELECT __id, h_address, abbrev(h_address)::text, abbrev(h_address) || '/' || coalesce(maskl, 32) FROM
  (
    SELECT * FROM testhosts
    WHERE '10.3.0.0/24'::inet >>= h_address
  ) AS t
WHERE (SELECT max(net_address) FROM testnetworks WHERE
  net_address >>= (abbrev(h_address) || '/' || coalesce(maskl, 32))::inet AND
  net_address != (abbrev(h_address) || '/' || coalesce(maskl, 32))::inet) = '10.3.0.0/24'::inet;
-- ===================================================================================
EXPLAIN ANALYSE
SELECT __id, "ipAddress", abbrev("ipAddress")::text, abbrev("ipAddress") || '/' || coalesce(masklen, 32) FROM equipment."dataPorts"
WHERE (SELECT max(address) FROM network.networks WHERE
  address >>= (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet AND
  address != (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet) = '10.1.6.32/29'::inet;

EXPLAIN ANALYSE
SELECT __id, "ipAddress", abbrev("ipAddress")::text, abbrev("ipAddress") || '/' || coalesce(masklen, 32) FROM
  (
    SELECT * FROM equipment."dataPorts"
    WHERE '10.1.6.32/29'::inet >>= "ipAddress"
  ) AS t
WHERE (SELECT max(address) FROM network.networks WHERE
  address >>= (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet AND
  address != (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet) = '10.1.6.32/29'::inet;

SELECT __id, "ipAddress", abbrev("ipAddress")::text, abbrev("ipAddress") || '/' || coalesce(masklen, 32) FROM
  (
    SELECT * FROM equipment."dataPorts"
    WHERE '10.1.6.0/24'::inet >>= "ipAddress"
  ) AS t
WHERE (SELECT max(address) FROM network.networks WHERE
  address >>= (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet AND
  address != (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet) = '10.1.6.0/24'::inet;

UPDATE equipment."dataPorts" SET masklen = NULL WHERE "ipAddress" = '10.1.6.35'::inet;
SELECT * FROM equipment."dataPorts" WHERE "ipAddress" = '10.1.6.35'::inet;


SELECT '10.0.0.0/16'::inet >>= '10.0.0.1/24'::inet;

SELECT max(net_address) FROM testnetworks WHERE
  net_address >>= '10.3.0.1/32' AND
  net_address != '10.3.0.1/32';

SELECT net_address FROM testnetworks WHERE
  net_address >>= network('10.3.0.1/32') AND
  net_address != '10.3.0.1/32'
ORDER BY net_address;






SELECT __id, address,
  (
    SELECT string_agg(t.__id::text, ',') FROM (
                                              WITH all_children AS (
                                                  SELECT __id, address FROM network.networks WHERE
                                                    address << t0.address
                                              )
                                              SELECT __id, address FROM all_children AS t1 WHERE
                                                NOT EXISTS(SELECT address FROM all_children AS t2 WHERE t2.address >> t1.address)
                                              ORDER BY address asc
                                            ) AS t
  ) AS net_children,
  (
    SELECT string_agg(host_t.__id::text, ',')
    FROM (
           SELECT __id, "ipAddress"
           FROM equipment."dataPorts"
           WHERE network("ipAddress") = t0.address
           ORDER BY "ipAddress" asc
         ) AS host_t
  ) AS host_children
FROM network.networks AS t0
  INNER JOIN (
               SELECT 3275 AS src_id
               UNION SELECT 4039
               UNION SELECT 2995
               UNION SELECT 3274
               UNION SELECT 3146
               UNION SELECT 4093
               UNION SELECT 4094
               UNION SELECT 3334
             ) as subtable ON subtable.src_id = t0.__id
SELECT * FROM network.networks WHERE address = '10.1.6.0/24'::inet

-- ===========================
-- ============================
EXPLAIN ANALYSE
SELECT __id, address,
  (
    SELECT string_agg(t_net.__id::text, ',') FROM (
                                                    WITH all_children AS (
                                                        SELECT __id, address FROM network.networks WHERE
                                                          address << t0.address
                                                    )
                                                    SELECT __id, address FROM all_children AS t1 WHERE
                                                      NOT EXISTS(SELECT address FROM all_children AS t2 WHERE t2.address >> t1.address)
                                                    ORDER BY address
                                                  ) AS t_net
  ) AS net_children,
  (
    SELECT string_agg(host_t.__id::text, ',')
    FROM (
           SELECT __id FROM
             (
               SELECT * FROM equipment."dataPorts"
               WHERE t0.address >>= "ipAddress"
             ) AS t
           WHERE (SELECT max(address)
                  FROM network.networks
                  WHERE
             address >>= (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet AND
             address != (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet) = t0.address
--              address >>= (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet AND
--              address != (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet) = t0.address
         ) AS host_t
  ) AS host_children
FROM network.networks AS t0
  INNER JOIN (
               -- select '11.0.0.0/8'::inet as src_address
               select '10.1.6.32/29'::inet as src_address
               union select '10.0.0.0/8'::inet
               union select '10.0.0.0/16'::inet
               union select '10.0.0.0/9'::inet
                   union select '12.0.0.0/16'::inet
             ) as subtable ON subtable.src_address = t0.address;
