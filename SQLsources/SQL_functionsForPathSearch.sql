-- ===========find path by ip and rec_type ('host' | 'net)
DROP FUNCTION IF EXISTS network.ip_path(inet, text, OUT text);
CREATE OR REPLACE FUNCTION network.ip_path(IN ip inet, IN rec_type text, OUT path text) AS $$
BEGIN
  IF rec_type = 'network' THEN
    path := (SELECT string_agg(t2.id::citext, ',')
             FROM (
                  SELECT t1.__id id, t1.address ip
                  FROM network.networks t1
                  WHERE ip << t1.address
                  ORDER BY t1.address
                  ) t2
            );
  ELSEIF rec_type = 'host' THEN
      path := (SELECT string_agg(t2.id::citext, ',')
               FROM (
                    SELECT t1.__id id, t1.address ip
                    FROM network.networks t1
                    WHERE ip <<= t1.address
                    ORDER BY t1.address
                    ) t2
              );
  ELSE path := NULL ;
  END IF;
END;
$$
LANGUAGE plpgsql;


-- Query to find net's and host's paths by ip using params
SELECT t1.address ip, network.ip_path(t1.address::inet, 'network') path, 'network' rec_type
FROM network.networks t1 WHERE t1.address::citext LIKE :ip
UNION
SELECT t1."ipAddress" ip, network.ip_path(t1."ipAddress"::inet, 'host') path, 'host' rec_type
FROM equipment."dataPorts" t1 WHERE t1."ipAddress"::citext LIKE :ip
ORDER BY ip;

-- Query to find net's and host's paths by ip
SELECT t1.address ip, network.ip_path(t1.address::inet, 'network') path, 'net' rec_type
FROM network.networks t1 WHERE t1.address::citext LIKE '10.1.3.%'
UNION
SELECT t1."ipAddress" ip, network.ip_path(t1."ipAddress"::inet, 'host') path, 'host' rec_type
FROM equipment."dataPorts" t1 WHERE t1."ipAddress"::citext LIKE '10.1.3.%'
ORDER BY ip;