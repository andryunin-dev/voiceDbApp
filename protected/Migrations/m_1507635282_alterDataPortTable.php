<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1507635282_alterDataPortTable
    extends Migration
{

    public function up()
    {
        $sql['alter_table_equipment.dataPorts_add_column'] = '
            ALTER TABLE equipment."dataPorts"
                ADD COLUMN "lastUpdate"  TIMESTAMP WITH TIME ZONE';

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
        $sql['alter_table_equipment.dataPorts_drop_column'] = '
            ALTER TABLE equipment."dataPorts"
                DROP COLUMN "lastUpdate"';

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
