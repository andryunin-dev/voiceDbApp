<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1571052498_createMappedPcDataView
    extends Migration
{

    public function up()
    {
        $sql['create view mappedPcData'] = 'CREATE VIEW view."mappedPcData" AS
            WITH
              switch AS (
                WITH
                  appliance AS (
                    WITH
                      management_dataport AS ( SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" WHERE "isManagement" IS TRUE ),
                      sw AS ( SELECT __id, __platform_item_id, details, __location_id FROM equipment.appliances
                              WHERE __type_id = ( SELECT __id FROM equipment."applianceTypes" WHERE type = \'switch\') )
                    SELECT sw.__id, sw.__platform_item_id, sw.details, sw.__location_id, management_dataport."ipAddress"
                    FROM sw
                    JOIN management_dataport ON sw.__id = management_dataport.__appliance_id
                  ),
                  "appliance1C" AS (
                    WITH "invItem1C" AS ( SELECT __id, "inventoryNumber" FROM storage_1c."inventoryItem1C" )
                    SELECT "app1C".__voice_appliance_id, "invItem1C"."inventoryNumber"
                    FROM storage_1c."appliances1C" AS "app1C"
                    LEFT JOIN "invItem1C" ON "app1C".__inventory_item_id = "invItem1C".__id
                  ),
                  platformItem AS (
                    SELECT plItem.__id, platform.title, plItem."serialNumber" FROM equipment."platformItems" plItem
                    LEFT JOIN equipment.platforms platform ON plItem.__platform_id = platform.__id
                  ),
                  location AS (
                    WITH reg_center AS ( SELECT DISTINCT lotus_id, reg_center AS title FROM view.lotus_db_data )
                    SELECT office.__id AS office_id, reg_center.title AS reg_center,  city.title AS city, office.title AS office
                    FROM company.offices office
                    LEFT JOIN geolocation.addresses ON office.__address_id = addresses.__id
                    LEFT JOIN geolocation.cities city ON addresses.__city_id = city.__id
                    LEFT JOIN reg_center ON office."lotusId" = reg_center.lotus_id
                  )
                SELECT
                  trim(\'"\' FROM cast (appliance.details->\'hostname\' AS citext)) AS hostname,
                  platformItem.title AS model,
                  platformItem."serialNumber",
                  "appliance1C"."inventoryNumber",
                  appliance."ipAddress",
                  location.reg_center,
                  location.city,
                  location.office
                FROM appliance
                LEFT JOIN "appliance1C" ON appliance.__id = "appliance1C".__voice_appliance_id
                LEFT JOIN platformItem ON platformItem.__id = appliance.__platform_item_id
                LEFT JOIN location ON appliance.__location_id = location.office_id
              ),
              pc AS (
                WITH
                  map_switch AS (
                    WITH stat AS (
                      SELECT t2.sw_hostname, t2.sw_interface, count(client_mac) AS clients_mac_amount
                      FROM view.pc__ip_mac AS t2
                      GROUP BY t2.sw_hostname, t2.sw_interface
                      ORDER BY t2.sw_hostname, t2.sw_interface
                    )
                    SELECT t1.client_mac, t1.sw_interface, t1.sw_hostname, clients_mac_amount FROM view.pc__ip_mac AS t1
                    LEFT JOIN stat ON t1.sw_hostname = stat.sw_hostname AND t1.sw_interface = stat.sw_interface
                  )
                SELECT
                  pc.*,
                  map_switch.sw_interface AS sw_port,
                  map_switch.clients_mac_amount,
                  map_switch.sw_hostname
                FROM view.pc__device AS pc
                LEFT JOIN map_switch ON pc.mac = map_switch.client_mac
              ),
              phone AS (
                SELECT
                  model,
                  (prefix || "phoneDN") AS dn,
                  "cdpNeighborDeviceId" AS sw_hostname,
                  "cdpNeighborPort" AS sw_port
                FROM equipment."phoneInfo"
                WHERE "cdpNeighborDeviceId" NOTNULL AND "cdpNeighborDeviceId" NOT LIKE (\'\') AND "cdpNeighborPort" NOTNULL AND "cdpNeighborPort" NOT LIKE (\'\')
              )
            SELECT
              pc.mac AS pc_mac,
              pc.os_name AS pc_os_name,
              pc.os_edition AS pc_os_edition,
              pc.os_version AS pc_os_version,
              pc.os_sp AS pc_os_sp,
              pc.os_bits AS pc_os_bits,
              pc.kernel AS pc_kernel,
              pc.mstsc AS pc_mstsc,
              pc.name AS pc_name,
              pc.last_ip AS pc_ip,
              pc.drive_serial AS pc_drive_serial,
              pc.drive_size AS pc_drive_size,
              pc.cpu AS pc_cpu,
              pc.memory AS pc_memory,
              pc.last_update AS pc_last_update,
              pc.inv_number AS pc_inv_number,
              pc.inv_update AS pc_inv_update,
              pc.last_refresh AS pc_last_refresh,
              pc.sw_port,
              pc.clients_mac_amount,
              switch.hostname AS sw_hostname,
              switch.model AS sw_model,
              switch."serialNumber" AS "sw_serialNumber",
              switch."inventoryNumber" AS "sw_inventoryNumber",
              switch."ipAddress" AS "sw_ipAddress",
              switch.reg_center AS sw_reg_center,
              switch.city AS sw_city,
              switch.office AS sw_office,
              phone.model AS phone_model,
              phone.dn AS phone_dn
            FROM pc
            LEFT JOIN switch ON pc.sw_hostname LIKE switch.hostname
            LEFT JOIN phone ON pc.sw_hostname = phone.sw_hostname AND (substring(lower(pc.sw_port), \'^..\') = substring(lower(phone.sw_port), \'^..\')) AND (substring(pc.sw_port, \'\d.+$\') = substring(phone.sw_port, \'\d.+$\')) AND pc.clients_mac_amount < 3';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop view mappedPcData'] = 'DROP VIEW IF EXISTS view.mappedPcData';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
