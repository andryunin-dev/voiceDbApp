<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1538044355_createForeing1CTable
    extends Migration
{

    public function up()
    {
        $sql['create_table__storage_1c.foreing_1c'] = '
            CREATE TABLE "storage_1c"."foreing_1c" (
                __id SERIAL NOT NULL,
                inventory_number citext,
                serial_number citext,
                type_of_nomenclature citext,
                nomenclature citext,
                date_of_registration TIMESTAMP,
                rooms_code citext,
                rooms_address citext,
                mol citext,
                mol_tab_number citext,
                inventory_user citext,
                inventory_user_tab_number citext,
                last_update TIMESTAMP,
                PRIMARY KEY(__id))
        ';

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
        $sql['drop_table__storage_1c.foreing_1c'] = 'DROP TABLE "storage_1c"."foreing_1c"';

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
