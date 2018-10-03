CREATE MATERIALIZED VIEW view.apps_geo_mat AS
WITH locs AS (
       SELECT
              offices.__id office_id,
              offices.title office,
              offices."lotusId" office_lotus_id,
              offices.details office_details,
              offices.comment office_comment,
              "officeStatuses".__id office_status_id,
              "officeStatuses".title office_status,
              addresses.address office_address,
              cities.__id city_id,
              cities.title city,
              regions.__id region_id,
              regions.title region
       FROM company.offices
       JOIN company."officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
       JOIN geolocation.addresses ON offices.__address_id = addresses.__id
       JOIN geolocation.cities ON addresses.__city_id = cities.__id
       JOIN geolocation.regions ON cities.__region_id = regions.__id
),
apps AS (
       SELECT
              ap.__id app_id,
              ap.__location_id location_id,
              ap.__cluster_id cluster_id,
              ap.__platform_item_id pl_item_id,
              ap.__software_item_id sw_item_id,
              ap.__type_id type_id,
              ap.__vendor_id ven_id,
              dp.__id port_id,
              dp.__network_id port_net_id,
              pl.__id pl_id,
              sw.__id sw_id,
              mdi.__id md_item_id,
              md.__id md_id,
              ap.details app_details,
              ap.comment app_comment,
              ap."lastUpdate" app_last_update,
              ap."inUse" app_in_use,
              vnd.title vendor,
              cl.title cl_name,
              cl.comment cl_comment,
              cl.details cl_details,
              apt.type app_type,
              apt."sortOrder" type_weight,
              dp."ipAddress" port_ip,
              dp."lastUpdate" port_last_update,
              dp.comment port_comment,
              dp.details port_details,
              dp."isManagement" port_is_mng,
              dp."macAddress" port_mac,
              dp.masklen port_mask_len,
              pli.details pl_item_details,
              pli.comment pl_item_comment,
              pli.version pl_ver,
              pli."inventoryNumber" pl_inv_number,
              pli."serialNumber" pl_ser_number,
              pl.title pl,
              pl."isHW" is_hw,
              swi.version sw_ver,
              swi.comment sw_comment,
              swi.details sw_details,
              sw.title sw,
              mdi.details md_item_details,
              mdi.comment md_item_comment,
              mdi."serialNumber" md_ser_number,
              mdi."inventoryNumber" md_inv_number,
              mdi."inUse" md_in_use,
              mdi."notFound" md_not_found,
              mdi."lastUpdate" md_last_update,
              md.title md,
              md.description md_descr
       FROM equipment.appliances ap
       FULL JOIN equipment.clusters cl ON ap.__cluster_id = cl.__id
       LEFT JOIN equipment.vendors vnd ON ap.__vendor_id = vnd.__id
       LEFT JOIN equipment."applianceTypes" apt ON ap.__type_id = apt.__id
       LEFT JOIN equipment."dataPorts" dp ON ap.__id = dp.__appliance_id
       LEFT JOIN equipment."moduleItems" mdi ON ap.__id = mdi.__appliance_id
       JOIN equipment.modules md ON mdi.__module_id = md.__id
       LEFT JOIN equipment."platformItems" pli ON ap.__platform_item_id = pli.__id
       JOIN equipment.platforms pl ON pli.__platform_id = pl.__id
       LEFT JOIN equipment."softwareItems" swi ON ap.__software_item_id = swi.__id
       JOIN equipment.software sw ON swi.__software_id = sw.__id
       ),
nets AS (
    SELECT
           nets.__id net_id,
           nets.__vlan_id net_vlan_id,
           nets.__vrf_id net_vrf_id,
           nets.address net_ip,
           nets.comment net_comment
    FROM network.networks nets
    FULL JOIN network.vlans vlans ON nets.__vlan_id = vlans.__id
    FULL JOIN network.vrfs vrfs ON nets.__vrf_id = vrfs.__id
  )
SELECT *
FROM apps
  FULL JOIN locs ON locs.office_id = apps.location_id
  FULL JOIN nets ON apps.port_net_id = nets.net_id
