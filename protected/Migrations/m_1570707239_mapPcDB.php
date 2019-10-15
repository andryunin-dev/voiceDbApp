<?php

namespace App\Migrations;

use function T4\app;
use T4\Orm\Migration;

class m_1570707239_mapPcDB
    extends Migration
{

    public function up()
    {
        $user = app()->config->db->pcData->user;
        $password = app()->config->db->pcData->password;

        $sql['create schema'] = 'CREATE SCHEMA IF NOT EXISTS pc';
        $sql['create server'] = '
            CREATE SERVER IF NOT EXISTS pc_data
            FOREIGN DATA WRAPPER postgres_fdw
            OPTIONS (host \'ts-it12.rs.ru\', dbname \'SysInfo\')';
        $sql['create user'] = '
            CREATE USER MAPPING FOR CURRENT_USER
            SERVER pc_data
            OPTIONS (user \''.$user.'\', password \''.$password.'\')';
        $sql['import foreign schema'] = 'IMPORT FOREIGN SCHEMA "public"
            LIMIT TO ("device", "ip_mac")
            FROM SERVER pc_data
            INTO pc';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop foreign table'] = 'DROP FOREIGN TABLE IF EXISTS pc.device, pc.ip_mac';
        $sql['drop user'] = 'DROP USER MAPPING FOR CURRENT_USER SERVER pc_data';
        $sql['drop server'] = 'DROP SERVER IF EXISTS pc_data';
        $sql['drop schema'] = 'DROP SCHEMA IF EXISTS pc';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
