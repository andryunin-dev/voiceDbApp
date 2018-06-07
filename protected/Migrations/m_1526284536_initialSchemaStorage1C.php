<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1526284536_initialSchemaStorage1C
    extends Migration
{

    public function up()
    {
        // schema
        $sql['create_schema_storage_1c'] = 'CREATE SCHEMA IF NOT EXISTS storage_1c';

        // regions1C
        $sql['create_table_storage_1c.regions1C'] = 'CREATE TABLE "storage_1c"."regions1C" (__id SERIAL NOT NULL, title citext UNIQUE NOT NULL, PRIMARY KEY(__id))';

        // cities1C
        $sql['create_table_storage_1c.cities1C'] = 'CREATE TABLE "storage_1c"."cities1C" (__id SERIAL NOT NULL, __region_1c_id BIGINT NOT NULL, title citext, PRIMARY KEY(__id))';

        $sql['create_index_idx_cities1C__region1c_id'] = 'CREATE INDEX "idx_cities1C__region1c_id" ON "storage_1c"."cities1C" (__region_1c_id)';
        $sql['alter_table_storage_1c.cities1C'] = 'ALTER TABLE "storage_1c"."cities1C" ADD CONSTRAINT "fk_cities1C__region1c_id" FOREIGN KEY (__region_1c_id) REFERENCES "storage_1c"."regions1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        // roomsTypes
        $sql['create_table_storage_1c.roomsTypes'] = 'CREATE TABLE "storage_1c"."roomsTypes" (__id SERIAL NOT NULL, type citext UNIQUE NOT NULL, PRIMARY KEY(__id))';

        // rooms1C
        $sql['create_table_storage_1c.rooms1C'] = 'CREATE TABLE "storage_1c"."rooms1C" (__id SERIAL NOT NULL, __type_id BIGINT NOT NULL, __voice_office_id BIGINT, __city_1c_id BIGINT NOT NULL, title citext, "roomsCode" citext UNIQUE NOT NULL, address citext, "isAutoDefinition" BOOLEAN DEFAULT TRUE, PRIMARY KEY(__id))';

        $sql['create_index_idx_rooms1C__type_id'] = 'CREATE INDEX "idx_rooms1C__type_id" ON "storage_1c"."rooms1C" (__type_id)';
        $sql['alter_table_storage_1c.rooms1C_fk_rooms1C__type_id'] = 'ALTER TABLE "storage_1c"."rooms1C" ADD CONSTRAINT "fk_rooms1C__type_id" FOREIGN KEY (__type_id) REFERENCES "storage_1c"."roomsTypes" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_rooms1C__city_1c_id'] = 'CREATE INDEX "idx_rooms1C__city_1c_id" ON "storage_1c"."rooms1C" (__city_1c_id)';
        $sql['alter_table_storage_1c.rooms1C_fk_rooms1C__city_1c_id'] = 'ALTER TABLE "storage_1c"."rooms1C" ADD CONSTRAINT "fk_rooms1C__city_1c_id" FOREIGN KEY (__city_1c_id) REFERENCES "storage_1c"."cities1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_rooms1C__voice_office_id'] = 'CREATE INDEX "idx_rooms1C__voice_office_id" ON "storage_1c"."rooms1C" (__voice_office_id)';
        $sql['alter_table_storage_1c.rooms1C_fk_rooms1C__voice_office_id'] = 'ALTER TABLE "storage_1c"."rooms1C" ADD CONSTRAINT "fk_rooms1C__voice_office_id" FOREIGN KEY (__voice_office_id) REFERENCES "company"."offices" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_rooms1C__rooms_code'] = 'CREATE INDEX "idx_rooms1C__rooms_code" ON "storage_1c"."rooms1C" ("roomsCode")';


        // category
        $sql['create_table_storage_1c.categories'] = 'CREATE TABLE "storage_1c"."categories" (__id SERIAL NOT NULL, title citext UNIQUE NOT NULL, PRIMARY KEY(__id))';


        // nomenclatureTypes
        $sql['create_table_storage_1c.nomenclatureTypes'] = 'CREATE TABLE "storage_1c"."nomenclatureTypes" (__id SERIAL NOT NULL, type citext UNIQUE NOT NULL, PRIMARY KEY(__id))';


        // nomenclature1C
        $sql['create_table_storage_1c.nomenclature1C'] = 'CREATE TABLE "storage_1c"."nomenclature1C" (__id SERIAL NOT NULL, __type_id BIGINT NOT NULL, title citext, PRIMARY KEY(__id))';

        $sql['create_index_idx_nomenclature1C__type_id'] = 'CREATE INDEX "idx_nomenclature1C__type_id" ON "storage_1c"."nomenclature1C" (__type_id)';
        $sql['alter_table_storage_1c.nomenclature1C'] = 'ALTER TABLE "storage_1c"."nomenclature1C" ADD CONSTRAINT "fk_nomenclature1C__type_id" FOREIGN KEY (__type_id) REFERENCES "storage_1c"."nomenclatureTypes" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';


        // mols
        $sql['create_table_storage_1c.mols'] = 'CREATE TABLE "storage_1c".mols (__id SERIAL NOT NULL, fio citext, "molTabNumber" INTEGER UNIQUE NOT NULL, PRIMARY KEY(__id))';
        $sql['create_index_idx_mols__mol_tabnumber'] = 'CREATE INDEX "idx_mols__mol_tabnumber" ON "storage_1c".mols ("molTabNumber")';


        // inventoryItem1C
        $sql['create_table_storage_1c.inventoryItem1C'] = 'CREATE TABLE "storage_1c"."inventoryItem1C" (__id SERIAL NOT NULL, "__rooms_1c_id" BIGINT NOT NULL, "__category_id" BIGINT NOT NULL, __nomenclature_id BIGINT NOT NULL, __mol_id BIGINT NOT NULL, "inventoryNumber" citext UNIQUE NOT NULL, "serialNumber" citext, "dateOfRegistration" TIMESTAMP, "lastUpdate" TIMESTAMP, PRIMARY KEY(__id))';

        $sql['create_index_idx_inventoryItem1C__category_id'] = 'CREATE INDEX "idx_inventoryItem1C__category_id" ON "storage_1c"."inventoryItem1C" ("__category_id")';
        $sql['alter_table_storage_1c.inventoryItem1C_fk_inventoryItem1C__category_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" ADD CONSTRAINT "fk_inventoryItem1C__category_id" FOREIGN KEY ("__category_id") REFERENCES "storage_1c"."categories" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_inventoryItem1C__nomenclature_id'] = 'CREATE INDEX "idx_inventoryItem1C__nomenclature_id" ON "storage_1c"."inventoryItem1C" (__nomenclature_id)';
        $sql['alter_table_storage_1c.inventoryItem1C_fk_inventoryItem1C__nomenclature_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" ADD CONSTRAINT "fk_inventoryItem1C__nomenclature_id" FOREIGN KEY (__nomenclature_id) REFERENCES "storage_1c"."nomenclature1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_inventoryItem1C__mol_id'] = 'CREATE INDEX "idx_inventoryItem1C__mol_id" ON "storage_1c"."inventoryItem1C" (__mol_id)';
        $sql['alter_table_storage_1c.inventoryItem1C_fk_inventoryItem1C__mol_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" ADD CONSTRAINT "fk_inventoryItem1C__mol_id" FOREIGN KEY (__mol_id) REFERENCES "storage_1c".mols (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_inventoryItem1C__rooms_1c_id'] = 'CREATE INDEX "idx_inventoryItem1C__rooms_1c_id" ON "storage_1c"."inventoryItem1C" (__rooms_1c_id)';
        $sql['alter_table_storage_1c.inventoryItem1C_fk_inventoryItem1C__rooms_1c_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" ADD CONSTRAINT "fk_inventoryItem1C__rooms_1c_id" FOREIGN KEY (__rooms_1c_id) REFERENCES "storage_1c"."rooms1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_inventoryItem1C__inventory_number'] = 'CREATE INDEX "idx_inventoryItem1C__inventory_number" ON "storage_1c"."inventoryItem1C" ("inventoryNumber")';
        $sql['create_index_idx_inventoryItem1C__serial_number'] = 'CREATE INDEX "idx_inventoryItem1C__serial_number" ON "storage_1c"."inventoryItem1C" ("serialNumber")';


        // mol-rooms1C
        $sql['create_table_storage_1c.mol_rooms1C'] = 'CREATE TABLE "storage_1c"."mol_rooms1C" (__mol_id BIGINT NOT NULL, __rooms_1c_id BIGINT NOT NULL, PRIMARY KEY(__mol_id, __rooms_1c_id))';
        $sql['create_index_idx_mol_rooms1C___mol_id'] = 'CREATE INDEX "idx_mol_rooms1C___mol_id" ON "storage_1c"."mol_rooms1C" (__mol_id)';
        $sql['create_index_idx_mol_rooms1C___rooms_1c_id'] = 'CREATE INDEX "idx_mol_rooms1C___rooms_1c_id" ON "storage_1c"."mol_rooms1C" (__rooms_1c_id)';
        $sql['alter_table_storage_1c.mol_rooms1C_fk_mol_rooms1C___mol_id'] = 'ALTER TABLE "storage_1c"."mol_rooms1C" ADD CONSTRAINT "fk_mol_rooms1C___mol_id" FOREIGN KEY (__mol_id) REFERENCES "storage_1c".mols (__id) ON DELETE CASCADE';
        $sql['alter_table_storage_1c.mol_rooms1C_fk_mol_rooms1C___rooms_1c'] = 'ALTER TABLE "storage_1c"."mol_rooms1C" ADD CONSTRAINT "fk_mol_rooms1C___rooms_1c" FOREIGN KEY (__rooms_1c_id) REFERENCES "storage_1c"."rooms1C" (__id) ON DELETE CASCADE';


        // appliance1C
        $sql['create_table_storage_1c.appliances1C'] = 'CREATE TABLE "storage_1c"."appliances1C" (__id SERIAL NOT NULL, __inventory_item_id BIGINT UNIQUE NOT NULL, __voice_appliance_id BIGINT UNIQUE, PRIMARY KEY(__id))';

        $sql['create_index_idx_appliances1C__inventory_item_id'] = 'CREATE INDEX "idx_appliances1C__inventory_item_id" ON "storage_1c"."appliances1C" (__inventory_item_id)';
        $sql['alter_table_storage_1c.appliances1C_fk_appliances1C__inventory_item_id'] = 'ALTER TABLE "storage_1c"."appliances1C" ADD CONSTRAINT "fk_appliances1C__inventory_item_id" FOREIGN KEY (__inventory_item_id) REFERENCES "storage_1c"."inventoryItem1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_appliances1C__voice_appliance_id'] = 'CREATE INDEX "idx_appliances1C__voice_appliance_id" ON "storage_1c"."appliances1C" (__voice_appliance_id)';
        $sql['alter_table_storage_1c.appliances1C_fk_appliances1C__voice_appliance_id'] = 'ALTER TABLE "storage_1c"."appliances1C" ADD CONSTRAINT "fk_appliances1C__voice_appliance_id" FOREIGN KEY (__voice_appliance_id) REFERENCES "equipment"."appliances" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';


        // module1C
        $sql['create_table_storage_1c.modules1C'] = 'CREATE TABLE "storage_1c"."modules1C" (__id SERIAL NOT NULL, __inventory_item_id BIGINT UNIQUE, __voice_module_id BIGINT UNIQUE, PRIMARY KEY(__id))';

        $sql['create_index_idx_modules1C__inventory_item_id'] = 'CREATE INDEX "idx_modules1C__inventory_item_id" ON "storage_1c"."modules1C" (__inventory_item_id)';
        $sql['alter_table_storage_1c.modules1C_fk_modules1C__inventory_item_id'] = 'ALTER TABLE "storage_1c"."modules1C" ADD CONSTRAINT "fk_modules1C__inventory_item_id" FOREIGN KEY (__inventory_item_id) REFERENCES "storage_1c"."inventoryItem1C" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';

        $sql['create_index_idx_modules1C__voice_module_id'] = 'CREATE INDEX "idx_modules1C__voice_module_id" ON "storage_1c"."modules1C" (__voice_module_id)';
        $sql['alter_table_storage_1c.modules1C_fk_modules1C__voice_module_id'] = 'ALTER TABLE "storage_1c"."modules1C" ADD CONSTRAINT "fk_modules1C__voice_module_id" FOREIGN KEY (__voice_module_id) REFERENCES "equipment"."moduleItems" (__id) ON UPDATE CASCADE ON DELETE RESTRICT';


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
        // module1C
        $sql['drop_constraint_fk_modules1C__voice_module_id'] = 'ALTER TABLE "storage_1c"."modules1C" DROP CONSTRAINT "fk_modules1C__voice_module_id"';
        $sql['drop_constraint_fk_modules1C__inventory_item_id'] = 'ALTER TABLE "storage_1c"."modules1C" DROP CONSTRAINT "fk_modules1C__inventory_item_id"';
        $sql['drop_table_storage_1c.modules1C'] = 'DROP TABLE "storage_1c"."modules1C"';

        // appliance1C
        $sql['drop_constraint_fk_appliances1C__voice_appliance_id'] = 'ALTER TABLE "storage_1c"."appliances1C" DROP CONSTRAINT "fk_appliances1C__voice_appliance_id"';
        $sql['drop_constraint_fk_appliances1C__inventory_item_id'] = 'ALTER TABLE "storage_1c"."appliances1C" DROP CONSTRAINT "fk_appliances1C__inventory_item_id"';
        $sql['drop_table_storage_1c.appliances1C'] = 'DROP TABLE "storage_1c"."appliances1C"';

        // mol-rooms1C
        $sql['drop_constraint_fk_mol_rooms1C___rooms_1c'] = 'ALTER TABLE "storage_1c"."mol_rooms1C" DROP CONSTRAINT "fk_mol_rooms1C___rooms_1c"';
        $sql['drop_constraint_fk_mol_rooms1C___mol_id'] = 'ALTER TABLE "storage_1c"."mol_rooms1C" DROP CONSTRAINT "fk_mol_rooms1C___mol_id"';
        $sql['drop_table_storage_1c.mol_rooms1C'] = 'DROP TABLE "storage_1c"."mol_rooms1C"';

        // inventoryItem1C
        $sql['drop_constraint_fk_inventoryItem1C__rooms_1c_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" DROP CONSTRAINT "fk_inventoryItem1C__rooms_1c_id"';
        $sql['drop_constraint_fk_inventoryItem1C__mol_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" DROP CONSTRAINT "fk_inventoryItem1C__mol_id"';
        $sql['drop_constraint_fk_inventoryItem1C__nomenclature_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" DROP CONSTRAINT "fk_inventoryItem1C__nomenclature_id"';
        $sql['drop_constraint_fk_inventoryItem1C__category_id'] = 'ALTER TABLE "storage_1c"."inventoryItem1C" DROP CONSTRAINT "fk_inventoryItem1C__category_id"';
        $sql['drop_table_storage_1c.inventoryItem1C'] = 'DROP TABLE "storage_1c"."inventoryItem1C"';

        // mols
        $sql['drop_table_storage_1c.mols'] = 'DROP TABLE "storage_1c".mols';

        // nomenclature1C
        $sql['drop_constraint_fk_nomenclature1C__type_id'] = 'ALTER TABLE "storage_1c"."nomenclature1C" DROP CONSTRAINT "fk_nomenclature1C__type_id"';
        $sql['drop_table_storage_1c.nomenclature1C'] = 'DROP TABLE "storage_1c"."nomenclature1C"';

        // nomenclatureTypes
        $sql['drop_table_storage_1c.nomenclatureTypes'] = 'DROP TABLE "storage_1c"."nomenclatureTypes"';

        // category
        $sql['drop_table_storage_1c.categories'] = 'DROP TABLE "storage_1c"."categories"';

        // rooms1C
        $sql['drop_constraint_fk_rooms1C__voice_office_id'] = 'ALTER TABLE "storage_1c"."rooms1C" DROP CONSTRAINT "fk_rooms1C__voice_office_id"';
        $sql['drop_constraint_fk_rooms1C__city_1c_id'] = 'ALTER TABLE "storage_1c"."rooms1C" DROP CONSTRAINT "fk_rooms1C__city_1c_id"';
        $sql['drop_constraint_fk_rooms1C__type_id'] = 'ALTER TABLE "storage_1c"."rooms1C" DROP CONSTRAINT "fk_rooms1C__type_id"';
        $sql['drop_table_storage_1c.rooms1C'] = 'DROP TABLE "storage_1c"."rooms1C"';

        // roomsTypes
        $sql['drop_table_storage_1c.roomsTypes'] = 'DROP TABLE "storage_1c"."roomsTypes"';

        // cities1C
        $sql['drop_constraint_fk_cities1C__region1c_id'] = 'ALTER TABLE "storage_1c"."cities1C" DROP CONSTRAINT "fk_cities1C__region1c_id"';
        $sql['drop_table_storage_1c.cities1C'] = 'DROP TABLE "storage_1c"."cities1C"';

        // regions1C
        $sql['drop_table_storage_1c.regions1C'] = 'DROP TABLE "storage_1c"."regions1C"';

        // schema
        $sql['drop_schema_storage_1c'] = 'DROP SCHEMA IF EXISTS storage_1c';


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
