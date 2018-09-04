<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1536067909_createNetworksView
    extends Migration
{
    public function up()
    {
        $sql['create view'] = '
        CREATE OR REPLACE VIEW view.networks AS
            SELECT
                   networks.__id AS "netId",
                   networks.address AS address,
                   netmask(networks.address) AS netmask,
                   networks.comment AS comment,
                   vrfs.__id AS "vrfId",
                   vrfs.name AS "vrfName",
                   vrfs.rd AS "vrfRd"
            FROM network.networks
              JOIN network.vrfs ON networks.__vrf_id = vrfs.__id';


        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop view'] = 'DROP VIEW IF EXISTS view.networks';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}