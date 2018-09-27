<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1538028567_change1CDevelopersView
    extends Migration
{

    public function up()
    {
        $sql['drop old view'] = 'DROP VIEW view.dev_geo_1c_info';
        $sql['change view'] = '
        CREATE OR REPLACE VIEW view.dev_geo_1c_info AS
            WITH locations AS (
              SELECT
                     geo_offices.__id AS office_id,
                     geo_offices.title AS office,
                     geo_city.title AS city
              FROM company.offices AS geo_offices
              JOIN geolocation.addresses AS geo_addr ON geo_offices.__address_id = geo_addr.__id
              JOIN geolocation.cities AS geo_city ON geo_addr.__city_id = geo_city.__id
              JOIN geolocation.regions AS geo_reg ON geo_city.__region_id = geo_reg.__id
            ),
            hw_devices AS (
                SELECT
                       eq_apps.__id AS device_id,
                       eq_apps.details::json->>\'hostname\' AS hostname,
                       locations.city AS city,
                       locations.office AS office,
                       eq_pl.title AS device,
                       eq_apps."lastUpdate" AS last_update,
                       (date_part(\'epoch\', now() - eq_apps."lastUpdate")/3600)::int AS age_hours,
                       eq_pl_items."serialNumber" AS serial_number,
                       app_types.type,
                       name AS dev_name,
                       prefix::text || "phoneDN"::text AS full_dn,
                       "alertingName" AS alerting_name,
                       "cdpNeighborIP" AS switch_ip,
                       "cdpNeighborPort" AS switch_port
                FROM equipment.appliances AS eq_apps
                JOIN equipment."platformItems" AS eq_pl_items ON eq_apps.__platform_item_id = eq_pl_items.__id
                JOIN equipment.platforms AS eq_pl ON eq_pl_items.__platform_id = eq_pl.__id
                JOIN equipment."applianceTypes" AS app_types ON eq_apps.__type_id = app_types.__id
                JOIN equipment.vendors AS eq_vendor ON eq_apps.__vendor_id = eq_vendor.__id
                LEFT JOIN equipment."phoneInfo" AS eq_phone_info ON eq_apps.__id = eq_phone_info.__appliance_id
                LEFT JOIN locations ON locations.office_id = eq_apps.__location_id
                WHERE eq_pl."isHW" = TRUE
              ),
            inventory_1c AS (
                SELECT
                       __voice_appliance_id AS app_id,
                       "inventoryNumber" AS inventory_number
                FROM storage_1c."appliances1C"
                JOIN storage_1c."inventoryItem1C" ON "appliances1C".__inventory_item_id = "inventoryItem1C".__id
              ),
            ip_to_dev_info AS (
                SELECT
                       "ipAddress",
                       hw_devices.device AS dev_title,
                       hw_devices.hostname,
                       inventory_1c.inventory_number
                FROM equipment."dataPorts" AS eq_port_info
                JOIN hw_devices ON hw_devices.device_id = eq_port_info.__appliance_id
                LEFT JOIN inventory_1c ON inventory_1c.app_id = hw_devices.device_id
              )
            
            SELECT
                   hw_devices.*,
                   inventory_1c.inventory_number AS dev_inventory_number,
                   cdp_info.dev_title AS switch_model,
                   cdp_info.inventory_number AS switch_invenroty_number,
                   cdp_info.hostname AS switch_hostname
            FROM hw_devices
            LEFT JOIN inventory_1c ON hw_devices.device_id = inventory_1c.app_id
            LEFT JOIN (
                SELECT *
                FROM ip_to_dev_info
                ) AS cdp_info ON hw_devices.switch_ip = cdp_info."ipAddress"';


        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop old view'] = 'DROP VIEW view.dev_geo_1c_info';
        $sql['revert view'] = '
        CREATE OR REPLACE VIEW view.dev_geo_1c_info AS
            WITH locations AS (
              SELECT
                     geo_offices.__id AS office_id,
                     geo_offices.title AS office,
                     geo_city.title AS city
              FROM company.offices AS geo_offices
              JOIN geolocation.addresses AS geo_addr ON geo_offices.__address_id = geo_addr.__id
              JOIN geolocation.cities AS geo_city ON geo_addr.__city_id = geo_city.__id
              JOIN geolocation.regions AS geo_reg ON geo_city.__region_id = geo_reg.__id
            ),
            hw_devices AS (
                SELECT
                       eq_apps.__id AS device_id,
                       eq_apps.details::json->>\'hostname\' AS hostname,
                       locations.city AS city,
                       locations.office AS office,
                       eq_pl.title AS device,
                       eq_apps."lastUpdate" AS last_update,
                       eq_pl_items."serialNumber" AS serial_number,
                       app_types.type,
                       name AS dev_name,
                       prefix::text || "phoneDN"::text AS full_dn,
                       "alertingName" AS alerting_name,
                       "cdpNeighborIP" AS switch_ip,
                       "cdpNeighborPort" AS switch_port
                FROM equipment.appliances AS eq_apps
                JOIN equipment."platformItems" AS eq_pl_items ON eq_apps.__platform_item_id = eq_pl_items.__id
                JOIN equipment.platforms AS eq_pl ON eq_pl_items.__platform_id = eq_pl.__id
                JOIN equipment."applianceTypes" AS app_types ON eq_apps.__type_id = app_types.__id
                JOIN equipment.vendors AS eq_vendor ON eq_apps.__vendor_id = eq_vendor.__id
                LEFT JOIN equipment."phoneInfo" AS eq_phone_info ON eq_apps.__id = eq_phone_info.__appliance_id
                LEFT JOIN locations ON locations.office_id = eq_apps.__location_id
                WHERE eq_pl."isHW" = TRUE
              ),
            inventory_1c AS (
                SELECT
                       __voice_appliance_id AS app_id,
                       "inventoryNumber" AS inventory_number
                FROM storage_1c."appliances1C"
                JOIN storage_1c."inventoryItem1C" ON "appliances1C".__inventory_item_id = "inventoryItem1C".__id
              ),
            ip_to_dev_info AS (
                SELECT
                       "ipAddress",
                       hw_devices.device AS dev_title,
                       hw_devices.hostname,
                       inventory_1c.inventory_number
                FROM equipment."dataPorts" AS eq_port_info
                JOIN hw_devices ON hw_devices.device_id = eq_port_info.__appliance_id
                LEFT JOIN inventory_1c ON inventory_1c.app_id = hw_devices.device_id
              )
            
            SELECT
                   hw_devices.*,
                   inventory_1c.inventory_number AS dev_inventory_number,
                   cdp_info.dev_title AS switch_model,
                   cdp_info.inventory_number AS switch_invenroty_number,
                   cdp_info.hostname AS switch_hostname
            FROM hw_devices
            LEFT JOIN inventory_1c ON hw_devices.device_id = inventory_1c.app_id
            LEFT JOIN (
                SELECT *
                FROM ip_to_dev_info
                ) AS cdp_info ON hw_devices.switch_ip = cdp_info."ipAddress"';


        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}