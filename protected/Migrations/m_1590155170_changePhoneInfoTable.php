<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1590155170_changePhoneInfoTable
    extends Migration
{

    public function up()
    {
        $sql['add_column_cdpLastUpdate_to_table_PhoneInfo'] = '
            ALTER TABLE equipment."phoneInfo"
                ADD COLUMN "cdpLastUpdate" TIMESTAMP WITH TIME ZONE
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
        $sql['drop_column_cdpLastUpdate_from_table_PhoneInfo'] = '
            ALTER TABLE equipment."phoneInfo"
                DROP COLUMN "cdpLastUpdate"
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
