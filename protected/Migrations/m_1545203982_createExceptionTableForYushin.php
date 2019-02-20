<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1545203982_createExceptionTableForYushin
    extends Migration
{
    
    public function up()
    {
        $sql['create table'] = '
            CREATE TABLE mapping.exceptions (
              __id SERIAL,
              "serial" citext,
              "comment" citext,
              PRIMARY KEY (__id)
            )
        ';
        $sql['rights for Yushin'] = 'GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON mapping.exceptions TO yushin_an';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
    public function down()
    {
        $sql['drop table'] = 'DROP TABLE IF EXISTS mapping.exceptions';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}