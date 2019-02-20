<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542263432_createMapTable1CtoLocations
    extends Migration
{
    public function up()
    {
        $sql['create table'] = '
            CREATE TABLE IF NOT EXISTS mapping."location1C_to_lotusId" (
              "flatCode" BIGINT,
              lotus_id INTEGER,
              "flatAddress" citext,
              PRIMARY KEY ("flatCode")
            )
        ';
        $sql['rights for Yushin 1'] = 'GRANT CONNECT ON DATABASE "phpVDB" TO yushin_an';
        $sql['rights for Yushin 2'] = 'GRANT USAGE ON SCHEMA mapping TO yushin_an';
        $sql['rights for Yushin 3'] = 'GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON mapping."location1C_to_lotusId" TO yushin_an';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
    public function down()
    {
        $sql['drop table'] = 'DROP TABLE IF EXISTS mapping."location1C_to_lotusId"';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}