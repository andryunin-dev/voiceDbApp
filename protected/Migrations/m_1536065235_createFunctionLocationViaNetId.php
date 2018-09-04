<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1536065235_createFunctionLocationViaNetId
    extends Migration
{
    public function up()
    {
        $sql['create function'] = '
        CREATE OR REPLACE FUNCTION network_locations(IN netId BIGINT, OUT locations jsonb) AS $$
            BEGIN
              locations := (
                SELECT json_object_agg(DISTINCT offices.__id, offices.title) FILTER (WHERE offices.__id NOTNULL)
                FROM company.offices AS offices
                  JOIN equipment.appliances ON offices.__id = appliances.__location_id
                  JOIN equipment."dataPorts" ON appliances.__id = "dataPorts".__appliance_id
                  JOIN network.networks AS net ON "dataPorts".__network_id = net.__id
                WHERE net.__id = netId
                );
            end;
            $$
            LANGUAGE plpgsql';


        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop function'] = 'DROP FUNCTION IF EXISTS network_locations(bigint)';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}