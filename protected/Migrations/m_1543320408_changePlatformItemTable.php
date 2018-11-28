<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1543320408_changePlatformItemTable
    extends Migration
{

    public function up()
    {
        $sql['equipment.platformItems__add column'] = 'ALTER TABLE equipment."platformItems" ADD COLUMN "serialNumberAlt" citext';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['equipment.platformItems__drop column'] = 'ALTER TABLE equipment."platformItems" DROP COLUMN "serialNumberAlt"';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
