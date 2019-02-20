<?php

namespace App\Migrations;

use T4\Console\Application;
use T4\Orm\Migration;

class m_1543823226_changeMapLotusDB
    extends Migration
{

    public function up()
    {
        $app = Application::instance();
        $lotusConfig = $app->config->db->lotusData;
        $host = $lotusConfig->host;
        $dbName = 'LotusData';
        $remoteSchema = 'public';
        $remoteTable = 'phone_book';
        $user = $lotusConfig->user;
        $password = $lotusConfig->password;
        $localSchema = 'lotus';

        $sql['create_server_lotus_data_phone_book'] = 'CREATE SERVER lotus_data_phone_book FOREIGN DATA WRAPPER  postgres_fdw OPTIONS (host \'' . $host .'\', dbname \'' . $dbName .'\')';
        $sql['create_userMapping_for_lotus_data_phone_book'] = 'CREATE USER MAPPING FOR CURRENT_USER SERVER lotus_data_phone_book OPTIONS (user \'' . $user . '\', password \'' . $password .'\')';
        $sql['import_foreignTable'] = 'IMPORT FOREIGN SCHEMA "'. $remoteSchema . '" LIMIT TO ("' . $remoteTable . '") FROM SERVER lotus_data_phone_book INTO "' . $localSchema . '"';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $remoteTable = 'phone_book';
        $localSchema = 'lotus';

        $sql['drop_foreignTable'] = 'DROP FOREIGN TABLE "' . $localSchema . '"."' . $remoteTable . '"';
        $sql['drop_userMapping_for_lotus_data_phone_book'] = 'DROP USER MAPPING FOR CURRENT_USER SERVER lotus_data_phone_book';
        $sql['drop_server_lotus_data_phone_book'] = 'DROP SERVER lotus_data_phone_book';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
