<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1549975593_changeApplianceTable
    extends Migration
{

    public function up()
    {
        $sql['change_table__equipment.add_column_untrusted_location'] = '
            ALTER TABLE equipment.appliances ADD COLUMN "untrustedLocation" BOOLEAN DEFAULT FALSE
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['change_table__equipment.drop_column_untrusted_location'] = '
            ALTER TABLE equipment.appliances DROP COLUMN "untrustedLocation"
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
