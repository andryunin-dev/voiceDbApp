<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1532516860_createDevCallsStatsMatView
    extends Migration
{

    public function up()
    {
        $sourceTable = 'cdr.dev_calls_stats';
        $destinationTable = 'view.dev_calls_stats';

        $sql['drop old dev_calls_stats view'] = 'DROP MATERIALIZED VIEW IF EXISTS '.$destinationTable;
        $sql['create dev_calls_stats view'] =
            'CREATE MATERIALIZED VIEW '.$destinationTable.' AS
                SELECT
                    phInfo.__appliance_id AS appliance_id,
                    stats.device_name,
                    stats.last_call_day,
                    stats.d0_calls_amount,
                    stats.m0_calls_amount,
                    stats.m1_calls_amount,
                    stats.m2_calls_amount
                FROM '.$sourceTable.' AS stats
                    LEFT JOIN equipment."phoneInfo" AS phInfo ON phInfo.name = stats.device_name'
        ;

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

        // For test DB
//        $this->setDb('phpUnitTest');
//        foreach ($sql as $key => $query) {
//            if (true === $this->db->execute($query)) {
//                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
//            }
//        }
    }

    public function down()
    {
        $destinationTable = 'view.dev_calls_stats';

        $sql['drop dev_calls_stats view'] = 'DROP MATERIALIZED VIEW '.$destinationTable;

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

        // For test DB
//        $this->setDb('phpUnitTest');
//        foreach ($sql as $key => $query) {
//            if (true === $this->db->execute($query)) {
//                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
//            }
//        }
    }
    
}
