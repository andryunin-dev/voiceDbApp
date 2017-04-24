<?php

namespace App\Migrations;

use T4\Core\Exception;
use T4\Orm\Migration;

class m_1491820532_createVlansVrfsTables
    extends Migration
{

    public function up()
    {
        if ($this->db->query('SELECT COUNT(1) FROM network."networks"')->fetchScalar() > 0) {
            throw new Exception('Before apply this migration you need delete all records from network."networks" table in main DB.');
        }
        $table['network.vlans'] = 'CREATE TABLE network."vlans" (
            __id SERIAL,
            id INT,
            name VARCHAR(100),
            comment TEXT,
            PRIMARY KEY (__id),
            CONSTRAINT valid_valn_id CHECK (id > 0 AND id < 4095)
        )';
        $table['network.vrfs'] = 'CREATE TABLE network."vrfs" (
            __id SERIAL,
            name VARCHAR(100),
            rd VARCHAR(30) UNIQUE,
            comment TEXT,
            PRIMARY KEY (__id)
        )';

        $column['__location_id'] = 'ALTER TABLE network."networks" ADD COLUMN __location_id BIGINT ';
        $column['__vlan_id'] = 'ALTER TABLE network."networks" ADD COLUMN __vlan_id BIGINT';
        $column['__vrf_id'] = 'ALTER TABLE network."networks" ADD COLUMN __vrf_id BIGINT';

        $constrain['fk_location_id'] = 'ALTER TABLE network."networks" ADD CONSTRAINT fk_location_id FOREIGN KEY ("__location_id")
            REFERENCES company."offices" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT';
        $constrain['fk_vlan_id'] = 'ALTER TABLE network."networks" ADD CONSTRAINT fk_vlan_id FOREIGN KEY ("__vlan_id")
            REFERENCES network."vlans" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT';
        $constrain['fk_vrf_id'] = 'ALTER TABLE network."networks" ADD CONSTRAINT fk_vrf_id FOREIGN KEY ("__vrf_id")
            REFERENCES network."vrfs" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT';
        $constrain['not_null_vrf_id'] = 'ALTER TABLE network."networks" ALTER COLUMN __vrf_id SET NOT NULL ';
        $constrain['unique_address_vrf'] = 'ALTER TABLE network."networks" ADD CONSTRAINT unique_address_vrf UNIQUE (address, __vrf_id)';

        /**
         * create tables
         */
        foreach ($table as $key => $query) {
            echo 'main DB: creating ' . $key . ' table...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        /**
         * add columns to networks table
         */
        foreach ($column as $key => $query) {
            echo 'main DB: creating ' . $key . ' column...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        /**
         * add constraints in networks table
         */
        foreach ($constrain as $key => $query) {
            echo 'main DB: creating ' . $key . ' constraint...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        //insert global VRF into VRFs table
        echo 'main DB: inserting initial data into VRF table' . "\n";
        $this->db->execute('INSERT INTO network.vrfs (name, rd) VALUES (:name, :rd)', [':name' => 'global', 'rd' => ':']);
        echo 'Done!' . "\n";

        //============================ for test DB =========================
        $this->setDb('phpUnitTest');
        /**
         * create tables
         */
        foreach ($table as $key => $query) {
            echo 'test DB: creating ' . $key . ' table...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        /**
         * add columns to networks table
         */
        foreach ($column as $key => $query) {
            echo 'test DB: creating ' . $key . ' column...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        /**
         * add constraints in networks table
         */
        foreach ($constrain as $key => $query) {
            echo 'test DB: creating ' . $key . ' constraint...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        //insert global VRF into VRFs table
        echo 'test DB: inserting initial data into VRF table' . "\n";
        $this->db->execute('INSERT INTO network.vrfs (name, rd) VALUES (:name, :rd)', [':name' => 'global', 'rd' => ':']);
        echo 'Done!' . "\n";
    }

    public function down()
    {
        $column['__location_id'] = 'ALTER TABLE network."networks" DROP COLUMN __location_id';
        $column['__vlan_id'] = 'ALTER TABLE network."networks" DROP COLUMN __vlan_id';
        $column['__vrf_id'] = 'ALTER TABLE network."networks" DROP COLUMN __vrf_id';

        $constrain['fk_location_id'] = 'ALTER TABLE network."networks" DROP CONSTRAINT fk_location_id';
        $constrain['fk_vlan_id'] = 'ALTER TABLE network."networks" DROP CONSTRAINT fk_vlan_id';
        $constrain['fk_vrf_id'] = 'ALTER TABLE network."networks" DROP CONSTRAINT fk_vrf_id';
        $constrain['unique_address_vrf'] = 'ALTER TABLE network."networks" DROP CONSTRAINT unique_address_vrf';
        $constrain['not_null_vrf_id'] = 'ALTER TABLE network."networks" ALTER COLUMN __vrf_id DROP NOT NULL ';


        echo 'Dropping constraints on main DB' . "\n";
        foreach ($constrain as $key => $query) {
            echo 'dropping ' . $key . ' constraint...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        echo 'Dropping columns on main DB' . "\n";
        foreach ($column as $key => $query) {
            echo 'dropping ' . $key . ' column...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }


        echo 'Dropping tables on main DB...' . "\n";
        $this->dropTable('network.vrfs');
        $this->dropTable('network.vlans');

        //================for test DB====================
        $this->setDb('phpUnitTest');

        echo 'Dropping constraints on test DB' . "\n";
        foreach ($constrain as $key => $query) {
            echo 'dropping ' . $key . ' constraint...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }

        echo 'Dropping columns on test DB' . "\n";
        foreach ($column as $key => $query) {
            echo 'dropping ' . $key . ' column...' . "\n";
            if (true === $this->db->execute($query)) {
                echo 'Done!' . "\n";
            }
        }


        echo 'Dropping tables on test DB...' . "\n";
        $this->dropTable('network.vrfs');
        $this->dropTable('network.vlans');

    }
    
}