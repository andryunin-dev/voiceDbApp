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