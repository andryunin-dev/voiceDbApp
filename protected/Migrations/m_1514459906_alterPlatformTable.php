<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1514459906_alterPlatformTable
    extends Migration
{

    public function up()
    {
        $sql['alter_table_equipment.platforms_add_column'] = '
            ALTER TABLE equipment."platforms"
                ADD COLUMN "isHW"  BOOLEAN NOT NULL DEFAULT TRUE';

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
        $sql['alter_table_equipment.platforms_drop_column'] = '
            ALTER TABLE equipment."platforms"
                DROP COLUMN "isHW"';

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