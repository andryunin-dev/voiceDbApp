<?php

namespace App\Migrations;

use T4\Orm\Migration;
use function T4\app;

class m_1584692097_changeMapPcDb
    extends Migration
{

    public function up()
    {
        $user = app()->config->db->pcData->user;
        $password = app()->config->db->pcData->password;
        $host = app()->config->db->pcData->host;

        $sql['drop view mappedPcData'] = 'DROP VIEW IF EXISTS view."mappedPcData"';
        $sql['drop foreign tables'] = 'DROP FOREIGN TABLE IF EXISTS pc.pc_data_view, pc.ip_mac';
        $sql['drop user'] = 'DROP USER MAPPING IF EXISTS FOR CURRENT_USER SERVER pc_data';
        $sql['drop server'] = 'DROP SERVER IF EXISTS pc_data';

        $sql['create server'] = '
            CREATE SERVER pc_data
            FOREIGN DATA WRAPPER postgres_fdw
            OPTIONS (host \''.$host.'\', dbname \'SysInfo\')';
        $sql['create user'] = '
            CREATE USER MAPPING FOR CURRENT_USER
            SERVER pc_data
            OPTIONS (user \''.$user.'\', password \''.$password.'\')';
        $sql['import foreign table ip_mac'] = 'IMPORT FOREIGN SCHEMA "public"
            LIMIT TO ("ip_mac")
            FROM SERVER pc_data
            INTO pc';
        $sql['import foreign table pc_data_view'] = 'IMPORT FOREIGN SCHEMA "view"
            LIMIT TO ("pc_data_view")
            FROM SERVER pc_data
            INTO pc';
        $sql['create view mappedPcData'] = 'CREATE VIEW view."mappedPcData" AS
            WITH employee AS ( SELECT net_name, domain, (surname || \' \' || name || \' \' || patronymic) AS employee, position, division
                               FROM lotus.employees WHERE net_name IS NOT NULL AND net_name NOT LIKE (\'\') ),
                 switch AS ( WITH sw AS ( SELECT __id, trim(\'"\' FROM (details->\'hostname\')::citext) AS hostname, __platform_item_id, __location_id
                                          FROM equipment.appliances AS appliance
                                          WHERE appliance.__type_id = ( SELECT "applianceTypes".__id  FROM equipment."applianceTypes" WHERE type LIKE \'switch\' )
                                            AND ((date_part(\'epoch\'::TEXT, age(now(), "lastUpdate")) / (3600)::DOUBLE PRECISION))::INTEGER < 73 ),
                                  dataport AS ( SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" ),
                                  platform AS ( WITH platform AS ( SELECT __id, title FROM equipment.platforms )
                                                SELECT "platformItem".__id, "serialNumber", title AS model
                                                FROM equipment."platformItems" AS "platformItem"
                                                LEFT JOIN platform ON "platformItem".__platform_id = platform.__id ),
                                  inventory AS ( WITH inv AS ( SELECT __id, "inventoryNumber" FROM storage_1c."inventoryItem1C" )
                                                 SELECT __voice_appliance_id, "inventoryNumber"
                                                 FROM storage_1c."appliances1C" AS app_1c
                                                 LEFT JOIN inv ON app_1c.__inventory_item_id = inv.__id ),
                                  location AS ( WITH address AS (SELECT * FROM geolocation.addresses),
                                                     city AS (SELECT * FROM geolocation.cities),
                                                     lotus_db AS ( SELECT * FROM lotus.locations )
                                                SELECT office.__id, office.title AS office, lotus_db.reg_center, city.title AS city, address.address
                                                FROM company.offices AS office
                                                LEFT JOIN address ON office.__address_id = address.__id
                                                LEFT JOIN city ON address.__city_id = city.__id
                                                LEFT JOIN lotus_db ON lotus_db.lotus_id = office."lotusId" )
                             SELECT hostname, model, "serialNumber", "ipAddress", "inventoryNumber", location.office, location.reg_center, location.city, location.address
                             FROM sw
                             LEFT JOIN dataport ON dataport.__appliance_id = sw.__id
                             LEFT JOIN platform ON sw.__platform_item_id = platform.__id
                             LEFT JOIN inventory ON sw.__id = inventory.__voice_appliance_id
                             LEFT JOIN location ON sw.__location_id = location.__id ),
                 phone AS ( WITH dev AS ( WITH statistics AS ( SELECT sw_hostname, sw_interface, count(client_mac) AS client_mac_amount
                                                               FROM pc.ip_mac
                                                               WHERE sw_hostname IS NOT NULL
                                                               GROUP BY sw_hostname, sw_interface )
                                          SELECT client_ip, ip_mac.sw_hostname, ip_mac.sw_interface
                                          FROM pc.ip_mac AS ip_mac
                                          LEFT JOIN statistics ON ip_mac.sw_hostname = statistics.sw_hostname AND ip_mac.sw_interface = statistics.sw_interface
                                          WHERE client_mac_amount < 3 ),
                                 ph AS ( WITH appliance AS ( SELECT __id FROM equipment.appliances app
                                                             WHERE app.__type_id = ( SELECT "applianceTypes".__id FROM equipment."applianceTypes" WHERE type LIKE \'phone\' )
                                                               AND ((date_part(\'epoch\'::TEXT, age(now(), app."lastUpdate")) / (3600)::DOUBLE PRECISION))::INTEGER < 73),
                                              phone_info AS ( SELECT __appliance_id, model, (prefix || "phoneDN") AS dn  FROM equipment."phoneInfo" ),
                                              dataport AS ( SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" WHERE "isManagement" IS TRUE )
                                         SELECT model, dn, "ipAddress"
                                         FROM appliance
                                         LEFT JOIN  phone_info ON phone_info.__appliance_id = appliance.__id
                                         JOIN dataport ON dataport.__appliance_id  = appliance.__id )
                            SELECT sw_hostname, sw_interface, model AS phone_model, dn AS phone_dn
                            FROM dev
                            JOIN ph ON dev.client_ip = ph."ipAddress" )
            SELECT pc.*,
                   (CASE WHEN pc.pc_ip::inet << \'10.80.128.0/18\'::inet THEN 1
                         WHEN pc.pc_ip::inet << \'10.81.128.0/18\'::inet THEN 1
                         ELSE 0 END) AS vpn,
                   employee.employee,
                   employee.position,
                   employee.division,
                   switch.model AS sw_model,
                   switch."serialNumber" AS "sw_serialNumber",
                   switch."inventoryNumber" AS "sw_inventoryNumber",
                   switch.office AS sw_office,
                   switch.reg_center AS sw_reg_center,
                   switch.city AS sw_city,
                   switch.address AS sw_address,
                   phone.phone_model,
                   phone.phone_dn
            FROM pc.pc_data_view AS pc
            LEFT JOIN employee ON pc.merged_login = employee.net_name AND pc.merged_domain = employee.domain
            LEFT JOIN switch ON host(pc.sw_ip)::inet = switch."ipAddress"
            LEFT JOIN phone ON pc.sw_hostname = phone.sw_hostname AND pc.sw_interface = phone.sw_interface';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
    }
    
}
