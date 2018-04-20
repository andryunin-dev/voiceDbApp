UPDATE equipment."dataPorts" SET full_ip = (abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet;

CREATE INDEX idx_dport_full_ip
  ON equipment."dataPorts" ("full_ip");
CREATE INDEX idx_network_address
  ON network.networks (address);

-- CREATE GIST INDEXES
CREATE INDEX idx_dport_gist_full_ip
  ON equipment."dataPorts"  USING GIST (full_ip inet_ops);
CREATE INDEX idx_dport_gist_concat
  ON equipment."dataPorts"  USING GIST (((abbrev("ipAddress") || '/' || coalesce(masklen, 32))::inet) inet_ops);
CREATE INDEX idx_network_gist_address
  ON network.networks USING GIST (address inet_ops);

CREATE INDEX idx_net_host_gist
  ON equipment."dataPorts"  USING GIST (full_ip inet_ops)

DROP INDEX idx_net_netmask_host;
CREATE INDEX idx_net_netmask_host
  ON equipment."dataPorts" (netmask(full_ip));


DROP INDEX equipment.idx_dport_full_ip;
DROP INDEX equipment.idx_dport_gist_full_ip;
DROP INDEX equipment.idx_dport_gist_concat;

DROP INDEX network.idx_network_address;
DROP INDEX network.idx_network_gist_address;

EXPLAIN ANALYSE
SELECT __id, full_ip FROM
  (
    SELECT * FROM equipment."dataPorts"
    WHERE :net_address >>= full_ip
  ) AS t
WHERE (SELECT max(address) FROM network.networks WHERE
  address >>= full_ip AND
  address != full_ip) = :net_address

EXPLAIN ANALYSE
SELECT "ipAddress" FROM equipment."dataPorts" WHERE full_ip << '10.1.6.0/24'::inet;

EXPLAIN ANALYSE
SELECT * FROM equipment."dataPorts"
WHERE full_ip <<= :net_address

-- =============================
-- ============================
-- ============================
-- ========================FUNCTION get children info=============
DROP FUNCTION net(INT[]);
CREATE OR REPLACE FUNCTION net(idArray INT[]) RETURNS TABLE(id INT, address cidr, net_chldr text, host_chldr text) AS $$
BEGIN
  RETURN QUERY
  SELECT __id, t0.address,
    (
      SELECT string_agg(t_net.__id::text, ',') FROM
        (
          WITH all_children AS (
              SELECT __id, net_0.address FROM network.networks AS net_0 WHERE
                net_0.address << t0.address
          )
          SELECT __id, all_children.address FROM all_children WHERE
            NOT EXISTS(SELECT all_children_2.address FROM all_children AS all_children_2 WHERE all_children_2.address >> all_children.address)
          ORDER BY all_children.address
        ) AS t_net
    ) AS net_children,
    (
      SELECT string_agg(host_t.__id::text, ',')
      FROM (
             SELECT __id FROM
               (
                 SELECT * FROM equipment."dataPorts" AS dport
                 WHERE t0.address >>= dport."ipAddress"
               ) AS all_hosts
             WHERE (SELECT max(net_1.address)
                    FROM network.networks AS net_1
                    WHERE
                      net_1.address >>= (abbrev(all_hosts."ipAddress") || '/' || coalesce(all_hosts.masklen, 32))::inet AND
                      net_1.address != (abbrev(all_hosts."ipAddress") || '/' || coalesce(all_hosts.masklen, 32))::inet) = t0.address
           ) AS host_t
    ) AS host_children
  FROM network.networks AS t0
    INNER JOIN (
                 SELECT unnest(idArray) AS src_address
               ) as subtable ON subtable.src_address = t0.__id;
END
$$ LANGUAGE plpgsql;