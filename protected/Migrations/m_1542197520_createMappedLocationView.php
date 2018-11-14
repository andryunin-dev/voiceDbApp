<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542197520_createMappedLocationView
    extends Migration
{

    public function up()
    {
        $sql['drop if exists'] = 'DROP VIEW IF EXISTS view.mapped_locations';
        $sql['create view'] = '
            CREATE OR REPLACE VIEW view.mapped_locations AS
                SELECT
                       coalesce(src."lotusId", map.lotus_id) "Lotus_id",
                       coalesce(map.city, src.city) "City",
                       coalesce(map.reg_center, src."regCenter") "RegCenter",
                       coalesce(map.region, src.region) "Region",
                       coalesce(map.office, src.office) "Office",
                       src.comment "Comment",
                       src.address "Address",
                       src.people "People"
                FROM view.geo src
                FULL JOIN mapping.reg_centers map ON src."lotusId" = map.lotus_id
                ORDER BY "Lotus_id"
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
        $sql['drop view'] = 'DROP VIEW IF EXISTS view.mapped_locations';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}