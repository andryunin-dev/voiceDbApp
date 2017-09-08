<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1504862722_fixDevPhone_infoGeoView
    extends Migration
{

    public function up()
    {
        $sql['drop_old'] = 'DROP VIEW IF EXISTS view.dev_phone_info_geo';
        $sql['dev_phoneInfo_geo'] = '
        CREATE OR REPLACE VIEW view.dev_phone_info_geo AS
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
              appliances.details::jsonb->>\'hostname\' AS hostname,
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
        $sql['drop_view_dev_phone_info_geo'] = 'DROP VIEW IF EXISTS view.dev_phone_info_geo';
        $sql['view.geo_dev_phone_info'] = '
    CREATE OR REPLACE VIEW view.dev_phone_info_geo AS
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
            (EXTRACT(EPOCH FROM age(now(), appliances."lastUpdate"))/3600)::INT AS "appAge",
            appliances."inUse" AS "appInUse",
            appliances.details::jsonb->>\'hostname\' AS hostname,
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
            platform.__id AS "platform_id",

            "softwareVendor".__id AS "softwareVendor_id",
            "softwareVendor".title AS "softwareVendor",
            "softwareItem".__id AS "softwareItem_id",
            software.__id AS "software_id",
            software.title AS "softwareTitle",
            "softwareItem".version AS "softwareVersion"
        FROM equipment.appliances AS appliances
            LEFT JOIN equipment."applianceTypes" AS "appTypes" ON appliances.__type_id = "appTypes".__id
            LEFT JOIN equipment."platformItems" AS "platformItem" ON appliances.__platform_item_id = "platformItem".__id
            LEFT JOIN equipment.platforms AS platform ON "platformItem".__platform_id = platform.__id
            LEFT JOIN equipment.vendors AS "platformVendor" ON platform.__vendor_id = "platformVendor".__id

            LEFT JOIN equipment."softwareItems" AS "softwareItem" ON appliances.__software_item_id = "softwareItem".__id
            LEFT JOIN equipment.software AS software ON "softwareItem".__software_id = software.__id
            LEFT JOIN equipment.vendors AS "softwareVendor" ON software.__vendor_id = "softwareVendor".__id
            LEFT JOIN equipment.clusters AS "clusters" ON appliances.__cluster_id = clusters.__id
    ), port_info AS (
        SELECT
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
    ), phone_info AS (
        SELECT
            equipment."phoneInfo".__appliance_id AS phone_app_id,
            equipment."phoneInfo".name AS name,
            equipment."phoneInfo".model AS model,
            equipment."phoneInfo".prefix AS prefix,
            equipment."phoneInfo"."phoneDN" AS "phoneDn",
            equipment."phoneInfo".status AS status,
            equipment."phoneInfo".description AS "phoneDescription",
            equipment."phoneInfo".css AS css,
            equipment."phoneInfo"."devicePool" AS "devicePool",
            equipment."phoneInfo"."alertingName" AS "alertingName",
            equipment."phoneInfo".partition AS partition,
            equipment."phoneInfo".timezone AS timezone,
            equipment."phoneInfo"."dhcpEnabled" AS "dhcpEnabled",
            equipment."phoneInfo"."dhcpServer" AS "dhcpServer",
            equipment."phoneInfo"."domainName" AS "domainName",
            equipment."phoneInfo"."tftpServer1" AS "tftpServer1",
            equipment."phoneInfo"."tftpServer2" AS "tftpServer2",
            equipment."phoneInfo"."defaultRouter" AS "defaultRouter",
            equipment."phoneInfo"."dnsServer1" AS "dnsServer1",
            equipment."phoneInfo"."dnsServer2" AS "dnsServer2",
            equipment."phoneInfo"."callManager1" AS "callManager1",
            equipment."phoneInfo"."callManager2" AS "callManager2",
            equipment."phoneInfo"."callManager3" AS "callManager3",
            equipment."phoneInfo"."callManager4" AS "callManager4",
            equipment."phoneInfo"."vlanId" AS "vlanId",
            equipment."phoneInfo"."userLocale" AS "userLocale",
            equipment."phoneInfo"."cdpNeighborDeviceId" AS "cdpNeighborDeviceId",
            equipment."phoneInfo"."cdpNeighborIP" AS "cdpNeighborIP",
            equipment."phoneInfo"."cdpNeighborPort" AS "cdpNeighborPort",
            equipment."phoneInfo"."publisherIp" AS "publisherIp"
        FROM equipment."phoneInfo"
    ), net_management_info AS (
        SELECT *
        FROM port_info WHERE "isManagement" = TRUE
    )
    SELECT * ,
         ( SELECT array_to_json(array_agg(to_jsonb(t))) FROM (
                                                                SELECT *
                                                                FROM port_info
                                                                WHERE devices.appliance_id = port_info.appliance_id
                                                                ORDER BY port_info."ipAddress") AS t
        ) AS "portInfo",
        (SELECT "ipAddress" FROM net_management_info WHERE net_management_info.appliance_id = devices.appliance_id LIMIT 1) AS "managementIp"
    FROM devices
        LEFT JOIN phone_info ON devices.appliance_id = phone_info.phone_app_id
        LEFT JOIN geo ON geo.office_id = devices.location_id
';

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