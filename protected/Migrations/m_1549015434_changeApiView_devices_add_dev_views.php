<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1549015434_changeApiView_devices_add_dev_views
    extends Migration
{

    public function up()
    {
        $sql['drop view devices'] = 'DROP VIEW IF EXISTS api_view.devices';
        $sql['drop view geo'] = 'DROP VIEW IF EXISTS api_view.geo';
        $sql['create view devices'] = '
        CREATE VIEW api_view.devices AS (
SELECT dv.__id               dev_id,
       dv.__location_id      location_id,
       dv.__cluster_id       cluster_id,
       dv.__platform_item_id platform_item_id,
       dv.__software_item_id software_item_id,
       dv.__type_id          dev_type_id,
       dv.__vendor_id        vendor_id,
       pl.__id               platform_id,
       sw.__id               software_id,
       vndsft.__id           software_vendor_id,
       vndpl.__id            platform_vendor_id,
       dv.details            dev_details,
       dv.comment            dev_comment,
       dv."lastUpdate"       dev_last_update,
       dv."inUse"            dev_in_use,
       vnd.title             vendor,
       cl.title              claster_name,
       cl.comment            claster_comment,
       cl.details            claster_details,
       apt.type              dev_type,
       apt."sortOrder"       type_weight,
       pli.details           platform_item_details,
       pli.comment           platform_item_comment,
       pli.version           platform_item_version,
       pli."inventoryNumber" platform_item_inv_number,
       pli."serialNumber"    platform_item_sn,
       pli."serialNumberAlt" platform_item_sn_alt,
       pl.title              platform,
       vndpl.title           platform_vendor,
       pl."isHW"             is_hw,
       sw.title              software,
       vndsft.title          software_vendor,
       swi.version           software_item_ver,
       swi.comment           software_item_comment,
       swi.details           software_item_details
FROM equipment.appliances dv
       FULL JOIN equipment.clusters cl ON dv.__cluster_id = cl.__id
       LEFT JOIN equipment.vendors vnd ON dv.__vendor_id = vnd.__id
       FULL JOIN equipment."applianceTypes" apt ON dv.__type_id = apt.__id
       LEFT JOIN equipment."platformItems" pli ON dv.__platform_item_id = pli.__id
       LEFT JOIN equipment.platforms pl ON pli.__platform_id = pl.__id
       LEFT JOIN equipment.vendors vndpl ON pl.__vendor_id = vndpl.__id
       LEFT JOIN equipment."softwareItems" swi ON dv.__software_item_id = swi.__id
       LEFT JOIN equipment.software sw ON swi.__software_id = sw.__id
       LEFT JOIN equipment.vendors vndsft ON sw.__vendor_id = vndsft.__id)
        ';
        $sql['create view geo'] = '
        CREATE VIEW api_view.geo AS (
SELECT offices.__id           office_id,
       offices.title          office,
       offices."lotusId"      office_lotus_id,
       offices.details        office_details,
       offices.comment        office_comment,
       "officeStatuses".__id  office_status_id,
       "officeStatuses".title office_status,
       addresses.address      office_address,
       cities.__id            city_id,
       cities.title           city,
       regions.__id           region_id,
       regions.title          region
FROM company.offices
       JOIN company."officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
       JOIN geolocation.addresses ON offices.__address_id = addresses.__id
       JOIN geolocation.cities ON addresses.__city_id = cities.__id
       JOIN geolocation.regions ON cities.__region_id = regions.__id)
        ';
        $sql['create view modules'] = '
        CREATE VIEW api_view.modules AS (
SELECT mdi.__appliance_id   dev_id,
       mdi.__id              module_item_id,
       md.__id               module_id,
       mdi.__location_id     module_item_location_id,
       mdi.details           module_item_details,
       mdi.comment           module_item_comment,
       mdi."serialNumber"    module_item_sn,
       mdi."inventoryNumber" module_item_inv_number,
       mdi."inUse"           module_item_in_use,
       mdi."notFound"        module_item_not_found,
       mdi."lastUpdate"      module_item_last_update,
       md.title              module,
       md.description        module_descr
FROM equipment."moduleItems" mdi
       LEFT JOIN equipment.modules md ON mdi.__module_id = md.__id)
        ';
        $sql['create view dports'] = '
        CREATE VIEW api_view.dports AS (
SELECT dp.__appliance_id    dev_id,
       dp.__id               port_id,
       dp.__network_id       port_net_id,
       dpt.__id              port_type_id,
       vrf.__id              port_vrf_id,
       dp."ipAddress"        port_ip,
       dp."lastUpdate"       port_last_update,
       dp.comment            port_comment,
       dp.details            port_details,
       dp."isManagement"     port_is_mng,
       dp."macAddress"       port_mac,
       dp.masklen            port_mask_len,
       dpt.type              port_type
FROM equipment."dataPorts" dp
       LEFT JOIN equipment."dataPortTypes" dpt ON dp.__type_port_id = dpt.__id
       LEFT JOIN network.networks net ON dp.__network_id = net.__id
       LEFT JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id)
        ';
        $sql['create view vrfs'] = '
        CREATE VIEW api_view.vrfs AS (
SELECT vrf.__id              vrf_id,
       vrf.name              vrf_name,
       vrf.rd                vrf_rd,
       vrf.comment           vrf_comment
FROM network.vrfs vrf)
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
        $sql['drop view devices'] = 'DROP VIEW IF EXISTS api_view.devices';
        $sql['drop view dports'] = 'DROP VIEW IF EXISTS api_view.dports';
        $sql['drop view geo'] = 'DROP VIEW IF EXISTS api_view.geo';
        $sql['drop view modules'] = 'DROP VIEW IF EXISTS api_view.modules';
        $sql['drop view vrfs'] = 'DROP VIEW IF EXISTS api_view.vrfs';
        $sql['create view devices'] = '
        CREATE VIEW api_view.devices AS (
SELECT dv.__id               dev_id,
        dv.__location_id      location_id,
        dv.__cluster_id       cluster_id,
        dv.__platform_item_id platform_item_id,
        dv.__software_item_id software_item_id,
        dv.__type_id          dev_type_id,
        dv.__vendor_id        vendor_id,
        dp.__id               port_id,
        dp.__network_id       network_id,
        pl.__id               platform_id,
        sw.__id               software_id,
        mdi.__id              module_item_id,
        md.__id               module_id,
        dpt.__id              port_type_id,
        dv.details            dev_details,
        dv.comment            dev_comment,
        dv."lastUpdate"       dev_last_update,
        dv."inUse"            dev_in_use,
        vnd.title             vendor,
        cl.title              claster_name,
        cl.comment            claster_comment,
        cl.details            claster_details,
        apt.type              dev_type,
        apt."sortOrder"       type_weight,
        dp."ipAddress"        port_ip,
        dp."lastUpdate"       port_last_update,
        dp.comment            port_comment,
        dp.details            port_details,
        dp."isManagement"     port_is_mng,
        dp."macAddress"       port_mac,
        dp.masklen            port_mask_len,
        dpt.type              port_type,
        pli.details           platform_item_details,
        pli.comment           platform_item_comment,
        pli.version           platform_version,
        pli."inventoryNumber" platform_inv_number,
        pli."serialNumber"    platform_sn,
        pli."serialNumberAlt" platform_sn_alt,
        pl.title              platform,
        pl."isHW"             is_hw,
        swi.version           software_ver,
        swi.comment           software_comment,
        swi.details           software_details,
        sw.title              software,
        mdi.details           module_item_details,
        mdi.comment           module_item_comment,
        mdi."serialNumber"    module_item_sn,
        mdi."inventoryNumber" module_item_inv_number,
        mdi."inUse"           module_in_use,
        mdi."notFound"        module_not_found,
        mdi."lastUpdate"      module_last_update,
        md.title              module,
        md.description        module_descr
FROM equipment.appliances dv FULL
       JOIN equipment.clusters cl ON dv.__cluster_id = cl.__id
       LEFT JOIN equipment.vendors vnd ON dv.__vendor_id = vnd.__id FULL
       JOIN equipment."applianceTypes" apt ON dv.__type_id = apt.__id
       LEFT JOIN equipment."dataPorts" dp ON dv.__id = dp.__appliance_id
       LEFT JOIN equipment."dataPortTypes" dpt ON dp.__type_port_id = dpt.__id
       LEFT JOIN equipment."moduleItems" mdi ON dv.__id = mdi.__appliance_id
       LEFT JOIN equipment.modules md ON mdi.__module_id = md.__id
       LEFT JOIN equipment."platformItems" pli ON dv.__platform_item_id = pli.__id
       JOIN equipment.platforms pl ON pli.__platform_id = pl.__id
       LEFT JOIN equipment."softwareItems" swi ON dv.__software_item_id = swi.__id
       LEFT JOIN equipment.software sw ON swi.__software_id = sw.__id)
       ';
        $sql['create view geo'] = '
        CREATE VIEW api_view.geo AS (
  SELECT offices.__id           location_id,
         offices.title          office,
         offices."lotusId"      office_lotus_id,
         offices.details        office_details,
         offices.comment        office_comment,
         "officeStatuses".__id  office_status_id,
         "officeStatuses".title office_status,
         addresses.address      office_address,
         cities.__id            city_id,
         cities.title           city,
         regions.__id           region_id,
         regions.title          region
  FROM company.offices
         JOIN company."officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
         JOIN geolocation.addresses ON offices.__address_id = addresses.__id
         JOIN geolocation.cities ON addresses.__city_id = cities.__id
         JOIN geolocation.regions ON cities.__region_id = regions.__id
)
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}