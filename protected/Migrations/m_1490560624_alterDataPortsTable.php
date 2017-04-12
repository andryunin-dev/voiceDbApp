<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1490560624_alterDataPortsTable
    extends Migration
{

    public function up()
    {
        //BEFORE INSERT COLUMN WITH NOT NULL CONSTRAIN NEED DELETE ALL RECORD FROM TABLE!!!
        $this->db->execute('DELETE FROM equipment."dataPorts"');

        $sql['addColumn'] = 'ALTER TABLE equipment."dataPorts" ADD COLUMN __network_id  BIGINT NOT NULL ';
        $sql['addConstrain'] = 'ALTER TABLE equipment."dataPorts" ADD CONSTRAINT fk_network_id FOREIGN KEY (__network_id)
              REFERENCES network."networks" (__id)
              ON DELETE RESTRICT
              ON UPDATE CASCADE';
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'On main DB ' . $key . ' done' . "\n";
            }
        }

        //for test DB
        $this->setDb('phpUnitTest');
        $this->db->execute('DELETE FROM equipment."dataPorts"');

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'On test DB ' . $key . ' done' . "\n";
            }
        }
    }

    public function down()
    {
        $sql = 'ALTER TABLE equipment."dataPorts" DROP COLUMN __network_id';
        if (true === $this->db->execute($sql)) {
            echo 'On main DB drop column done' . "\n";
        }
        //for test DB
        $this->setDb('phpUnitTest');
        if (true === $this->db->execute($sql)) {
            echo 'On test DB drop column done' . "\n";
        }
    }
    
}