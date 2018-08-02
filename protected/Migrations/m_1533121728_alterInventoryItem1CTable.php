<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1533121728_alterInventoryItem1CTable
    extends Migration
{

    public function up()
    {

        $sql['drop constraint inventoryItem1C_inventoryNumber_key'] = 'ALTER TABLE storage_1c."inventoryItem1C" DROP CONSTRAINT "inventoryItem1C_inventoryNumber_key"';
        $sql['set constraint not null'] = 'ALTER TABLE storage_1c."inventoryItem1C" ALTER COLUMN "inventoryNumber" SET NOT NULL';


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
        $sql['set constraint inventoryItem1C_inventoryNumber_key'] = 'ALTER TABLE storage_1c."inventoryItem1C" ADD CONSTRAINT "inventoryItem1C_inventoryNumber_key" UNIQUE ("inventoryNumber")';


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
