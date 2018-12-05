<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1543826947_createLotusDbPhoneBookMatView
    extends Migration
{

    public function up()
    {
        $sql['drop_old_mat_view__lotus_db_phone_book'] = 'DROP MATERIALIZED VIEW IF EXISTS view.lotus_db_phone_book';
        $sql['create_mat_view__lotus_db_phone_book'] =
            'CREATE MATERIALIZED VIEW view.lotus_db_phone_book AS
                SELECT *, now() AS last_refresh FROM lotus.phone_book';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop_mat_view__lotus_db_phone_book'] = 'DROP MATERIALIZED VIEW IF EXISTS view.lotus_db_phone_book';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
