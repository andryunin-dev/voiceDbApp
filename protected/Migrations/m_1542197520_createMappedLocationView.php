<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542197520_createMappedLocationView
    extends Migration
{

    public function up()
    {
        $sql['drop if exists'] = 'DROP VIEW IF EXISTS view."mappedLotusLocations"';
        $sql['create view'] = '
            CREATE OR REPLACE VIEW view."mappedLotusLocations" AS
                SELECT
                       coalesce(src."lotusId", map.lotus_id) "lotus_id",
                       coalesce(map.city, src.city) "city",
                       coalesce(map.reg_center, src."regCenter") "regCenter",
                       coalesce(map.region, src.region) "region",
                       coalesce(map.office, src.office) "office",
                       src.comment "comment",
                       src.address "address",
                       src.people "people"
                FROM view.geo src
                FULL JOIN mapping."lotusLocations" map ON src."lotusId" = map.lotus_id
                ORDER BY "lotus_id"
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
        $sql['drop view'] = 'DROP VIEW IF EXISTS view."mappedLotusLocations"';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}