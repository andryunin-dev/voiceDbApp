<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542807537_alterNomenclature1CTable
    extends Migration
{

    public function up()
    {
        $sql['alter_table_nomenclature_1c__add_column'] = 'ALTER TABLE storage_1c."nomenclature1C" ADD "nomenclatureId" citext';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['alter_table_nomenclature_1c__drop_column'] = 'ALTER TABLE storage_1c."nomenclature1C" DROP COLUMN "nomenclatureId"';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
