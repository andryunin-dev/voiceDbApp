<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1543218722_changeMapTableLotusTo1cLocations
    extends Migration
{

    public function up()
    {
        $sql['drop depended view'] = '
        DROP VIEW IF EXISTS view."mappedLotusLoc_1CLocations"
        ';
        $sql['change table'] = '
        ALTER TABLE mapping."location1C_to_lotusId" ALTER COLUMN "flatCode" TYPE citext USING "flatCode"::citext
        ';
        $sql['recreate view'] = '
        create view view."mappedLotusLoc_1CLocations" as
            SELECT onec_to_lotus.lotus_id,
                   "mappedLotusLocations".city,
                   "mappedLotusLocations"."regCenter",
                   "mappedLotusLocations".region,
                   "mappedLotusLocations".office,
                   "mappedLotusLocations".comment,
                   "mappedLotusLocations".address,
                   "mappedLotusLocations".people,
                   onec_to_lotus."flatCode",
                   onec_to_lotus."flatAddress"
            FROM (view."mappedLotusLocations"
                RIGHT JOIN mapping."location1C_to_lotusId" onec_to_lotus USING (lotus_id))
            ORDER BY onec_to_lotus."flatCode"
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
        $sql['drop view'] = 'DROP VIEW IF EXISTS view."mappedLotusLoc_1CLocations"';
        $sql['change table'] = 'ALTER TABLE mapping."location1C_to_lotusId" ALTER COLUMN "flatCode" TYPE bigint USING "flatCode"::bigint';
        $sql['recreate view'] = '
        create view view."mappedLotusLoc_1CLocations" as
            SELECT onec_to_lotus.lotus_id,
                   "mappedLotusLocations".city,
                   "mappedLotusLocations"."regCenter",
                   "mappedLotusLocations".region,
                   "mappedLotusLocations".office,
                   "mappedLotusLocations".comment,
                   "mappedLotusLocations".address,
                   "mappedLotusLocations".people,
                   onec_to_lotus."flatCode",
                   onec_to_lotus."flatAddress"
            FROM (view."mappedLotusLocations"
                RIGHT JOIN mapping."location1C_to_lotusId" onec_to_lotus USING (lotus_id))
            ORDER BY onec_to_lotus."flatCode"
        ';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}