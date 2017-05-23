<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1495547785_alterModuleItem
    extends Migration
{

    public function up()
    {
        $sql['equipment.moduleItems1'] = 'ALTER TABLE equipment."moduleItems" ADD COLUMN "using"  BOOLEAN DEFAULT TRUE';
        $sql['equipment.moduleItems2'] = 'ALTER TABLE equipment."moduleItems" ADD COLUMN "removed"  BOOLEAN DEFAULT FALSE ';
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
        $sql['equipment.moduleItems1'] = 'ALTER TABLE equipment."moduleItems" DROP COLUMN "using"';
        $sql['equipment.moduleItems2'] = 'ALTER TABLE equipment."moduleItems" DROP COLUMN "removed"';
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