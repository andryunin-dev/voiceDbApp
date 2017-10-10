<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1506948864_restoreDevPortPhone_infoGeoView
    extends Migration
{

    public function up()
    {
        $sql['drop_old'] = 'DROP VIEW IF EXISTS view.dev_port_phone_info_geo';
        $sql['dev_port_phoneInfo_geo'] = '
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
        //drop view to change columns type to citext
        $sql['drop_old'] = 'DROP VIEW view.dev_port_phone_info_geo';

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