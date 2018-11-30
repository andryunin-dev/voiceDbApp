<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1543490896_changeNomenclatureMappingTable
    extends Migration
{

    public function up()
    {
        $sql['create table'] = 'ALTER TABLE mapping.nomenclature ADD COLUMN "listNumber" INT';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop table'] = 'ALTER TABLE mapping.nomenclature DROP COLUMN "listNumber"';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}