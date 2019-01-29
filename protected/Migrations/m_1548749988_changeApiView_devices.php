<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1548749988_changeApiView_devices
    extends Migration
{

    public function up()
    {
        $sql['drop view'] = 'DROP VIEW IF EXISTS api_view.devices';
        $sql['create view'] = '
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
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    
    }

    public function down()
    {
        $sql['drop view'] = 'DROP VIEW IF EXISTS api_view.devices';
        $sql['create view'] = '
        CREATE VIEW api_view.devices AS (
SELECT
       dv.__id dev_id,
       dv.__location_id location_id,
       dv.__cluster_id cluster_id,
       dv.__platform_item_id platform_item_id,
       dv.__software_item_id software_item_id,
       dv.__type_id dev_type_id,
       dv.__vendor_id vendor_id,
       dp.__id port_id,
       dp.__network_id network_id,
       vrf.__id port_vrf_id,
       vrf.name port_vrf_name,
       pl.__id platform_id,
       sw.__id software_id,
       mdi.__id module_item_id,
       md.__id module_id,
       dv.details dev_details,
       dv.comment dev_comment,
       dv."lastUpdate" dev_last_update,
       dv."inUse" dev_in_use,
       vnd.title vendor,
       cl.title claster_name,
       cl.comment claster_comment,
       cl.details claster_details,
       apt.type dev_type,
       apt."sortOrder" type_weight,
       dp."ipAddress" port_ip,
       dp."lastUpdate" port_last_update,
       dp.comment port_comment,
       dp.details port_details,
       dp."isManagement" port_is_mng,
       dp."macAddress" port_mac,
       dp.masklen port_mask_len,
       pli.details platform_item_details,
       pli.comment platform_item_comment,
       pli.version platform_version,
       pli."inventoryNumber" platform_inv_number,
       pli."serialNumber" platform_sn,
       pli."serialNumberAlt" platform_sn_alt,
       pl.title platform,
       pl."isHW" is_hw,
       swi.version software_ver,
       swi.comment software_comment,
       swi.details software_details,
       sw.title software,
       mdi.details module_item_details,
       mdi.comment module_item_comment,
       mdi."serialNumber" module_item_sn,
       mdi."inventoryNumber" module_item_inv_number,
       mdi."inUse" module_in_use,
       mdi."notFound" module_not_found,
       mdi."lastUpdate" module_last_update,
       md.title module,
       md.description module_descr
FROM equipment.appliances dv
         FULL JOIN equipment.clusters cl ON dv.__cluster_id = cl.__id
              LEFT JOIN equipment.vendors vnd ON dv.__vendor_id = vnd.__id
         FULL JOIN equipment."applianceTypes" apt ON dv.__type_id = apt.__id
              LEFT JOIN equipment."dataPorts" dp ON dv.__id = dp.__appliance_id
              LEFT JOIN equipment."moduleItems" mdi ON dv.__id = mdi.__appliance_id
              LEFT JOIN equipment.modules md ON mdi.__module_id = md.__id
              LEFT JOIN equipment."platformItems" pli ON dv.__platform_item_id = pli.__id
              JOIN equipment.platforms pl ON pli.__platform_id = pl.__id
              LEFT JOIN equipment."softwareItems" swi ON dv.__software_item_id = swi.__id
              LEFT JOIN equipment.software sw ON swi.__software_id = sw.__id
              LEFT JOIN network.networks net ON dp.__network_id = net.__id
              LEFT JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id
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