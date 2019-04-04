<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1554101593_createApiView_NetworkView
    extends Migration
{

    public function up()
    {
        $sql['create api_view.networks'] =
            '
            CREATE VIEW api_view.networks as
            SELECT nets.__id      AS net_id,
                   nets.__vrf_id  AS vrf_id,
                   nets.address   AS net_ip,
                   nets.comment   AS net_comment,
                   vrfs.rd        AS vrf_rd,
                   vrfs.name      AS vrf_name,
                   vrfs.comment   AS vrf_comment
            FROM network.networks nets
                FULL JOIN network.vrfs vrfs ON nets.__vrf_id = vrfs.__id
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
        $sql['drop api_view.networks'] = 'DROP VIEW IF EXISTS api_view.networks';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}