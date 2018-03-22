<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1518163537_createDevPhoneInfoGeoMatView
    extends Migration
{

    public function up()
    {
        $sql['dropOldView'] = 'DROP MATERIALIZED VIEW IF EXISTS view.dev_phone_info_geo_mat';
        $sql['createMatView'] = '
        CREATE MATERIALIZED VIEW view.dev_phone_info_geo_mat AS
            SELECT *, now() AS last_refresh
            FROM view.dev_phone_info_geo';
        $sql['createIndexOnLotusId'] = 'CREATE INDEX idx_dev_phone_info_geo_mat_lotus_id ON view.dev_phone_info_geo_mat("lotusId")';
        $sql['vacuumAnalyze'] = 'VACUUM ANALYZE';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        //$this->setDb('phpUnitTest');
        //foreach ($sql as $key => $query) {
        //    if (true === $this->db->execute($query)) {
        //        echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
        //    }
        //}

    }

    public function down()
    {
        $sql['dropIndexOnLotusId'] = 'DROP MATERIALIZED VIEW IF EXISTS view.dev_phone_info_geo_mat';
        $sql['dropView'] = 'DROP INDEX IF EXISTS view.idx_dev_phone_info_geo_mat_lotus_id';
        $sql['vacuumAnalyze'] = 'VACUUM ANALYZE';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        //$this->setDb('phpUnitTest');
        //foreach ($sql as $key => $query) {
        //    if (true === $this->db->execute($query)) {
        //        echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
        //    }
        //}

    }
    
}