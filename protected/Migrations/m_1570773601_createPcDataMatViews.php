<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1570773601_createPcDataMatViews
    extends Migration
{

    public function up()
    {
        $sql['create mat view pc__device'] = 'CREATE MATERIALIZED VIEW view.pc__device AS SELECT *, now() AS last_refresh FROM pc.device';
        $sql['create mat view pc__ip_mac'] = 'CREATE MATERIALIZED VIEW view.pc__ip_mac AS SELECT *, now() AS last_refresh FROM pc.ip_mac';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop mat view pc_ip_mac'] = 'DROP MATERIALIZED VIEW IF EXISTS view.pc__ip_mac';
        $sql['drop mat view pc_device'] = 'DROP MATERIALIZED VIEW IF EXISTS view.pc__device';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
