<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1507007977_addOrderColumnToAppType
    extends Migration
{

    public function up()
    {
        $sql['addColumn'] = 'ALTER TABLE equipment."applianceTypes" ADD COLUMN "sortOrder" INTEGER DEFAULT 0';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' OK' . PHP_EOL;
            }
        }

    }

    public function down()
    {
        $sql['dropColumn'] = 'ALTER TABLE equipment."applianceTypes" DROP COLUMN "sortOrder"';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
    }
    
}