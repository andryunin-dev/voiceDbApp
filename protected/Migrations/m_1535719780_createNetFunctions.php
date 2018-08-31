<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1535719780_createNetFunctions
    extends Migration
{
    public function up()
    {
        $sql['drop old function 1'] = 'DROP FUNCTION IF EXISTS root_ids()';
        $sql['drop old function 2'] = 'DROP FUNCTION IF EXISTS net_children(int)';
        $sql['drop old function 3'] = 'DROP FUNCTION IF EXISTS host_children(int)';

        $sql['create root_ids function'] = '
        CREATE OR REPLACE FUNCTION root_ids() RETURNS TABLE("netsIds" text, "hostsIds" text) AS $$
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
                    ) AS "rootHosts32"
                ) AS "hostId";
            END
            $$ LANGUAGE plpgsql
        ';
        $sql['create net_children function'] = '
        CREATE OR REPLACE FUNCTION net_children(IN netId INT, OUT nets text) AS $$
            BEGIN
              nets := (SELECT string_agg(net_children.id::TEXT, \',\')
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
            LANGUAGE plpgsql
        ';
        $sql['create host_children function'] = '
        CREATE OR REPLACE FUNCTION host_children(IN netId INT, OUT hosts text) AS $$
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
            $$
            LANGUAGE plpgsql        
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop function 3'] = 'DROP FUNCTION IF EXISTS host_children(int)';
        $sql['drop function 2'] = 'DROP FUNCTION IF EXISTS net_children(int)';
        $sql['drop function 1'] = 'DROP FUNCTION IF EXISTS root_ids()';


        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }}