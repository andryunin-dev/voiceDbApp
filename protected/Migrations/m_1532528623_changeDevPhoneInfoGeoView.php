<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1532528623_changeDevPhoneInfoGeoView
    extends Migration
{

    public function up()
    {
        $targetView = 'view.dev_phone_info_geo';
        $dependentView = 'view.dev_phone_info_geo_mat';
        $index = 'idx_dev_phone_info_geo_mat_lotus_id';

        $sql['drop indexOnLotusId'] = 'DROP INDEX IF EXISTS '.$index;
        $sql['drop dependent view '.$dependentView] = 'DROP MATERIALIZED VIEW IF EXISTS '.$dependentView;
        $sql['drop old '.$targetView] = 'DROP VIEW IF EXISTS '.$targetView;

        $sql['create '.$targetView] =
            'CREATE VIEW '.$targetView.' AS 
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

        $sql['create dependent view '.$dependentView] =
            'CREATE MATERIALIZED VIEW '.$dependentView.' AS
                SELECT *, now() AS last_refresh
                FROM '.$targetView
        ;
        $sql['create indexOnLotusId'] = 'CREATE INDEX '.$index.' ON '.$dependentView.'("lotusId")';


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
        $targetView = 'view.dev_phone_info_geo';
        $dependentView = 'view.dev_phone_info_geo_mat';
        $index = 'idx_dev_phone_info_geo_mat_lotus_id';

        $sql['drop indexOnLotusId'] = 'DROP INDEX IF EXISTS '.$index;
        $sql['drop dependent view '.$dependentView] = 'DROP MATERIALIZED VIEW IF EXISTS '.$dependentView;
        $sql['drop '.$targetView] = 'DROP VIEW IF EXISTS '.$targetView;

        $sql['create old '.$targetView] =
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
                     ON geo.office_id = appliances.__location_id'
        ;

        $sql['create dependent view '.$dependentView] =
            'CREATE MATERIALIZED VIEW '.$dependentView.' AS
                SELECT *, now() AS last_refresh
                FROM '.$targetView
        ;
        $sql['create indexOnLotusId'] = 'CREATE INDEX '.$index.' ON '.$dependentView.'("lotusId")';


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
