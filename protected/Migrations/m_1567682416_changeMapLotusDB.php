<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1567682416_changeMapLotusDB
    extends Migration
{

    public function up()
    {
        $sql['create foreignTable'] = '
            CREATE FOREIGN TABLE lotus.employees (
               name citext,
               surname citext,
               patronymic citext,
               division citext,
               work_phone citext,
               mobile_phone citext,
               work_email citext,
               position citext,
               persons_code BIGINT NOT NULL,
               net_name citext ,
               domain citext 
            )
            SERVER lotus_data_phone_book
            OPTIONS (SCHEMA_NAME \'public\', TABLE_NAME \'phone_book\')
        ';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop foreignTable'] = 'DROP FOREIGN TABLE IF EXISTS lotus.employees';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}
