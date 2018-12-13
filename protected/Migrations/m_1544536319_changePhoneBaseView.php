<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1544536319_changePhoneBaseView
    extends Migration
{

    public function up()
    {
        $targetView = 'view.phone_base';

        $sql['drop_old__view.phone_base '] = 'DROP VIEW IF EXISTS '.$targetView;
        $sql['create_view.phone_base '] = '
            CREATE VIEW '.$targetView.' AS
                WITH
                    phone AS (
                        SELECT
                            __appliance_id,
                            "publisherIp",
                            name,
                            "phoneDN" AS dn,
                            prefix,
                            "alertingName",
                            description,
                            "vlanId",
                            "cdpNeighborDeviceId",
                            "cdpNeighborPort",
                            "cdpNeighborIP"
                        FROM equipment."phoneInfo"
                    ),

                    appliance AS (
                        WITH
                            "dataPort" AS (SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" WHERE "isManagement" IS TRUE),
                
                            "platformItem" AS (
                                WITH
                                    platform AS (
                                        WITH
                                            vendor AS (SELECT __id, title FROM equipment.vendors)
                                        SELECT
                                            platform.__id,
                                            platform.title,
                                            vendor.title AS vendor
                                        FROM equipment.platforms AS platform
                                        LEFT JOIN vendor ON platform.__vendor_id = vendor.__id
                                    )
                                SELECT
                                    "platformItems".__id,
                                    "platformItems"."serialNumber",
                                    platform.__id AS "platformId",
                                    platform.title AS platform,
                                    platform.vendor AS vendor
                                FROM equipment."platformItems" AS "platformItems"
                                LEFT JOIN platform ON "platformItems".__platform_id = platform.__id
                            ),
                
                            office AS (
                                WITH
                                    address AS (
                                        WITH
                                            city AS (
                                                SELECT
                                                   city.__id,
                                                   city.title
                                                FROM geolocation.cities AS city
                                            )
                                        SELECT
                                            address.__id,
                                            city.__id AS "cityId",
                                            city.title AS city
                                        FROM geolocation.addresses AS address
                                        LEFT JOIN city ON address.__city_id = city.__id
                                    )
                                SELECT
                                    office.__id,
                                    office.title,
                                    address."cityId",
                                    address.city
                                FROM company.offices AS office
                                LEFT JOIN address ON office.__address_id = address.__id
                            ),
                
                            "appliance1c" AS (
                                WITH
                                    "inventoryItem" AS (
                                        SELECT
                                            "inventoryItem1C".__id,
                                            "inventoryItem1C"."inventoryNumber"
                                        FROM storage_1c."inventoryItem1C" AS "inventoryItem1C"
                                    )
                                SELECT
                                    "appliances1C".__voice_appliance_id,
                                    "inventoryItem"."inventoryNumber"
                                FROM storage_1c."appliances1C" AS "appliances1C"
                                LEFT JOIN "inventoryItem" ON "appliances1C".__inventory_item_id = "inventoryItem".__id
                            )
                
                        SELECT
                            appliance.__id,
                            (CASE WHEN (((date_part(\'epoch\' :: TEXT, age(now(), "lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER) < 73 THEN \'да\' ELSE \'нет\' END) AS "isActive",
                            "dataPort"."ipAddress",
                            "platformItem"."serialNumber",
                            appliance1c."inventoryNumber",
                            "platformItem"."platformId",
                            "platformItem".vendor,
                            "platformItem".platform,
                            office.__id AS "officeId",
                            office.title AS office,
                            office."cityId",
                            office.city,
                            appliance.details,
                            appliance.comment,
                            appliance."lastUpdate"
                        FROM equipment.appliances AS appliance
                        LEFT JOIN "dataPort" ON appliance.__id = "dataPort".__appliance_id
                        LEFT JOIN "platformItem" ON appliance.__platform_item_id = "platformItem".__id
                        LEFT JOIN office ON appliance.__location_id = office.__id
                        LEFT JOIN appliance1c ON appliance.__id = appliance1c.__voice_appliance_id
                    ),
                
                    switch AS (
                        SELECT
                            appliance."ipAddress",
                            appliance."inventoryNumber",
                            appliance.platform AS "switchPlatform"
                        FROM appliance
                    ),
                
                    publisher AS (
                        SELECT
                            appliance.details::jsonb->>\'reportName\'  AS "reportName",
                            appliance."ipAddress"
                        FROM appliance
                    )

                SELECT
                    phone.name,
                    cast(phone.prefix || phone.dn AS citext) AS dn,
                    cast(phone.prefix || \'-\' || phone.dn AS citext) AS "dnDash",
                    phone.prefix AS "dnPrefix",
                    phone."alertingName",
                    phone.description AS depiction,
                    cast(phone."vlanId" AS citext),
                    cast(appliance."isActive" AS citext),
                    cast(host(appliance."ipAddress") AS citext) AS "ipAddress",
                    appliance."serialNumber",
                    appliance."inventoryNumber",
                    appliance."platformId",
                    appliance.platform,
                    appliance."officeId",
                    appliance.office,
                    appliance."cityId",
                    appliance.city,
                    appliance.comment,
                    appliance."lastUpdate",
                    phone."cdpNeighborDeviceId" AS "switchHostname",
                    phone."cdpNeighborPort" AS "switchPort",
                    cast(host(phone."cdpNeighborIP") AS citext) AS "switchIp",
                    switch."inventoryNumber" AS "switchInventoryNumber",
                    cast(switch."switchPlatform" AS citext),
                    cast(publisher."reportName" AS citext) AS "publisherName",
                    cast(host(publisher."ipAddress") AS citext) AS "publisherIp"
                FROM phone
                LEFT JOIN appliance ON phone.__appliance_id = appliance.__id
                LEFT JOIN switch ON phone."cdpNeighborIP" = switch."ipAddress"
                LEFT JOIN publisher ON CAST(phone."publisherIp" AS inet) = publisher."ipAddress"
            ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $targetView = 'view.phone_base';

        $sql['drop__view.phone_base '] = 'DROP VIEW IF EXISTS '.$targetView;
        $sql['create_old__view.phone_base '] = '
            CREATE VIEW '.$targetView.' AS
                WITH
                    phone AS (
                        SELECT
                            __appliance_id,
                            "publisherIp",
                            name,
                            (prefix || "phoneDN") AS dn,
                            "alertingName",
                            description,
                            "vlanId",
                            "cdpNeighborDeviceId",
                            "cdpNeighborPort",
                            "cdpNeighborIP"
                        FROM equipment."phoneInfo"
                    ),
    
                    appliance AS (
                        WITH
                            "dataPort" AS (SELECT __appliance_id, "ipAddress" FROM equipment."dataPorts" WHERE "isManagement" IS TRUE),
                
                            "platformItem" AS (
                                WITH
                                    platform AS (
                                        WITH
                                            vendor AS (SELECT __id, title FROM equipment.vendors)
                                        SELECT
                                            platform.__id,
                                            platform.title,
                                            vendor.title AS vendor
                                        FROM equipment.platforms AS platform
                                        LEFT JOIN vendor ON platform.__vendor_id = vendor.__id
                                    )
                                SELECT
                                    "platformItems".__id,
                                    "platformItems"."serialNumber",
                                    platform.__id AS "platformId",
                                    platform.title AS platform,
                                    platform.vendor AS vendor
                                FROM equipment."platformItems" AS "platformItems"
                                LEFT JOIN platform ON "platformItems".__platform_id = platform.__id
                            ),
                
                            office AS (
                                WITH
                                    address AS (
                                        WITH
                                            city AS (
                                                SELECT
                                                   city.__id,
                                                   city.title
                                                FROM geolocation.cities AS city
                                            )
                                        SELECT
                                            address.__id,
                                            city.__id AS "cityId",
                                            city.title AS city
                                        FROM geolocation.addresses AS address
                                        LEFT JOIN city ON address.__city_id = city.__id
                                    )
                                SELECT
                                    office.__id,
                                    office.title,
                                    address."cityId",
                                    address.city
                                FROM company.offices AS office
                                LEFT JOIN address ON office.__address_id = address.__id
                            ),
                
                            "appliance1c" AS (
                                WITH
                                    "inventoryItem" AS (
                                        SELECT
                                            "inventoryItem1C".__id,
                                            "inventoryItem1C"."inventoryNumber"
                                        FROM storage_1c."inventoryItem1C" AS "inventoryItem1C"
                                    )
                                SELECT
                                    "appliances1C".__voice_appliance_id,
                                    "inventoryItem"."inventoryNumber"
                                FROM storage_1c."appliances1C" AS "appliances1C"
                                LEFT JOIN "inventoryItem" ON "appliances1C".__inventory_item_id = "inventoryItem".__id
                            )
                
                        SELECT
                            appliance.__id,
                            (CASE WHEN (((date_part(\'epoch\' :: TEXT, age(now(), "lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER) < 73 THEN \'да\' ELSE \'нет\' END) AS "isActive",
                            "dataPort"."ipAddress",
                            "platformItem"."serialNumber",
                            appliance1c."inventoryNumber",
                            "platformItem"."platformId",
                            "platformItem".vendor,
                            "platformItem".platform,
                            office.__id AS "officeId",
                            office.title AS office,
                            office."cityId",
                            office.city,
                            appliance.details
                        FROM equipment.appliances AS appliance
                        LEFT JOIN "dataPort" ON appliance.__id = "dataPort".__appliance_id
                        LEFT JOIN "platformItem" ON appliance.__platform_item_id = "platformItem".__id
                        LEFT JOIN office ON appliance.__location_id = office.__id
                        LEFT JOIN appliance1c ON appliance.__id = appliance1c.__voice_appliance_id
                    ),
                
                    switch AS (
                        SELECT
                            appliance."ipAddress",
                            appliance."inventoryNumber",
                            (appliance.vendor || \' \' || appliance.platform) AS "switchPlatform"
                        FROM appliance
                    ),
                
                    publisher AS (
                        SELECT
                            appliance.details::jsonb->>\'reportName\'  AS "reportName",
                            appliance."ipAddress"
                        FROM appliance
                    )
    
                SELECT
                    phone.name,
                    cast(phone.dn AS citext),
                    phone."alertingName",
                    phone.description AS depiction,
                    cast(phone."vlanId" AS citext),
                    cast(appliance."isActive" AS citext),
                    cast(host(appliance."ipAddress") AS citext) AS "ipAddress",
                    appliance."serialNumber",
                    appliance."inventoryNumber",
                    appliance."platformId",
                    appliance.platform,
                    appliance."officeId",
                    appliance.office,
                    appliance."cityId",
                    appliance.city,
                    phone."cdpNeighborDeviceId" AS "switchHostname",
                    phone."cdpNeighborPort" AS "switchPort",
                    cast(host(phone."cdpNeighborIP") AS citext) AS "switchIp",
                    switch."inventoryNumber" AS "switchInventoryNumber",
                    cast(switch."switchPlatform" AS citext),
                    cast(publisher."reportName" AS citext) AS "publisherName",
                    cast(host(publisher."ipAddress") AS citext) AS "publisherIp"
                FROM phone
                LEFT JOIN appliance ON phone.__appliance_id = appliance.__id
                LEFT JOIN switch ON phone."cdpNeighborIP" = switch."ipAddress"
                LEFT JOIN publisher ON CAST(phone."publisherIp" AS inet) = publisher."ipAddress"
            ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
