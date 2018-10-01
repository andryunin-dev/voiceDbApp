<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1538049410_alterPhoneInfoTable
    extends Migration
{

    public function up()
    {
        $sql['drop_view__view.dev_geo_1c_info'] = 'DROP VIEW IF EXISTS view.dev_geo_1c_info';
        $sql['drop_view__view.dev_port_phone_info_geo'] = 'DROP VIEW IF EXISTS view.dev_port_phone_info_geo';
        $sql['drop indexOnLotusId'] = 'DROP INDEX IF EXISTS idx_dev_phone_info_geo_mat_lotus_id';
        $sql['drop_view__view.dev_phone_info_geo_mat'] = 'DROP MATERIALIZED VIEW IF EXISTS view.dev_phone_info_geo_mat';
        $sql['drop_view__view.dev_phone_info_geo'] = 'DROP VIEW IF EXISTS view.dev_phone_info_geo';
        $sql['drop_view__view.dev_module_port_geo'] = 'DROP VIEW IF EXISTS view.dev_module_port_geo';

        $sql['alter_table__equipment.phoneInfo'] =
            'ALTER TABLE equipment."phoneInfo"
                ALTER COLUMN "prefix" SET DATA TYPE citext,
                ALTER COLUMN "phoneDN" SET DATA TYPE citext';

        // --------------
        $targetView = 'view.dev_module_port_geo';
        $sql['recovery_view__view.dev_module_port_geo'] =
            'CREATE OR REPLACE VIEW '.$targetView.' AS
                SELECT
                    region,
                    region_id,
                    city,
                    city_id,
                    office,
                    office_id,
                    "lotusId",
                    "officeAddress",
                    "officeComment",
                    "officeDetails",
            
                    appliances.__id AS appliance_id,
                    appliances."lastUpdate" AS "appLastUpdate",
                    (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
                    appliances."inUse" AS "appInUse",
                    CAST(appliances.details::jsonb->>\'hostname\' AS citext) AS hostname,
                    CASE WHEN ("phoneInfo".prefix IS NOT NULL) OR ("phoneInfo"."phoneDN" IS NOT NULL )
                        THEN
                            CAST(appliances.details::jsonb->>\'hostname\'||
                                 \',(DN \' ||
                                 "phoneInfo".prefix ||
                                 "phoneInfo"."phoneDN" ||
                                 \')\' AS citext)
                    ELSE
                        CAST(appliances.details::jsonb->>\'hostname\' AS citext)
                    END
                        AS hostname_dn,
                    appliances.details AS "appDetails",
                    appliances."comment" AS "appComment",
                    "appTypes".__id AS "appType_id",
                    "appTypes".type AS "appType",
                    "appTypes"."sortOrder" AS "appSortOrder",
                    clusters.__id AS cluster_id,
                    clusters.title AS "clusterTitle",
                    clusters.details AS "clusterDetails",
                    clusters.comment AS "clusterComment",
                    "platformVendor".__id AS "platformVendor_id",
                    "platformVendor".title AS "platformVendor",
                    "platformItem".__id AS "platformItem_id",
                    platform.title AS "platformTitle",
                    platform.__id AS "platform_id",
                    platform."isHW" AS "isHW",
                    "platformItem"."serialNumber" AS "platformSerial",
                    "softwareVendor".__id AS "softwareVendor_id",
                    "softwareVendor".title AS "softwareVendor",
                    "softwareItem".__id AS "softwareItem_id",
                    software.__id AS "software_id",
                    software.title AS "softwareTitle",
                    "softwareItem".version AS "softwareVersion",
                    CASE WHEN COALESCE("softwareItem".version, \'\') = \'\'
                        THEN
                            CAST(software.title AS citext)
                    ELSE
                        CAST(software.title || \' \' || "softwareItem".version AS citext)
                    END
                        AS "softwareAndVersion",
            
                    ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                        (SELECT
                             "moduleItems".__appliance_id AS appliance_id,
                             "moduleItems".__id AS "moduleItem_id",
                             "moduleItems"."serialNumber" AS "serialNumber",
                             "moduleItems"."inventoryNumber" AS "inventoryNumber",
                             "moduleItems"."details" AS "details",
                             "moduleItems"."comment" AS "comment",
                             "moduleItems"."inUse" AS "inUse",
                             "moduleItems"."notFound" AS "notFound",
                             "moduleItems"."lastUpdate" AS "lastUpdate",
                             (EXTRACT(EPOCH FROM age(now(), "moduleItems"."lastUpdate"))/3600)::INT AS "moduleItemAge",
                             "modules".__id AS "module_id",
                             "modules".title AS "title",
                             "modules".description AS "description"
                         FROM equipment."moduleItems" AS "moduleItems"
                             JOIN equipment.modules AS modules ON "moduleItems".__module_id = modules.__id
                         WHERE appliances.__id = "moduleItems".__appliance_id
                         ORDER BY module_id) AS t
                    ) AS "moduleInfo",
                    ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                        (SELECT
                             "dPorts".__appliance_id AS appliance_id,
                             "dPorts".__network_id AS network_id,
                             "dPorts"."ipAddress" AS "ipAddress",
                             "dPorts"."masklen" AS "masklen",
                             "dPorts"."macAddress" AS "macAddress",
                             "dPorts"."details" AS "details",
                             "dPorts"."comment" AS "comment",
                             "dPorts"."isManagement" AS "isManagement",
                             "dPorts"."__type_port_id" AS "portType_id",
                             "dPortTypes".type AS "portType"
                         FROM equipment."dataPorts" AS "dPorts"
                             JOIN equipment."dataPortTypes" AS "dPortTypes" ON "dPorts".__type_port_id = "dPortTypes".__id
                         WHERE appliances.__id = "dPorts".__appliance_id
                         ORDER BY "ipAddress") AS t
                    ) AS "portInfo",
                    (SELECT "ipAddress" FROM equipment."dataPorts" AS "dPorts"
                    WHERE appliances.__id = "dPorts".__appliance_id AND "isManagement" = TRUE
                     ORDER BY "ipAddress" LIMIT 1
                    ) AS "managementIp",
                    "inventoryItem1C"."inventoryNumber" AS "inventoryNumber",
                    mols1C.fio AS "responsiblePerson",
                    "devCallsStats".last_call_day,
                    "devCallsStats".d0_calls_amount,
                    "devCallsStats".m0_calls_amount,
                    "devCallsStats".m1_calls_amount,
                    "devCallsStats".m2_calls_amount
  
                FROM equipment.appliances AS appliances
                    LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
                    LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
                    LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
                    LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
            
                    LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
                    LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
                    LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
                    LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id
            
                    LEFT JOIN equipment."phoneInfo" AS "phoneInfo" ON "phoneInfo".__appliance_id = appliances.__id
            
                    LEFT JOIN storage_1c."appliances1C" AS appliances1C ON appliances.__id = appliances1C.__voice_appliance_id
                    LEFT JOIN storage_1c."inventoryItem1C" AS "inventoryItem1C" ON appliances1C.__inventory_item_id = "inventoryItem1C".__id
                    LEFT JOIN storage_1c.mols AS mols1C ON "inventoryItem1C".__mol_id = mols1C.__id
            
                    LEFT JOIN (
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
                              ) AS geo
                        ON geo.office_id = appliances.__location_id
                        
                    LEFT JOIN view.dev_calls_stats AS "devCallsStats" ON "devCallsStats".appliance_id = appliances.__id'
        ;


        // ----------------------
        $targetView = 'view.dev_phone_info_geo';
        $dependentView = 'view.dev_phone_info_geo_mat';
        $index = 'idx_dev_phone_info_geo_mat_lotus_id';
        $sql['recovery - '.$targetView] =
            'CREATE OR REPLACE VIEW '.$targetView.' AS 
                SELECT
                    "lotus_regCenter",
                    region,
                    lotus_region,
                    region_id,
                    city,
                    lotus_city,
                    city_id,
                    office,
                    lotus_office,
                    office_id,
                    "lotusId",
                    "lotus_lotusId",
                    "officeAddress",
                    "lotus_officeAddress",
                    "officeComment",
                    "officeDetails",
                    lotus_employees,
                    "lotus_lastRefresh",
                    
                    appliances.__id AS appliance_id,
                    appliances."lastUpdate" AS "appLastUpdate",
                    (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
                    appliances."inUse" AS "appInUse",
                    CAST(appliances.details::jsonb->>\'hostname\' AS citext) AS hostname,
                    appliances.details AS "appDetails",
                    appliances."comment" AS "appComment",
                    "appTypes".__id AS "appType_id",
                    "appTypes".type AS "appType",
                    clusters.__id AS cluster_id,
                    clusters.title AS "clusterTitle",
                    clusters.details AS "clusterDetails",
                    clusters.comment AS "clusterComment",
                    "platformVendor".__id AS "platformVendor_id",
                    "platformVendor".title AS "platformVendor",
                    "platformItem".__id AS "platformItem_id",
                    platform.title AS "platformTitle",
                    platform."isHW" AS "isHW",
                    platform.__id AS "platform_id",
                    "platformItem"."serialNumber" AS "platformSerial",
                    "softwareVendor".__id AS "softwareVendor_id",
                    "softwareVendor".title AS "softwareVendor",
                    "softwareItem".__id AS "softwareItem_id",
                    software.__id AS "software_id",
                    software.title AS "softwareTitle",
                    "softwareItem".version AS "softwareVersion",
                    
                    name,
                    model,
                    prefix,
                    "phoneDN",
                    status,
                    "phoneInfo".description AS "phoneDescription",
                    css,
                    "devicePool",
                    "alertingName",
                    partition,
                    timezone,
                    "dhcpEnabled",
                    "dhcpServer",
                    "domainName",
                    "tftpServer1",
                    "tftpServer2",
                    "defaultRouter",
                    "dnsServer1",
                    "dnsServer2",
                    "callManager1",
                    "callManager2",
                    "callManager3",
                    "callManager4",
                    "vlanId",
                    "userLocale",
                    "cdpNeighborDeviceId",
                    "cdpNeighborIP",
                    "cdpNeighborPort",
                    "publisherIp",
                    "unknownLocation",
                    (SELECT "ipAddress" FROM equipment."dataPorts" AS "dPorts"
                    WHERE appliances.__id = "dPorts".__appliance_id AND "isManagement" = TRUE
                     ORDER BY "ipAddress" LIMIT 1
                    ) AS "managementIp",
                    
                    "devCallsStats".last_call_day,
                    "devCallsStats".d0_calls_amount,
                    "devCallsStats".m0_calls_amount,
                    "devCallsStats".m1_calls_amount,
                    "devCallsStats".m2_calls_amount,
                    "inventoryItem1C"."inventoryNumber",
                    mols1C.fio AS "responsiblePerson"
                
                FROM equipment.appliances AS appliances
                    LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
                    LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
                    LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
                    LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
                    
                    LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
                    LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
                    LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
                    LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id
                    
                    LEFT JOIN equipment."phoneInfo" AS "phoneInfo" ON "phoneInfo".__appliance_id = appliances.__id
                    
                    LEFT JOIN (
                                SELECT
                                  lotus_data.reg_center AS "lotus_regCenter",
                    
                                  region.title      AS region,
                                  lotus_data.region AS lotus_region,
                                  region.__id       AS region_id,
                    
                                  city.title        AS city,
                                  lotus_data.city AS lotus_city,
                                  city.__id         AS city_id,
                    
                                  offices.title     AS office,
                                  lotus_data.title AS lotus_office,
                                  offices.__id      AS office_id,
                    
                                  offices."lotusId" AS "lotusId",
                                  lotus_data.lotus_id AS "lotus_lotusId",
                    
                                  offices.comment   AS "officeComment",
                                  offices.details   AS "officeDetails",
                    
                                  address.address   AS "officeAddress",
                                  lotus_data.address AS "lotus_officeAddress",
                    
                                  lotus_data.employees AS lotus_employees,
                                  lotus_data.last_refresh AS "lotus_lastRefresh"
                    
                    
                                FROM company.offices AS offices
                                  JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
                                  JOIN geolocation.cities AS city ON city.__id = address.__city_id
                                  JOIN geolocation.regions AS region ON region.__id = city.__region_id
                                  FULL JOIN view.lotus_db_data AS lotus_data ON offices."lotusId" = lotus_data.lotus_id
                              ) AS geo
                      ON geo.office_id = appliances.__location_id
                    
                    LEFT JOIN view.dev_calls_stats AS "devCallsStats" ON "devCallsStats".appliance_id = appliances.__id
                    LEFT JOIN storage_1c."appliances1C" AS "appliances1C" ON "appliances1C".__voice_appliance_id = appliances.__id
                    LEFT JOIN storage_1c."inventoryItem1C" AS "inventoryItem1C" ON "inventoryItem1C".__id = "appliances1C".__inventory_item_id
                    LEFT JOIN storage_1c.mols AS mols1C ON "inventoryItem1C".__mol_id = mols1C.__id'
        ;

        $sql['recovery - dependent view '.$dependentView] =
            'CREATE MATERIALIZED VIEW '.$dependentView.' AS
                SELECT *, now() AS last_refresh
                FROM '.$targetView
        ;
        $sql['recovery - indexOnLotusId'] = 'CREATE INDEX '.$index.' ON '.$dependentView.'("lotusId")';


        // -----------------
        $sql['recovery - dev_port_phone_info_geo'] = '
        CREATE OR REPLACE VIEW view.dev_port_phone_info_geo AS
            SELECT
              region,
              region_id,
              city,
              city_id,
              office,
              office_id,
              "lotusId",
              "officeAddress",
              "officeComment",
              "officeDetails",
            
              appliances.__id AS appliance_id,
              appliances."lastUpdate" AS "appLastUpdate",
              (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
              appliances."inUse" AS "appInUse",
              CAST(appliances.details::jsonb->>\'hostname\' AS citext) AS hostname,
              appliances.details AS "appDetails",
              appliances."comment" AS "appComment",
              "appTypes".__id AS "appType_id",
              "appTypes".type AS "appType",
              "appTypes"."sortOrder" AS "appSortOrder",
              clusters.__id AS cluster_id,
              clusters.title AS "clusterTitle",
              clusters.details AS "clusterDetails",
              clusters.comment AS "clusterComment",
              "platformVendor".__id AS "platformVendor_id",
              "platformVendor".title AS "platformVendor",
              "platformItem".__id AS "platformItem_id",
              platform.title AS "platformTitle",
              platform.__id AS "platform_id",
              "platformItem"."serialNumber" AS "platformSerial",
              "softwareVendor".__id AS "softwareVendor_id",
              "softwareVendor".title AS "softwareVendor",
              "softwareItem".__id AS "softwareItem_id",
              software.__id AS "software_id",
              software.title AS "softwareTitle",
              "softwareItem".version AS "softwareVersion",
            
              name,
              model,
              prefix,
              "phoneDN",
              status,
              "phoneInfo".description AS "phoneDescription",
              css,
              "devicePool",
              "alertingName",
              partition,
              timezone,
              "dhcpEnabled",
              "dhcpServer",
              "domainName",
              "tftpServer1",
              "tftpServer2",
              "defaultRouter",
              "dnsServer1",
              "dnsServer2",
              "callManager1",
              "callManager2",
              "callManager3",
              "callManager4",
              "vlanId",
              "userLocale",
              "cdpNeighborDeviceId",
              "cdpNeighborIP",
              "cdpNeighborPort",
              "publisherIp",
              "unknownLocation",
              ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                (SELECT
                   "dPorts".__appliance_id AS appliance_id,
                   "dPorts".__network_id AS network_id,
                   "dPorts"."ipAddress" AS "ipAddress",
                   "dPorts"."masklen" AS "masklen",
                   "dPorts"."macAddress" AS "macAddress",
                   "dPorts"."details" AS "details",
                   "dPorts"."comment" AS "comment",
                   "dPorts"."isManagement" AS "isManagement",
                   "dPorts"."__type_port_id" AS "portType_id",
                   "dPortTypes".type AS "portType"
                 FROM equipment."dataPorts" AS "dPorts"
                   JOIN equipment."dataPortTypes" AS "dPortTypes" ON "dPorts".__type_port_id = "dPortTypes".__id
                 WHERE appliances.__id = "dPorts".__appliance_id
                 ORDER BY "ipAddress") AS t
              ) AS "portInfo",
              (SELECT "ipAddress" FROM equipment."dataPorts" AS "dPorts"
              WHERE appliances.__id = "dPorts".__appliance_id AND "isManagement" = TRUE
               ORDER BY "ipAddress" LIMIT 1
              ) AS "managementIp"
            FROM equipment.appliances AS appliances
              LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
              LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
              LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
              LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
            
              LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
              LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
              LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
              LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id
            
              LEFT JOIN equipment."phoneInfo" AS "phoneInfo" ON "phoneInfo".__appliance_id = appliances.__id
            
              LEFT JOIN (
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
                        ) AS geo
               ON geo.office_id = appliances.__location_id
        ';


        // -----------------
        $sql['recovery view - dev_geo_1c_info'] = '
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
        // For test DB
        //$this->setDb('phpUnitTest');
        //foreach ($sql as $key => $query) {
        //    if (true === $this->db->execute($query)) {
        //        echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
        //    }
        //}
    }

    public function down()
    {
        $sql['drop_view__view.dev_geo_1c_info'] = 'DROP VIEW IF EXISTS view.dev_geo_1c_info';
        $sql['drop_view__view.dev_port_phone_info_geo'] = 'DROP VIEW IF EXISTS view.dev_port_phone_info_geo';
        $sql['drop indexOnLotusId'] = 'DROP INDEX IF EXISTS idx_dev_phone_info_geo_mat_lotus_id';
        $sql['drop_view__view.dev_phone_info_geo_mat'] = 'DROP MATERIALIZED VIEW IF EXISTS view.dev_phone_info_geo_mat';
        $sql['drop_view__view.dev_phone_info_geo'] = 'DROP VIEW IF EXISTS view.dev_phone_info_geo';
        $sql['drop_view__view.dev_module_port_geo'] = 'DROP VIEW IF EXISTS view.dev_module_port_geo';

        $sql['alter_table__equipment.phoneInfo'] =
            'ALTER TABLE equipment."phoneInfo"
                ALTER COLUMN "prefix" SET DATA TYPE INTEGER USING prefix::integer,
                ALTER COLUMN "phoneDN" SET DATA TYPE INTEGER USING prefix::integer';

        // --------------
        $targetView = 'view.dev_module_port_geo';
        $sql['recovery_view__view.dev_module_port_geo'] =
            'CREATE OR REPLACE VIEW '.$targetView.' AS
                SELECT
                    region,
                    region_id,
                    city,
                    city_id,
                    office,
                    office_id,
                    "lotusId",
                    "officeAddress",
                    "officeComment",
                    "officeDetails",
            
                    appliances.__id AS appliance_id,
                    appliances."lastUpdate" AS "appLastUpdate",
                    (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
                    appliances."inUse" AS "appInUse",
                    CAST(appliances.details::jsonb->>\'hostname\' AS citext) AS hostname,
                    CASE WHEN ("phoneInfo".prefix IS NOT NULL) OR ("phoneInfo"."phoneDN" IS NOT NULL )
                        THEN
                            CAST(appliances.details::jsonb->>\'hostname\'||
                                 \',(DN \' ||
                                 "phoneInfo".prefix ||
                                 "phoneInfo"."phoneDN" ||
                                 \')\' AS citext)
                    ELSE
                        CAST(appliances.details::jsonb->>\'hostname\' AS citext)
                    END
                        AS hostname_dn,
                    appliances.details AS "appDetails",
                    appliances."comment" AS "appComment",
                    "appTypes".__id AS "appType_id",
                    "appTypes".type AS "appType",
                    "appTypes"."sortOrder" AS "appSortOrder",
                    clusters.__id AS cluster_id,
                    clusters.title AS "clusterTitle",
                    clusters.details AS "clusterDetails",
                    clusters.comment AS "clusterComment",
                    "platformVendor".__id AS "platformVendor_id",
                    "platformVendor".title AS "platformVendor",
                    "platformItem".__id AS "platformItem_id",
                    platform.title AS "platformTitle",
                    platform.__id AS "platform_id",
                    platform."isHW" AS "isHW",
                    "platformItem"."serialNumber" AS "platformSerial",
                    "softwareVendor".__id AS "softwareVendor_id",
                    "softwareVendor".title AS "softwareVendor",
                    "softwareItem".__id AS "softwareItem_id",
                    software.__id AS "software_id",
                    software.title AS "softwareTitle",
                    "softwareItem".version AS "softwareVersion",
                    CASE WHEN COALESCE("softwareItem".version, \'\') = \'\'
                        THEN
                            CAST(software.title AS citext)
                    ELSE
                        CAST(software.title || \' \' || "softwareItem".version AS citext)
                    END
                        AS "softwareAndVersion",
            
                    ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                        (SELECT
                             "moduleItems".__appliance_id AS appliance_id,
                             "moduleItems".__id AS "moduleItem_id",
                             "moduleItems"."serialNumber" AS "serialNumber",
                             "moduleItems"."inventoryNumber" AS "inventoryNumber",
                             "moduleItems"."details" AS "details",
                             "moduleItems"."comment" AS "comment",
                             "moduleItems"."inUse" AS "inUse",
                             "moduleItems"."notFound" AS "notFound",
                             "moduleItems"."lastUpdate" AS "lastUpdate",
                             (EXTRACT(EPOCH FROM age(now(), "moduleItems"."lastUpdate"))/3600)::INT AS "moduleItemAge",
                             "modules".__id AS "module_id",
                             "modules".title AS "title",
                             "modules".description AS "description"
                         FROM equipment."moduleItems" AS "moduleItems"
                             JOIN equipment.modules AS modules ON "moduleItems".__module_id = modules.__id
                         WHERE appliances.__id = "moduleItems".__appliance_id
                         ORDER BY module_id) AS t
                    ) AS "moduleInfo",
                    ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                        (SELECT
                             "dPorts".__appliance_id AS appliance_id,
                             "dPorts".__network_id AS network_id,
                             "dPorts"."ipAddress" AS "ipAddress",
                             "dPorts"."masklen" AS "masklen",
                             "dPorts"."macAddress" AS "macAddress",
                             "dPorts"."details" AS "details",
                             "dPorts"."comment" AS "comment",
                             "dPorts"."isManagement" AS "isManagement",
                             "dPorts"."__type_port_id" AS "portType_id",
                             "dPortTypes".type AS "portType"
                         FROM equipment."dataPorts" AS "dPorts"
                             JOIN equipment."dataPortTypes" AS "dPortTypes" ON "dPorts".__type_port_id = "dPortTypes".__id
                         WHERE appliances.__id = "dPorts".__appliance_id
                         ORDER BY "ipAddress") AS t
                    ) AS "portInfo",
                    (SELECT "ipAddress" FROM equipment."dataPorts" AS "dPorts"
                    WHERE appliances.__id = "dPorts".__appliance_id AND "isManagement" = TRUE
                     ORDER BY "ipAddress" LIMIT 1
                    ) AS "managementIp",
                    "inventoryItem1C"."inventoryNumber" AS "inventoryNumber",
                    mols1C.fio AS "responsiblePerson",
                    "devCallsStats".last_call_day,
                    "devCallsStats".d0_calls_amount,
                    "devCallsStats".m0_calls_amount,
                    "devCallsStats".m1_calls_amount,
                    "devCallsStats".m2_calls_amount
  
                FROM equipment.appliances AS appliances
                    LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
                    LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
                    LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
                    LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
            
                    LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
                    LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
                    LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
                    LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id
            
                    LEFT JOIN equipment."phoneInfo" AS "phoneInfo" ON "phoneInfo".__appliance_id = appliances.__id
            
                    LEFT JOIN storage_1c."appliances1C" AS appliances1C ON appliances.__id = appliances1C.__voice_appliance_id
                    LEFT JOIN storage_1c."inventoryItem1C" AS "inventoryItem1C" ON appliances1C.__inventory_item_id = "inventoryItem1C".__id
                    LEFT JOIN storage_1c.mols AS mols1C ON "inventoryItem1C".__mol_id = mols1C.__id
            
                    LEFT JOIN (
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
                              ) AS geo
                        ON geo.office_id = appliances.__location_id
                        
                    LEFT JOIN view.dev_calls_stats AS "devCallsStats" ON "devCallsStats".appliance_id = appliances.__id'
        ;


        // ----------------------
        $targetView = 'view.dev_phone_info_geo';
        $dependentView = 'view.dev_phone_info_geo_mat';
        $index = 'idx_dev_phone_info_geo_mat_lotus_id';
        $sql['recovery - '.$targetView] =
            'CREATE OR REPLACE VIEW '.$targetView.' AS 
                SELECT
                    "lotus_regCenter",
                    region,
                    lotus_region,
                    region_id,
                    city,
                    lotus_city,
                    city_id,
                    office,
                    lotus_office,
                    office_id,
                    "lotusId",
                    "lotus_lotusId",
                    "officeAddress",
                    "lotus_officeAddress",
                    "officeComment",
                    "officeDetails",
                    lotus_employees,
                    "lotus_lastRefresh",
                    
                    appliances.__id AS appliance_id,
                    appliances."lastUpdate" AS "appLastUpdate",
                    (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
                    appliances."inUse" AS "appInUse",
                    CAST(appliances.details::jsonb->>\'hostname\' AS citext) AS hostname,
                    appliances.details AS "appDetails",
                    appliances."comment" AS "appComment",
                    "appTypes".__id AS "appType_id",
                    "appTypes".type AS "appType",
                    clusters.__id AS cluster_id,
                    clusters.title AS "clusterTitle",
                    clusters.details AS "clusterDetails",
                    clusters.comment AS "clusterComment",
                    "platformVendor".__id AS "platformVendor_id",
                    "platformVendor".title AS "platformVendor",
                    "platformItem".__id AS "platformItem_id",
                    platform.title AS "platformTitle",
                    platform."isHW" AS "isHW",
                    platform.__id AS "platform_id",
                    "platformItem"."serialNumber" AS "platformSerial",
                    "softwareVendor".__id AS "softwareVendor_id",
                    "softwareVendor".title AS "softwareVendor",
                    "softwareItem".__id AS "softwareItem_id",
                    software.__id AS "software_id",
                    software.title AS "softwareTitle",
                    "softwareItem".version AS "softwareVersion",
                    
                    name,
                    model,
                    prefix,
                    "phoneDN",
                    status,
                    "phoneInfo".description AS "phoneDescription",
                    css,
                    "devicePool",
                    "alertingName",
                    partition,
                    timezone,
                    "dhcpEnabled",
                    "dhcpServer",
                    "domainName",
                    "tftpServer1",
                    "tftpServer2",
                    "defaultRouter",
                    "dnsServer1",
                    "dnsServer2",
                    "callManager1",
                    "callManager2",
                    "callManager3",
                    "callManager4",
                    "vlanId",
                    "userLocale",
                    "cdpNeighborDeviceId",
                    "cdpNeighborIP",
                    "cdpNeighborPort",
                    "publisherIp",
                    "unknownLocation",
                    (SELECT "ipAddress" FROM equipment."dataPorts" AS "dPorts"
                    WHERE appliances.__id = "dPorts".__appliance_id AND "isManagement" = TRUE
                     ORDER BY "ipAddress" LIMIT 1
                    ) AS "managementIp",
                    
                    "devCallsStats".last_call_day,
                    "devCallsStats".d0_calls_amount,
                    "devCallsStats".m0_calls_amount,
                    "devCallsStats".m1_calls_amount,
                    "devCallsStats".m2_calls_amount,
                    "inventoryItem1C"."inventoryNumber",
                    mols1C.fio AS "responsiblePerson"
                
                FROM equipment.appliances AS appliances
                    LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
                    LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
                    LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
                    LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
                    
                    LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
                    LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
                    LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
                    LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id
                    
                    LEFT JOIN equipment."phoneInfo" AS "phoneInfo" ON "phoneInfo".__appliance_id = appliances.__id
                    
                    LEFT JOIN (
                                SELECT
                                  lotus_data.reg_center AS "lotus_regCenter",
                    
                                  region.title      AS region,
                                  lotus_data.region AS lotus_region,
                                  region.__id       AS region_id,
                    
                                  city.title        AS city,
                                  lotus_data.city AS lotus_city,
                                  city.__id         AS city_id,
                    
                                  offices.title     AS office,
                                  lotus_data.title AS lotus_office,
                                  offices.__id      AS office_id,
                    
                                  offices."lotusId" AS "lotusId",
                                  lotus_data.lotus_id AS "lotus_lotusId",
                    
                                  offices.comment   AS "officeComment",
                                  offices.details   AS "officeDetails",
                    
                                  address.address   AS "officeAddress",
                                  lotus_data.address AS "lotus_officeAddress",
                    
                                  lotus_data.employees AS lotus_employees,
                                  lotus_data.last_refresh AS "lotus_lastRefresh"
                    
                    
                                FROM company.offices AS offices
                                  JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
                                  JOIN geolocation.cities AS city ON city.__id = address.__city_id
                                  JOIN geolocation.regions AS region ON region.__id = city.__region_id
                                  FULL JOIN view.lotus_db_data AS lotus_data ON offices."lotusId" = lotus_data.lotus_id
                              ) AS geo
                      ON geo.office_id = appliances.__location_id
                    
                    LEFT JOIN view.dev_calls_stats AS "devCallsStats" ON "devCallsStats".appliance_id = appliances.__id
                    LEFT JOIN storage_1c."appliances1C" AS "appliances1C" ON "appliances1C".__voice_appliance_id = appliances.__id
                    LEFT JOIN storage_1c."inventoryItem1C" AS "inventoryItem1C" ON "inventoryItem1C".__id = "appliances1C".__inventory_item_id
                    LEFT JOIN storage_1c.mols AS mols1C ON "inventoryItem1C".__mol_id = mols1C.__id'
        ;

        $sql['recovery - dependent view '.$dependentView] =
            'CREATE MATERIALIZED VIEW '.$dependentView.' AS
                SELECT *, now() AS last_refresh
                FROM '.$targetView
        ;
        $sql['recovery - indexOnLotusId'] = 'CREATE INDEX '.$index.' ON '.$dependentView.'("lotusId")';


        // -----------------
        $sql['recovery - dev_port_phone_info_geo'] = '
        CREATE OR REPLACE VIEW view.dev_port_phone_info_geo AS
            SELECT
              region,
              region_id,
              city,
              city_id,
              office,
              office_id,
              "lotusId",
              "officeAddress",
              "officeComment",
              "officeDetails",
            
              appliances.__id AS appliance_id,
              appliances."lastUpdate" AS "appLastUpdate",
              (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
              appliances."inUse" AS "appInUse",
              CAST(appliances.details::jsonb->>\'hostname\' AS citext) AS hostname,
              appliances.details AS "appDetails",
              appliances."comment" AS "appComment",
              "appTypes".__id AS "appType_id",
              "appTypes".type AS "appType",
              "appTypes"."sortOrder" AS "appSortOrder",
              clusters.__id AS cluster_id,
              clusters.title AS "clusterTitle",
              clusters.details AS "clusterDetails",
              clusters.comment AS "clusterComment",
              "platformVendor".__id AS "platformVendor_id",
              "platformVendor".title AS "platformVendor",
              "platformItem".__id AS "platformItem_id",
              platform.title AS "platformTitle",
              platform.__id AS "platform_id",
              "platformItem"."serialNumber" AS "platformSerial",
              "softwareVendor".__id AS "softwareVendor_id",
              "softwareVendor".title AS "softwareVendor",
              "softwareItem".__id AS "softwareItem_id",
              software.__id AS "software_id",
              software.title AS "softwareTitle",
              "softwareItem".version AS "softwareVersion",
            
              name,
              model,
              prefix,
              "phoneDN",
              status,
              "phoneInfo".description AS "phoneDescription",
              css,
              "devicePool",
              "alertingName",
              partition,
              timezone,
              "dhcpEnabled",
              "dhcpServer",
              "domainName",
              "tftpServer1",
              "tftpServer2",
              "defaultRouter",
              "dnsServer1",
              "dnsServer2",
              "callManager1",
              "callManager2",
              "callManager3",
              "callManager4",
              "vlanId",
              "userLocale",
              "cdpNeighborDeviceId",
              "cdpNeighborIP",
              "cdpNeighborPort",
              "publisherIp",
              "unknownLocation",
              ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM
                (SELECT
                   "dPorts".__appliance_id AS appliance_id,
                   "dPorts".__network_id AS network_id,
                   "dPorts"."ipAddress" AS "ipAddress",
                   "dPorts"."masklen" AS "masklen",
                   "dPorts"."macAddress" AS "macAddress",
                   "dPorts"."details" AS "details",
                   "dPorts"."comment" AS "comment",
                   "dPorts"."isManagement" AS "isManagement",
                   "dPorts"."__type_port_id" AS "portType_id",
                   "dPortTypes".type AS "portType"
                 FROM equipment."dataPorts" AS "dPorts"
                   JOIN equipment."dataPortTypes" AS "dPortTypes" ON "dPorts".__type_port_id = "dPortTypes".__id
                 WHERE appliances.__id = "dPorts".__appliance_id
                 ORDER BY "ipAddress") AS t
              ) AS "portInfo",
              (SELECT "ipAddress" FROM equipment."dataPorts" AS "dPorts"
              WHERE appliances.__id = "dPorts".__appliance_id AND "isManagement" = TRUE
               ORDER BY "ipAddress" LIMIT 1
              ) AS "managementIp"
            FROM equipment.appliances AS appliances
              LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
              LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
              LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
              LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id
            
              LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
              LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
              LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
              LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id
            
              LEFT JOIN equipment."phoneInfo" AS "phoneInfo" ON "phoneInfo".__appliance_id = appliances.__id
            
              LEFT JOIN (
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
                        ) AS geo
               ON geo.office_id = appliances.__location_id
        ';


        // -----------------
        $sql['recovery view - dev_geo_1c_info'] = '
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
        // For test DB
        //$this->setDb('phpUnitTest');
        //foreach ($sql as $key => $query) {
        //    if (true === $this->db->execute($query)) {
        //        echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
        //    }
        //}
    }
    
}
