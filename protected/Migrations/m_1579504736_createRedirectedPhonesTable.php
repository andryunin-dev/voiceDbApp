<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1579504736_createRedirectedPhonesTable
    extends Migration
{

    public function up()
    {
        $sql['create_schema__cucm'] = 'CREATE SCHEMA cucm';
        $sql['create_table__cucm.redirectedPhones'] = '
            CREATE TABLE cucm."redirectedPhones" (
                __id SERIAL NOT NULL,
                device citext,
                depiction citext,
                css citext,
                devicepool citext,
                phprefix citext,
                phonedn citext,
                alertingname citext,
                forwardall citext,
                forward_all_mail citext,
                forwardbusyinternal citext,
                forwardbusyexternal citext,
                forward_no_answer_internal citext,
                forward_no_answer_external citext,
                forward_unregistred_internal citext,
                forward_unregistred_external citext,
                cfnaduration citext,
                partition citext,
                model citext,
                cucm citext,
                "lastUpdate" TIMESTAMP,
                PRIMARY KEY(__id)
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
        $sql['drop_table__cucm.redirectedPhones'] = 'DROP TABLE IF EXISTS cucm."redirectedPhones"';
        $sql['drop_schema__cucm'] = 'DROP SCHEMA IF EXISTS cucm';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
