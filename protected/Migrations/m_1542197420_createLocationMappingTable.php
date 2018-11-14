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
            CREATE TABLE IF NOT EXISTS mapping.reg_centers (
              lotus_id INTEGER,
              reg_center citext,
              region citext,
              city citext,
              office citext,
              PRIMARY KEY (lotus_id)
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
        $sql['drop table'] = 'DROP TABLE IF EXISTS mapping.reg_centers';
        $sql['drop schema'] = 'DROP SCHEMA IF EXISTS mapping';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}