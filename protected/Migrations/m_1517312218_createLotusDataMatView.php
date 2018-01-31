<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1517312218_createLotusDataMatView
    extends Migration
{

    public function up()
    {
        $sql['drop_old'] = 'DROP MATERIALIZED VIEW IF EXISTS view.lotus_db_data';
        $sql['lotus_db_data'] =
            'CREATE MATERIALIZED VIEW view.lotus_db_data AS
                SELECT *, now() AS last_refresh FROM lotus.locations';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

    }

    public function down()
    {
        $sql['drop_old'] = 'DROP MATERIALIZED VIEW IF EXISTS view.lotus_db_data';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

    }
    
}