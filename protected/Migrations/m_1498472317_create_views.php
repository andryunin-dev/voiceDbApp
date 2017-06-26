<?php

namespace App\Migrations;

use T4\Orm\Migration;

/**
 * Create 2 views - geo_dev and geo_dev_module_port
 *
 * Class m_1498472317_create_views
 * @package App\Migrations
 */
class m_1498472317_create_views
    extends Migration
{

    public function up()
    {
        $sql['create_schema'] = 'CREATE SCHEMA IF NOT EXISTS view';
        $sql['drop_view_geo_dev'] = 'DROP VIEW IF EXISTS view.geo_dev';
        $sql['drop_view_geo_dev_module_port'] = 'DROP VIEW IF EXISTS view.geo_dev_module_port';
        $sql['create_geo_dev_view'] = '
    CREATE OR REPLACE VIEW view.geo_dev AS
    WITH geo AS (
        SELECT
            region.title AS region,
            region.__id AS region_id,
            city.title AS city,
            city.__id AS city_id,
            offices.title AS office,
            offices.__id AS office_id,
            offices."lotusId" AS "lotusId",
            offices.comment AS "officeComment",
            offices.details AS "officeDetails",
            address.address AS "officeAddress"

        FROM company.offices AS offices
            JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
            JOIN geolocation.cities AS city ON city.__id = address.__city_id
            JOIN geolocation.regions AS region ON region.__id = city.__region_id
    ), devices AS (
        SELECT
            appliances.__id AS appliance_id,
            appliances.__location_id AS location_id,
            appliances."lastUpdate" AS "appLastUpdate",
            appliances."inUse" AS "appInUse",
            appliances.details::jsonb->>\'hostname\' AS hostname,
            appliances.details AS "applDetails",
            appliances."comment" AS "appComment",

            clusters.__id AS cluster_id,
            clusters.title AS "clusterTitle",
            clusters.details AS "clusterDetails",
            clusters.comment AS "clusterComment",

            "platformVendor".__id AS "platformVendor_id",
            "platformVendor".title AS "platformVendor",
            "platformItem".__id AS "platformItem_id",
            platform.title AS "platformTitle",

            "softwareVendor".__id AS "softwareVendor_id",
            "softwareVendor".title AS "softwareVendor",
            "softwareItem".__id AS "softwareItem_id",
            software.title AS "softwareTitle",
            "softwareItem".version AS "softwareVersion"
        FROM equipment.appliances AS appliances
            LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
            LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
            LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id

            LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
            LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
            LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
            LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id

    )
    SELECT *
    FROM geo
        LEFT JOIN devices ON geo.office_id = devices.location_id
        ';

        $sql['create_geo_dev_module_port_view'] = '
    CREATE OR REPLACE VIEW view.geo_dev_module_port AS
    WITH geo AS (
        SELECT
            region.title AS region,
            region.__id AS region_id,
            city.title AS city,
            city.__id AS city_id,
            offices.title AS office,
            offices.__id AS office_id,
            offices."lotusId" AS "lotusId",
            offices.comment AS "officeComment",
            offices.details AS "officeDetails",
            address.address AS "officeAddress"

        FROM company.offices AS offices
            JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
            JOIN geolocation.cities AS city ON city.__id = address.__city_id
            JOIN geolocation.regions AS region ON region.__id = city.__region_id
    ), devices AS (
        SELECT
            appliances.__id AS appliance_id,
            appliances.__location_id AS location_id,
            appliances."lastUpdate" AS "appLastUpdate",
            appliances."inUse" AS "appInUse",
            appliances.details::jsonb->>\'hostname\' AS hostname,
            appliances.details AS "applDetails",
            appliances."comment" AS "appComment",

            clusters.__id AS cluster_id,
            clusters.title AS "clusterTitle",
            clusters.details AS "clusterDetails",
            clusters.comment AS "clusterComment",

            "platformVendor".__id AS "platformVendor_id",
            "platformVendor".title AS "platformVendor",
            "platformItem".__id AS "platformItem_id",
            platform.title AS "platformTitle",

            "softwareVendor".__id AS "softwareVendor_id",
            "softwareVendor".title AS "softwareVendor",
            "softwareItem".__id AS "softwareItem_id",
            software.title AS "softwareTitle",
            "softwareItem".version AS "softwareVersion"
        FROM equipment.appliances AS appliances
            LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
            LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
            LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id

            LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
            LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
            LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
            LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id

    ), module_info AS (
        SELECT
            "moduleItems".__appliance_id AS __applianse_id,
            "moduleItems".__id AS "itemId",
            "moduleItems"."serialNumber" AS "serialNumber",
            "moduleItems"."inventoryNumber" AS "inventoryNumber",
            "moduleItems"."details" AS "itemDetails",
            "moduleItems"."comment" AS "itemComment",
            "moduleItems"."inUse" AS "inUse",
            "moduleItems"."notFound" AS "notFound",
            "moduleItems"."lastUpdate" AS "itemLastUpdate",
            "modules".__id AS "__module_id",
            "modules".title AS "moduleTitle",
            "modules".description AS "moduleDescription"

        FROM equipment."moduleItems" AS "moduleItems"
            JOIN equipment.modules AS modules ON "moduleItems".__module_id = modules.__id
    ), port_info AS (
        SELECT
            "dPorts".__appliance_id AS __applianse_id,
            "dPorts".__network_id AS __network_id,
            "dPorts"."ipAddress" AS "ipAddress",
            "dPorts"."masklen" AS "masklen",
            "dPorts"."macAddress" AS "macAddress",
            "dPorts"."details" AS "details",
            "dPorts"."comment" AS "comment",
            "dPorts"."isManagement" AS "isManagement",
            "dPorts"."__type_port_id" AS "__type_port_id",
            "dPortTypes".type AS "portType"
        FROM equipment."dataPorts" AS "dPorts"
            JOIN equipment."dataPortTypes" AS "dPortTypes" ON "dPorts".__type_port_id = "dPortTypes".__id
    )
    SELECT * ,
        ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM (
                                                                SELECT *
                                                                FROM module_info
                                                                WHERE appliance_id = module_info.__applianse_id
                                                                ORDER BY module_info."moduleTitle") AS t
        ) AS "moduleInfo",
        ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM (
                                                                SELECT *
                                                                FROM port_info
                                                                WHERE appliance_id = port_info.__applianse_id
                                                                ORDER BY port_info."ipAddress") AS t
        ) AS "portInfo"
    FROM geo
        LEFT JOIN devices ON geo.office_id = devices.location_id
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop_schema_view_cascade'] = 'DROP SCHEMA IF EXISTS view CASCADE';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
    }
    
}