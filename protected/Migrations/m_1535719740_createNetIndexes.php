<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1535719740_createNetIndexes
    extends Migration
{

    public function up()
    {
        $sql['create gist net index'] = 'CREATE INDEX IF NOT EXISTS idx_network_gist_address ON network.networks USING GIST (address inet_ops)';
        $sql['create btree net index'] = 'CREATE INDEX IF NOT EXISTS idx_network_btree_address ON network.networks (address)';
        $sql['create netmask index'] = 'CREATE INDEX IF NOT EXISTS idx_network_btree_masklen ON network.networks (masklen(address))';
        $sql['VACUUM ANALYSE'] = 'VACUUM ANALYSE';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop netmask index'] = 'DROP INDEX IF EXISTS network.idx_network_btree_masklen';
        $sql['drop btree net index'] = 'DROP INDEX IF EXISTS network.idx_network_btree_address';
        $sql['drop gist net index'] = 'DROP INDEX IF EXISTS network.idx_network_gist_address';


        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}