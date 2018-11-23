<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542965849_createNomenclatureMapTable
    extends Migration
{
    public function up()
    {
        $sql['create table'] = '
        CREATE TABLE mapping.nomenclature (
          nomenclature_id citext,
          nomenclature citext,
          platform_id bigint,
          platform citext
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
        $sql['drop table'] = 'DROP TABLE IF EXISTS mapping.nomenclature';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}