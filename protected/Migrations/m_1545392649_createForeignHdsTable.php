<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1545392649_createForeignHdsTable
    extends Migration
{

    public function up()
    {
        $sql['create_schema_hds'] = 'CREATE SCHEMA IF NOT EXISTS hds';
        $sql['create_table__hds.foreign_hds'] = '
            CREATE TABLE hds.foreign_hds (
                prefix citext,
                dn citext,
                "lastUpdate" TIMESTAMP
            )
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
        $sql['drop_table__hds.foreign_hds'] = 'DROP TABLE hds.foreign_hds';
        $sql['drop_schema_hds'] = 'DROP SCHEMA IF EXISTS hds';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
