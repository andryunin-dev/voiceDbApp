<?php

namespace App\Migrations;

use T4\Console\Application;
use T4\Orm\Migration;

class m_1507809600_mapLotusDB
    extends Migration
{

    public function up()
    {
        $app = Application::instance();
        $lotusConfig = $app->config->db->lotusData;
        $host = $lotusConfig->host;
        $dbName = 'LotusData';
        $remoteSchema = 'public';
        $remoteTable = 'locations';
        $user = $lotusConfig->user;
        $password = $lotusConfig->password;
        $localSchema = 'lotus';

        $sql['extension'] = 'CREATE EXTENSION postgres_fdw';
        $sql['lotusSchema'] = 'CREATE SCHEMA "' . $localSchema . '"';
        $sql['server'] = 'CREATE SERVER lotus_data FOREIGN DATA WRAPPER  postgres_fdw OPTIONS (host \'' . $host .'\', dbname \'' . $dbName .'\')';
        $sql['userMapping'] = 'CREATE USER MAPPING FOR CURRENT_USER SERVER lotus_data OPTIONS (user \'' . $user . '\', password \'' . $password .'\')';
        $sql['foreignTable'] = 'IMPORT FOREIGN SCHEMA "'. $remoteSchema . '" LIMIT TO ("' . $remoteTable . '") FROM SERVER lotus_data INTO "' . $localSchema . '"';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

    }

    public function down()
    {
        $remoteTable = 'locations';
        $localSchema = 'lotus';


        $sql['foreignTable'] = 'DROP FOREIGN TABLE "' . $localSchema . '"."' . $remoteTable . '"';
        $sql['userMapping'] = 'DROP USER MAPPING FOR CURRENT_USER SERVER lotus_data';
        $sql['server'] = 'DROP SERVER lotus_data';
        $sql['lotusSchema'] = 'DROP SCHEMA "' . $localSchema . '"';
        $sql['extension'] = 'DROP EXTENSION postgres_fdw';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

    }
    
}