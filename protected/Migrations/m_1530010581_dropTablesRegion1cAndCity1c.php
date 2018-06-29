<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1530010581_dropTablesRegion1cAndCity1c
    extends Migration
{

    public function up()
    {
        // rooms1C
        $sql['drop_constraint_fk_rooms1C__city_1c_id'] = 'ALTER TABLE "storage_1c"."rooms1C" DROP CONSTRAINT "fk_rooms1C__city_1c_id"';
        $sql['drop_index_idx_rooms1C__city_1c_id'] = 'DROP INDEX storage_1c."idx_rooms1C__city_1c_id"';
        $sql['alter_table_storage_1c.__city_1c_id'] = 'ALTER TABLE "storage_1c"."rooms1C" DROP COLUMN __city_1c_id';

        $sql['drop_constraint_fk_rooms1C__type_id'] = 'ALTER TABLE "storage_1c"."rooms1C" DROP CONSTRAINT "fk_rooms1C__type_id"';
        $sql['drop_index_idx_rooms1C__type_id'] = 'DROP INDEX storage_1c."idx_rooms1C__type_id"';
        $sql['alter_table_storage_1c.__type_id'] = 'ALTER TABLE "storage_1c"."rooms1C" DROP COLUMN __type_id';

        // roomsTypes
        $sql['drop_table_storage_1c.roomsTypes'] = 'DROP TABLE "storage_1c"."roomsTypes"';

        // cities1C
        $sql['drop_constraint_fk_cities1C__region1c_id'] = 'ALTER TABLE "storage_1c"."cities1C" DROP CONSTRAINT "fk_cities1C__region1c_id"';
        $sql['drop_table_storage_1c.cities1C'] = 'DROP TABLE "storage_1c"."cities1C"';

        // regions1C
        $sql['drop_table_storage_1c.regions1C'] = 'DROP TABLE "storage_1c"."regions1C"';


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
        // regions1C
        $sql['create_table_storage_1c.regions1C'] = 'CREATE TABLE "storage_1c"."regions1C" (__id SERIAL NOT NULL, title citext UNIQUE NOT NULL, PRIMARY KEY(__id))';

        // cities1C
        $sql['create_table_storage_1c.cities1C'] = 'CREATE TABLE "storage_1c"."cities1C" (__id SERIAL NOT NULL, __region_1c_id BIGINT NOT NULL, title citext, PRIMARY KEY(__id))';

        $sql['create_index_idx_cities1C__region1c_id'] = 'CREATE INDEX "idx_cities1C__region1c_id" ON "storage_1c"."cities1C" (__region_1c_id)';
        $sql['alter_table_storage_1c.cities1C'] = 'ALTER TABLE "storage_1c"."cities1C" ADD CONSTRAINT "fk_cities1C__region1c_id" FOREIGN KEY (__region_1c_id) REFERENCES "storage_1c"."regions1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        // roomsTypes
        $sql['create_table_storage_1c.roomsTypes'] = 'CREATE TABLE "storage_1c"."roomsTypes" (__id SERIAL NOT NULL, type citext UNIQUE NOT NULL, PRIMARY KEY(__id))';

        // rooms1C
        $sql['alter_table_storage_1c.__city_1c_id'] = 'ALTER TABLE "storage_1c"."rooms1C" ADD COLUMN __city_1c_id BIGINT';
        $sql['create_index_idx_rooms1C__city_1c_id'] = 'CREATE INDEX "idx_rooms1C__city_1c_id" ON "storage_1c"."rooms1C" (__city_1c_id)';
        $sql['alter_table_storage_1c.rooms1C_fk_rooms1C__city_1c_id'] = 'ALTER TABLE "storage_1c"."rooms1C" ADD CONSTRAINT "fk_rooms1C__city_1c_id" FOREIGN KEY (__city_1c_id) REFERENCES "storage_1c"."cities1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['alter_table_storage_1c.__type_id'] = 'ALTER TABLE "storage_1c"."rooms1C" ADD COLUMN __type_id BIGINT';
        $sql['create_index_idx_rooms1C__type_id'] = 'CREATE INDEX "idx_rooms1C__type_id" ON "storage_1c"."rooms1C" (__type_id)';
        $sql['alter_table_storage_1c.rooms1C_fk_rooms1C__type_id'] = 'ALTER TABLE "storage_1c"."rooms1C" ADD CONSTRAINT "fk_rooms1C__type_id" FOREIGN KEY (__type_id) REFERENCES "storage_1c"."roomsTypes" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';


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
