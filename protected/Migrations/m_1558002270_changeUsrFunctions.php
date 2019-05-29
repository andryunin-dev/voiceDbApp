<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1558002270_changeUsrFunctions
    extends Migration
{

    public function up()
    {
        $sql['drop old function usr_network_locations_json'] =
            'drop function if exists usr_network_locations_json(bigint, integer, out jsonb)';
        $sql['drop old function usr_network_locations_string'] =
            'drop function if exists usr_network_locations_string(bigint, integer, varchar, out citext)';
        $sql['fix age json'] = '
        create function usr_network_locations_json(netid bigint, maxageinhours integer DEFAULT NULL::integer, OUT locations jsonb)
            returns jsonb
            language plpgsql
            as $$
            BEGIN
              IF maxAgeInHours ISNULL THEN
                locations := (
                             SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                             FROM company.offices AS offices
                                    JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                    JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                    JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                             WHERE net.__id = netId
                             );
              ELSE
                locations := (
                             SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                             FROM company.offices AS offices
                                    JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                    JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                    JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                             WHERE net.__id = netId AND (EXTRACT(EPOCH FROM age(now(), equipment."dataPorts"."lastUpdate"))/3600)::int <= maxAgeInHours
                             );
              END IF;
            end
            $$
        ';
        $sql['fix age string'] = '
        create function usr_network_locations_string(netid bigint, maxageinhours integer DEFAULT NULL, delimiter character varying DEFAULT \',\'::character varying, OUT locations citext)
          returns citext
        language plpgsql
        as $$
        BEGIN
                      IF maxAgeInHours ISNULL THEN
                        locations := (
                                     SELECT string_agg(DISTINCT offices.title::citext, delimiter) FILTER (WHERE offices.__id NOTNULL)
                                     FROM company.offices AS offices
                                            JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                            JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                            JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                                      WHERE net.__id = netId
                        );
                      ELSE
                        locations := (
                                     SELECT string_agg(DISTINCT offices.title::citext, delimiter) FILTER (WHERE offices.__id NOTNULL)
                                     FROM company.offices AS offices
                                            JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                            JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                            JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                                     WHERE net.__id = netId AND (EXTRACT(EPOCH FROM age(now(), equipment."dataPorts"."lastUpdate"))/3600)::int <= maxAgeInHours
                                     );
                      END IF;
                    end;
        $$
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
        $sql['drop function usr_network_locations_json'] =
            'drop function if exists usr_network_locations_json(bigint, integer, out jsonb)';
        $sql['drop function usr_network_locations_string'] =
            'drop function if exists usr_network_locations_string(bigint, integer, varchar, out citext)';
        $sql['restore old function usr_network_locations_json'] = '
        create function usr_network_locations_json(netid bigint, maxageinhours integer DEFAULT NULL::integer, OUT locations jsonb)
          returns jsonb
        language plpgsql
        as $$
        BEGIN
                      IF maxAgeInHours ISNULL THEN
                        locations := (
                                     SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                                     FROM company.offices AS offices
                                            JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                            JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                            JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                                     WHERE net.__id = netId
                                     );
                      ELSE
                        locations := (
                                     SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                                     FROM company.offices AS offices
                                            JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                            JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                            JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                                     WHERE net.__id = netId AND (EXTRACT(EPOCH FROM age(equipment."dataPorts"."lastUpdate"))/3600)::int <= maxAgeInHours
                                     );
                      END IF;
                    end;
        $$
        ';
        $sql['restore old function usr_network_locations_string'] = '
        create function usr_network_locations_string(netid bigint, maxageinhours integer, delimiter character varying DEFAULT \',\'::character varying, OUT locations citext)
          returns citext
        language plpgsql
        as $$
        BEGIN
                      IF maxAgeInHours ISNULL THEN
                        locations := (
                                     SELECT string_agg(DISTINCT offices.title::citext, delimiter) FILTER (WHERE offices.__id NOTNULL)
                                     FROM company.offices AS offices
                                            JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                            JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                            JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                                      WHERE net.__id = netId
                        );
                      ELSE
                        locations := (
                                     SELECT string_agg(DISTINCT offices.title::citext, delimiter) FILTER (WHERE offices.__id NOTNULL)
                                     FROM company.offices AS offices
                                            JOIN equipment.appliances ON offices.__id = appliances.__location_id
                                            JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                                            JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                                     WHERE net.__id = netId AND (EXTRACT(EPOCH FROM age(equipment."dataPorts"."lastUpdate"))/3600)::int <= maxAgeInHours
                                     );
                      END IF;
                    end;
        $$
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}