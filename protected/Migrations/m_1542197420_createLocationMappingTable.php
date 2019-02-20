<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542197420_createLocationMappingTable
    extends Migration
{
    
    public function up()
    {
        $sql['create schema'] = 'CREATE SCHEMA IF NOT EXISTS mapping';
        $sql['create table'] = '
            CREATE TABLE IF NOT EXISTS mapping."lotusLocations" (
              lotus_id INTEGER,
              reg_center citext,
              region citext,
              city citext,
              office citext,
              PRIMARY KEY (lotus_id)
            )
        ';
        $sql['rights for Yushin 1'] = 'GRANT CONNECT ON DATABASE "phpVDB" TO yushin_an';
        $sql['rights for Yushin 2'] = 'GRANT USAGE ON SCHEMA mapping TO yushin_an';
        $sql['rights for Yushin 3'] = 'GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON mapping."lotusLocations" TO yushin_an';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
    public function down()
    {
        $sql['drop table'] = 'DROP TABLE IF EXISTS mapping."lotusLocations"';
        $sql['drop schema'] = 'DROP SCHEMA IF EXISTS mapping';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}