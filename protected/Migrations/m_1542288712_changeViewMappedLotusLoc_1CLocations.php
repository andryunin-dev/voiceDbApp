<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542288712_changeViewMappedLotusLoc_1CLocations
    extends Migration
{
    public function up()
    {
        $sql['drop if exists'] = 'DROP VIEW IF EXISTS view."mappedLotusLoc_1CLocations"';
        $sql['create view'] = '
            CREATE OR REPLACE VIEW view."mappedLotusLoc_1CLocations" AS
                SELECT * FROM view."mappedLotusLocations"
                    RIGHT JOIN mapping."location1C_to_lotusId" oneC_to_lotus USING (lotus_id)
                ORDER BY oneC_to_lotus."flatCode"
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
        $sql['drop if exists'] = 'DROP VIEW IF EXISTS view."mappedLotusLoc_1CLocations"';
        $sql['create view'] = '
            CREATE OR REPLACE VIEW view."mappedLotusLoc_1CLocations" AS
                SELECT * FROM view."mappedLotusLocations"
                    FULL JOIN mapping."location1C_to_lotusId" oneC_to_lotus USING (lotus_id)
                ORDER BY oneC_to_lotus."flatCode"
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}