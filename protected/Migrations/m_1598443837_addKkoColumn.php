<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1598443837_addKkoColumn
    extends Migration
{

    public function up()
    {
        $sql['add_cco_column_to_offices'] = '
            ALTER TABLE company.offices
                ADD COLUMN "isCCO" BOOLEAN DEFAULT false
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
        $sql['drop_cco_columns_from_offices'] = '
            ALTER TABLE company.offices
                DROP COLUMN "isCCO", "isCCOtxt"
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}