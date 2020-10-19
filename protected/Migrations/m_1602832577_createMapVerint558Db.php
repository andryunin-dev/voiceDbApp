<?php

namespace App\Migrations;

use T4\Orm\Migration;
use function T4\app;

class m_1602832577_createMapVerint558Db
    extends Migration
{

    public function up()
    {
        $servername = app()->config->db->verint558->servername;
        $port = app()->config->db->verint558->port;
        $database = app()->config->db->verint558->database;
        $username = app()->config->db->verint558->username;
        $password = app()->config->db->verint558->password;

        $sql['create schema'] = 'CREATE SCHEMA IF NOT EXISTS verint558';
        $sql['create server'] = '
            CREATE SERVER verint558
            FOREIGN DATA WRAPPER tds_fdw
            OPTIONS (servername \'' . $servername . '\',  port \'' . $port . '\', database \'' . $database . '\')';
        $sql['create user'] = '
            CREATE USER MAPPING FOR CURRENT_USER
            SERVER verint558
            OPTIONS (username \'' . $username . '\', password \'' . $password . '\')';
        $sql['create foreign table'] = '
        CREATE FOREIGN TABLE verint558."infoKDVerintChannel" (recorder citext, dn citext)
        SERVER verint558
        OPTIONS (query \'EXEC dbo.pGetInfoKDVerintChannel\', row_estimate_method \'execute\', match_column_names \'0\')';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop foreign table'] = 'DROP FOREIGN TABLE IF EXISTS verint558."infoKDVerintChannel"';
        $sql['drop user'] = 'DROP USER MAPPING FOR CURRENT_USER SERVER verint558';
        $sql['drop server'] = 'DROP SERVER IF EXISTS verint558';
        $sql['drop schema'] = 'DROP SCHEMA IF EXISTS verint558';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

}
