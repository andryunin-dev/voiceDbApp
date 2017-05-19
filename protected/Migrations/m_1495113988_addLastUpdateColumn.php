<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1495113988_addLastUpdateColumn
    extends Migration
{

    /**
     * add column lastUpdate to Appliance and ModuleItem tables
     */
    public function up()
    {
        $sql['equipment.appliances'] = 'ALTER TABLE equipment."appliances" ADD COLUMN "lastUpdate"  TIMESTAMP WITH TIME ZONE';
        $sql['equipment.moduleItems'] = 'ALTER TABLE equipment."moduleItems" ADD COLUMN "lastUpdate"  TIMESTAMP WITH TIME ZONE';

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
        $sql['equipment.appliances'] = 'ALTER TABLE equipment."appliances" DROP COLUMN "lastUpdate"';
        $sql['equipment.moduleItems'] = 'ALTER TABLE equipment."moduleItems" DROP COLUMN "lastUpdate"';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: Table ' . $key . ' is rolled back' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: Table ' . $key . ' is rolled back' . PHP_EOL;
            }
        }
    }
    
}