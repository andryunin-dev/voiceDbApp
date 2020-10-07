<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1601643890_changePhoneInfoTable
    extends Migration
{

    public function up()
    {
        $sql['add_column_e164mask_to_table_PhoneInfo'] = '
            ALTER TABLE equipment."phoneInfo"
                ADD COLUMN "e164mask" citext
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
        $sql['drop_column_e164mask_from_table_PhoneInfo'] = '
            ALTER TABLE equipment."phoneInfo"
                DROP COLUMN "e164mask"
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
