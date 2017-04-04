<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1490556053_createNetworkTable
    extends Migration
{

    public function up()
    {
        //create schema "network"
        $sql['schema'] = 'CREATE SCHEMA network';
        $sql['table'] = 'CREATE TABLE network."networks" (
            __id SERIAL,
            address CIDR UNIQUE,
            __lft INT,
            __rgt INT,
            __lvl INT,
            __prt BIGINT,
            PRIMARY KEY (__id)
        )';
        $sql['idx__lft'] = 'CREATE INDEX IF NOT EXISTS __lft ON network."networks" (__lft)';
        $sql['idx__rgt'] = 'CREATE INDEX IF NOT EXISTS __rgt ON network."networks" (__rgt)';
        $sql['idx__lvl'] = 'CREATE INDEX IF NOT EXISTS __lvl ON network."networks" (__lvl)';
        $sql['idx__key'] = 'CREATE INDEX IF NOT EXISTS __key ON network."networks" (__lft, __rgt, __lvl)';
        $sql['idx__prt'] = 'CREATE INDEX IF NOT EXISTS __prt ON network."networks" (__prt)';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'on main DB ' . $key . ' created' . "\n";
            }
        }
        // for test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'on test DB ' . $key . ' created' . "\n";
            }
        }
    }

    public function down()
    {
        //drop table network.networks then drop schema
        $this->dropTable('network.networks');
        $this->db->execute('DROP SCHEMA network');
        echo 'On main DB table "networks" and schema "network" droped' . "\n";

        // for test DB
        $this->setDb('phpUnitTest');

        //drop table network.networks then drop schema
        $this->dropTable('network.networks');
        $this->db->execute('DROP SCHEMA network');
        echo 'On test DB table "networks" and schema "network" droped' . "\n";
    }
}