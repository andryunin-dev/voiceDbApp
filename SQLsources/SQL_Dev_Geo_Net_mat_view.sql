DROP materialized view IF EXISTS view.dev_geo_net_mat;
CREATE MATERIALIZED VIEW view.dev_geo_net_mat AS
  WITH locs AS (
      SELECT
             offices.__id location_id,
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
      devs AS (
        SELECT
               dv.__id dev_id,
               dv.__location_id location_id,
               dv.__cluster_id cluster_id,
               dv.__platform_item_id pl_item_id,
               dv.__software_item_id sw_item_id,
               dv.__type_id type_id,
               dv.__vendor_id ven_id,
               dp.__id port_id,
               dp.__network_id net_id,
               pl.__id pl_id,
               sw.__id sw_id,
               mdi.__id md_item_id,
               md.__id md_id,
               dv.details dev_details,
               dv.comment dev_comment,
               dv."lastUpdate" dev_last_update,
               dv."inUse" dev_in_use,
               vnd.title vendor,
               cl.title cl_name,
               cl.comment cl_comment,
               cl.details cl_details,
               apt.type dev_type,
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
    ),
      phi AS (
        SELECT
              __appliance_id dev_id,
               name ph_name,
               model ph_model,
               prefix ph_prefix,
               "phoneDN" ph_dn,
               status ph_status,
               description ph_description,
               css ph_css,
               "devicePool" ph_dev_pool,
               "alertingName" ph_alerting_name,
               partition ph_partition,
               timezone ph_timezone,
               "dhcpEnabled" ph_dhcp_en,
               "dhcpServer" ph_dhcp_server,
               "domainName" ph_domain_name,
               "tftpServer1" ph_tftp1,
               "tftpServer2" ph_tftp2,
               "defaultRouter" ph_default_router,
               "dnsServer1" ph_dns1,
               "dnsServer2" ph_dns2,
               "callManager1" ph_callmanager1,
               "callManager2" ph_callmanager2,
               "callManager3" ph_callmanager3,
               "callManager4" ph_callmanager4,
               "vlanId" ph_vlan_id,
               "userLocale" ph_user_locale,
               "cdpNeighborDeviceId" ph_cdp_neigh_dev_id,
               "cdpNeighborIP" ph_cdp_neigh_ip,
               "cdpNeighborPort" ph_cdp_neigh_port,
               "publisherIp" ph_publisher_ip,
               "unknownLocation" ph_unknown_location
        FROM equipment."phoneInfo" phi
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
  FROM devs
          FULL JOIN phi USING (dev_id)
           FULL JOIN locs USING (location_id)
           FULL JOIN nets USING (net_id);

-- SELECT example
SELECT
       location_id, pl_item_id,
       array_agg(DISTINCT pl_item_id) FILTER (WHERE type_id = 3) phones,
       array_agg(DISTINCT pl_item_id) FILTER (WHERE type_id != 3) other,
       array_agg(DISTINCT location_id)
FROM view.devs_geo_nets_mat
GROUP BY GROUPING SETS ( (location_id), (pl_item_id) );
