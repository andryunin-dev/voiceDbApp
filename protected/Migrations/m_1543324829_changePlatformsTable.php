<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1543324829_changePlatformsTable
    extends Migration
{

    public function up()
    {
        $sql['equipment.platforms__add column'] = 'ALTER TABLE equipment."platforms" ADD COLUMN "details" jsonb';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['equipment.platforms__drop column'] = 'ALTER TABLE equipment."platforms" DROP COLUMN "details"';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
