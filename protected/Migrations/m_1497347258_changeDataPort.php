<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1497347258_changeDataPort
    extends Migration
{

    /**
     * удалаяем жесткую привязку сети к data порту
     * в датапорт добавляем поле masklen(в нем храним длину маски подсети, если она есть)
     * в поле ipAddress храним только IP адрес без маски
     */
    public function up()
    {
        $sql['equipment.dataPorts_1'] = 'ALTER TABLE equipment."dataPorts" ALTER COLUMN __network_id DROP NOT NULL ';
        $sql['equipment.dataPorts_2'] = 'ALTER TABLE equipment."dataPorts" ADD COLUMN "masklen" INT';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['equipment.dataPorts_2'] = 'ALTER TABLE equipment."dataPorts" DROP COLUMN "masklen"';
        $sql['equipment.dataPorts_1'] = 'ALTER TABLE equipment."dataPorts" ALTER COLUMN __network_id SET NOT NULL ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: Table ' . $key . ' is altered' . PHP_EOL;
            }
        }

    }
    
}