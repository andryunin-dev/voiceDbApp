<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1549884118_fixApi_view_devices
    extends Migration
{

    public function up()
    {
        $sql['drop view devices'] = 'DROP VIEW IF EXISTS api_view.devices';
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
       FULL JOIN equipment.platforms pl ON pli.__platform_id = pl.__id
       LEFT JOIN equipment.vendors vndpl ON pl.__vendor_id = vndpl.__id
       LEFT JOIN equipment."softwareItems" swi ON dv.__software_item_id = swi.__id
       FULL JOIN equipment.software sw ON swi.__software_id = sw.__id
       LEFT JOIN equipment.vendors vndsft ON sw.__vendor_id = vndsft.__id)
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
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}