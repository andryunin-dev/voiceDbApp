-- GET ROOT NETWORKS AND HOSTS
-- ==========test
WITH all_roots AS (
    SELECT __id, address FROM testnetworks AS net1 WHERE
      NOT EXISTS(SELECT address from testnetworks AS net2 WHERE net2.address >> net1.address)
    ORDER BY address
)
SELECT
  (SELECT string_agg(all_roots.address ::text, ',') FROM  all_roots
  WHERE masklen(address) != 32) AS "rootNetId",
  (SELECT string_agg(all_roots.address ::text, ',') FROM  all_roots
  WHERE masklen(address) = 32)  AS "rootHostId";

DROP FUNCTION test_root_ids();
CREATE OR REPLACE FUNCTION test_root_ids() RETURNS TABLE("rootNetId" text, "rootHostId" text) AS $$
BEGIN
  RETURN QUERY WITH all_roots AS (
      SELECT __id, address FROM testnetworks AS net1 WHERE
        NOT EXISTS(SELECT address from testnetworks AS net2 WHERE net2.address >> net1.address)
      ORDER BY address
  )
  SELECT
    (SELECT string_agg(all_roots.address ::text, ',') FROM  all_roots
    WHERE masklen(address) != 32) AS "rootNetId",
    (SELECT string_agg(all_roots.address ::text, ',') FROM  all_roots
    WHERE masklen(address) = 32)  AS "rootHostId";
END
$$ LANGUAGE plpgsql;

SELECT * FROM test_root_ids();


-- ===========production
EXPLAIN ANALYSE
WITH all_roots AS (
    SELECT __id, address FROM network.networks AS net1 WHERE
      NOT EXISTS(SELECT address from network.networks AS net2 WHERE net2.address >> net1.address)
    ORDER BY address
)
SELECT
  (SELECT string_agg(all_roots.address::text, ',') FROM  all_roots
  WHERE masklen(address) !=32) AS "rootNetId",
  (SELECT string_agg(all_roots.address::text, ',') FROM  all_roots
  WHERE masklen(address) =32) AS "rootHostId";

DROP FUNCTION root_ids_string();
CREATE OR REPLACE FUNCTION root_ids_string() RETURNS TABLE("rootNetId" text, "rootHostId" text) AS $$
BEGIN
  RETURN QUERY WITH all_roots AS (
      SELECT __id, address FROM network.networks AS net1 WHERE
        NOT EXISTS(SELECT address from network.networks AS net2 WHERE net2.address >> net1.address)
      ORDER BY address
  )
  SELECT
    (SELECT string_agg(all_roots.address::text, ',') FROM  all_roots
    WHERE masklen(address) !=32) AS "rootNetId",
    (SELECT string_agg(all_roots.address::text, ',') FROM  all_roots
    WHERE masklen(address) =32) AS "rootHostId";
END
$$ LANGUAGE plpgsql;

SELECT * FROM root_ids_string();

EXPLAIN ANALYSE
WITH all_roots AS (
    SELECT __id, address FROM network.networks AS net1 WHERE
      NOT EXISTS(SELECT address from network.networks AS net2 WHERE net2.address >> net1.address)
    ORDER BY address
)
SELECT
  array(SELECT address FROM  all_roots
  WHERE masklen(address) !=32) AS "rootNetId",
  array(SELECT address FROM  all_roots
  WHERE masklen(address) =32) AS "rootHostId";

DROP FUNCTION root_ids_array();
CREATE OR REPLACE FUNCTION root_ids_array() RETURNS TABLE("rootNetId" text, "rootHostId" text) AS $$
BEGIN
  RETURN QUERY WITH all_roots AS (
      SELECT __id, address FROM network.networks AS net1 WHERE
        NOT EXISTS(SELECT address from network.networks AS net2 WHERE net2.address >> net1.address)
      ORDER BY address
  )
  SELECT
    (SELECT string_agg(all_roots.address::text, ',') FROM  all_roots
    WHERE masklen(address) !=32) AS "rootNetId",
    (SELECT string_agg(all_roots.address::text, ',') FROM  all_roots
    WHERE masklen(address) =32) AS "rootHostId";
END
$$ LANGUAGE plpgsql;

SELECT * FROM root_ids();

