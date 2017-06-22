<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1498117622_addInUseToAppliance
    extends Migration
{

    public function up()
    {
        $sql['equipment.appliances'] = 'ALTER TABLE equipment."appliances" ADD COLUMN "inUse"  BOOLEAN DEFAULT TRUE';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }

    }

    public function down()
    {
        $sql['equipment.appliances'] = 'ALTER TABLE equipment."appliances" DROP COLUMN "inUse"';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }
    }
}