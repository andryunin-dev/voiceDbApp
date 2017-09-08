<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1504863855_addIndexByPlatformSerial
    extends Migration
{

    public function up()
    {
        $sql['old'] = 'DROP INDEX IF EXISTS equipment.idx_platform_item_serial';
        $sql['idx_platform_item_serial'] = 'CREATE INDEX idx_platform_item_serial ON equipment."platformItems"("serialNumber");';

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
        $sql['idx_platform_item_serial'] = 'DROP INDEX equipment.idx_platform_item_serial';

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