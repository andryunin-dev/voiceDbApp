<?php

namespace App\Migrations;

use T4\Console\Application;
use T4\Orm\Migration;

class m_1532509493_mapCdrDB
    extends Migration
{

    public function up()
    {
        $app = Application::instance();
        $cdr = $app->config->db->cdr;
        $server = 'cdr_server';
        $remoteSchema = "cdr_call_activ";
        $localSchema = "cdr";
        $importTable = 'dev_calls_stats';

        $sql['create cdr schema'] = 'CREATE SCHEMA IF NOT EXISTS '.$localSchema;
        $sql['create server'] = 'CREATE SERVER '.$server.' FOREIGN DATA WRAPPER postgres_fdw OPTIONS (host \''.$cdr->host.'\', dbname \''.$cdr->dbname.'\')';
        $sql['create user mapping'] = 'CREATE USER MAPPING FOR CURRENT_USER SERVER '.$server.' OPTIONS (user \''.$cdr->user.'\', password \''.$cdr->password.'\')';
        $sql['import foreing schema'] = 'IMPORT FOREIGN SCHEMA '.$remoteSchema.' LIMIT TO ('.$importTable.') FROM SERVER '.$server.' INTO '.$localSchema;

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
        $server = 'cdr_server';
        $localSchema = "cdr";
        $importTable = 'dev_calls_stats';

        $sql['drop import foreing table'] = 'DROP FOREIGN TABLE IF EXISTS '.$localSchema.'.'.$importTable;
        $sql['drop user mapping'] = 'DROP USER MAPPING FOR CURRENT_USER SERVER '.$server;
        $sql['drop server'] = 'DROP SERVER '.$server;
        $sql['drop cdr schema'] = 'DROP SCHEMA IF EXISTS '.$localSchema;

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
