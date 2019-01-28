<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1548671575_createHdsWeeklyAgentsDnStatisticsTable
    extends Migration
{

    public function up()
    {
        $sql['create_table__hds.hds_weekly_agents_dn_statistics'] = '
            CREATE TABLE hds.hds_weekly_agents_dn_statistics (
                __id SERIAL NOT NULL,
                year INTEGER,
                week INTEGER,
                date TIMESTAMP,
                "lotusId" INTEGER,
                prefix citext,
                dn citext,
                PRIMARY KEY (__id)
            )
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
        $sql['drop_table__hds.hds_weekly_agents_dn_statistics'] = 'DROP TABLE hds.hds_weekly_agents_dn_statistics';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
