<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1567683189_createLotusDbPhoneBookExtendsMatView
    extends Migration
{

    public function up()
    {
        $sql['create materialize view'] = '
            CREATE MATERIALIZED VIEW view.lotus_employees AS SELECT *, now() AS last_refresh FROM lotus.employees
        ';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop materialize view'] = 'DROP MATERIALIZED VIEW IF EXISTS view.lotus_employees';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}
