<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1536138223_changeRootIdsFunction
    extends Migration
{

    public function up()
    {
        $sql['drop old function 1'] = 'DROP FUNCTION IF EXISTS root_ids()';

        // add order by address for hosts
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
                          ORDER BY net_address
                    ) AS "rootHosts32"
                ) AS "hostId";
            END
            $$ LANGUAGE plpgsql
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
        $sql['drop old function 1'] = 'DROP FUNCTION IF EXISTS root_ids()';

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
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}