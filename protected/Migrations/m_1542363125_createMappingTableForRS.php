<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542363125_createMappingTableForRS
    extends Migration
{
    public function up()
    {
        $sql['create table'] = '
            CREATE TABLE mapping.routers_switches (
              __id SERIAL,
              "oneC_name" citext,
              "db_name" citext,
              PRIMARY KEY (__id)
            )
        ';
        $sql['rights for Yushin 3'] = 'GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON mapping.routers_switches TO yushin_an';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
    public function down()
    {
        $sql['drop table'] = 'DROP TABLE IF EXISTS mapping.routers_switches';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }}