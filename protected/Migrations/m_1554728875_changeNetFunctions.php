<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1554728875_changeNetFunctions
    extends Migration
{
    
    public function up()
    {
        $sql['drop root_ids func'] = 'DROP FUNCTION IF EXISTS root_ids()';
        $sql['create usr_root_ids func'] = '
            create function usr_root_ids()
              returns TABLE("netsIds" text, "hostsIds" text)
            language plpgsql
            as $$
            BEGIN
                 RETURN QUERY WITH all_roots AS (
                     SELECT __id AS id, address AS net_address FROM network.networks AS net_table WHERE
                       NOT EXISTS(SELECT address from network.networks AS net_table2 WHERE net_table2.address >> net_table.address)
                     ORDER BY address
                 )
                 SELECT
                   (SELECT string_agg(all_roots.id ::text, \',\') FROM  all_roots
                   WHERE masklen(net_address) != 32) AS "netId",
                   --   select all root hosts that have 32 mask
                   (
                     SELECT string_agg("rootHosts32".id::text, \',\') FROM
                       (
                         SELECT __id AS id, host_table."ipAddress" AS host_address FROM equipment."dataPorts" AS host_table
                           JOIN
                           (
                             SELECT * FROM all_roots
                             WHERE masklen(net_address) = 32
                           ) AS all_32_net_root
                             ON host_table.__network_id = all_32_net_root.id
                             ORDER BY net_address
                       ) AS "rootHosts32"
                   ) AS "hostId";
                  END
            $$;
        ';
        $sql['drop host_children'] = 'DROP FUNCTION IF EXISTS host_children(integer, out text)';
        $sql['create usr_host_children func'] = '
            create function usr_host_children(netid integer, OUT hosts text)
              returns text
            language plpgsql
            as $$
            BEGIN
              hosts := (SELECT string_agg(host_children.id::text, \',\')
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
            $$;
        ';
        
        $sql['drop net_children'] = "DROP FUNCTION IF EXISTS net_children(integer, out text)";
        $sql['create usr_net_children func'] = "
            create function usr_net_children(netid integer, OUT nets text)
              returns text
            language plpgsql
            as $$
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
        ";
        
        $sql['drop network.ip_path'] = "DROP FUNCTION IF EXISTS network.ip_path(inet, text, out text)";
        $sql['drop ip_path'] = "DROP FUNCTION IF EXISTS ip_path(inet, text, out text)";
        $sql['create usr_ip_path func'] = "
            create function usr_ip_path(ip inet, rec_type text, OUT path text)
              returns text
            language plpgsql
            as $$
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
            $$;
        ";
        
        $sql['drop network_locations'] = "DROP FUNCTION IF EXISTS network_locations(bigint, out jsonb)";
        $sql['create usr_network_locations_json'] = "
            CREATE OR REPLACE FUNCTION usr_network_locations_json(IN netId BIGINT, IN maxAgeInHours INT DEFAULT NULL, OUT locations jsonb) AS $$
            BEGIN
              IF maxAgeInHours ISNULL THEN
                locations := (
                             SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                             FROM company.offices AS offices
                                    JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                    JOIN equipment.\"dataPorts\" ON appliances.__id = \"dataPorts\".__appliance_id
                                    JOIN network.networks AS net ON \"dataPorts\".__network_id = net.__id
                             WHERE net.__id = netId
                             );
              ELSE
                locations := (
                             SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                             FROM company.offices AS offices
                                    JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                    JOIN equipment.\"dataPorts\" ON appliances.__id = \"dataPorts\".__appliance_id
                                    JOIN network.networks AS net ON \"dataPorts\".__network_id = net.__id
                             WHERE net.__id = netId AND (EXTRACT(EPOCH FROM age(equipment.\"dataPorts\".\"lastUpdate\"))/3600)::int <= maxAgeInHours
                             );
              END IF;
            end;
            $$
            LANGUAGE plpgsql
                    ";
        
        $sql['create usr_network_locations_string'] = "
            CREATE OR REPLACE FUNCTION usr_network_locations_string(IN netId BIGINT, IN maxAgeInHours INT, IN delimiter varchar DEFAULT ',', OUT locations citext) AS $$
            BEGIN
              IF maxAgeInHours ISNULL THEN
                locations := (
                             SELECT string_agg(DISTINCT offices.title::citext, delimiter) FILTER (WHERE offices.__id NOTNULL)
                             FROM company.offices AS offices
                                    JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                    JOIN equipment.\"dataPorts\" ON appliances.__id = \"dataPorts\".__appliance_id
                                    JOIN network.networks AS net ON \"dataPorts\".__network_id = net.__id
                              WHERE net.__id = netId
                );
              ELSE
                locations := (
                             SELECT string_agg(DISTINCT offices.title::citext, delimiter) FILTER (WHERE offices.__id NOTNULL)
                             FROM company.offices AS offices
                                    JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                    JOIN equipment.\"dataPorts\" ON appliances.__id = \"dataPorts\".__appliance_id
                                    JOIN network.networks AS net ON \"dataPorts\".__network_id = net.__id
                             WHERE net.__id = netId AND (EXTRACT(EPOCH FROM age(equipment.\"dataPorts\".\"lastUpdate\"))/3600)::int <= maxAgeInHours
                             );
              END IF;
            end;
            $$
            LANGUAGE plpgsql
        ";
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
    public function down()
    {
        $sql['drop usr_root_ids func'] = 'DROP FUNCTION IF EXISTS usr_root_ids()';
        $sql['create root_ids func'] = '
            create function root_ids()
              returns TABLE("netsIds" text, "hostsIds" text)
            language plpgsql
            as $$
            BEGIN
                 RETURN QUERY WITH all_roots AS (
                     SELECT __id AS id, address AS net_address FROM network.networks AS net_table WHERE
                       NOT EXISTS(SELECT address from network.networks AS net_table2 WHERE net_table2.address >> net_table.address)
                     ORDER BY address
                 )
                 SELECT
                   (SELECT string_agg(all_roots.id ::text, \',\') FROM  all_roots
                   WHERE masklen(net_address) != 32) AS "netId",
                   --   select all root hosts that have 32 mask
                   (
                     SELECT string_agg("rootHosts32".id::text, \',\') FROM
                       (
                         SELECT __id AS id, host_table."ipAddress" AS host_address FROM equipment."dataPorts" AS host_table
                           JOIN
                           (
                             SELECT * FROM all_roots
                             WHERE masklen(net_address) = 32
                           ) AS all_32_net_root
                             ON host_table.__network_id = all_32_net_root.id
                             ORDER BY net_address
                       ) AS "rootHosts32"
                   ) AS "hostId";
                  END
            $$;
        ';
        
        $sql['drop usr_host_children'] = 'DROP FUNCTION IF EXISTS usr_host_children(integer, out text)';
        $sql['create host_children func'] = '
            create function host_children(netid integer, OUT hosts text)
              returns text
            language plpgsql
            as $$
            BEGIN
              hosts := (SELECT string_agg(host_children.id::text, \',\')
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
            $$;
        ';
        
        $sql['drop net_children'] = "DROP FUNCTION IF EXISTS usr_net_children(integer, out text)";
        $sql['create net_children func'] = "
            create function net_children(netid integer, OUT nets text)
              returns text
            language plpgsql
            as $$
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
        ";
        
        $sql['drop usr_ip_path'] = "DROP FUNCTION IF EXISTS usr_ip_path(inet, text, out text)";
        $sql['create ip_path func'] = "
            create function ip_path(ip inet, rec_type text, OUT path text)
              returns text
            language plpgsql
            as $$
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
            $$;
        ";
        
        $sql['drop usr_network_locations_json'] = "DROP FUNCTION IF EXISTS usr_network_locations_json(bigint, integer, out jsonb)";
        $sql['drop usr_network_locations_string'] = "DROP FUNCTION IF EXISTS usr_network_locations_string(bigint, integer, varchar, out citext)";
        $sql['create network_locations'] = "
            create function network_locations(netid bigint, OUT locations jsonb)
              returns jsonb
            language plpgsql
            as $$
            BEGIN
                locations := (
                    SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                    FROM company.offices AS offices
                      JOIN equipment.appliances ON offices.__id = appliances.__location_id
                      JOIN equipment.\"dataPorts\" ON appliances.__id = \"dataPorts\".__appliance_id
                      JOIN network.networks AS net ON \"dataPorts\".__network_id = net.__id
                    WHERE net.__id = netId
                    );
                end;
            $$
        ";
        
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}