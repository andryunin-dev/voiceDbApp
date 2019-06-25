<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1561452407_changeIpamHostView
    extends Migration
{
    public function up()
    {
        $sql['drop nets view'] = '
            DROP view ipam_view.hosts_ports
        ';

        $sql['create hosts_ports view'] = '
        create view ipam_view.hosts_ports as
  SELECT dp.__id                                                                                              AS port_id,
         dp."ipAddress"                                                                                       AS port_ip,
         dp.masklen                                                                                           AS port_masklen,
         netmask(set_masklen(dp."ipAddress", dp.masklen))                                                     AS port_mask,
         set_masklen(dp."ipAddress", COALESCE(dp.masklen, 32))                                                AS port_ip_cidr,
         dp."macAddress"                                                                                      AS port_mac,
         geo.__id                                                                                             AS location_id,
         geo.title                                                                                            AS dev_location,
         dp.comment                                                                                           AS port_comment,
         (dp.details ->> \'description\' :: text)                                                               AS port_desc,
         (dp.details ->> \'portName\' :: text)                                                                  AS port_name,
         dev.__id                                                                                             AS dev_id,
         concat_ws(\' \' :: text, ven.title, pl.title)                                                          AS dev_title,
         devt.type                                                                                            AS dev_type,
         (dev.details ->> \'hostname\' :: text)                                                                 AS dev_hostname,
         dev."lastUpdate"                                                                                     AS dev_last_update,
         (date_part(\'epoch\' :: text, dev."lastUpdate") * (1000) :: double precision)                          AS dev_last_update_ms,
         ((date_part(\'epoch\' :: text, age(now(), dev."lastUpdate")) /
           (3600) :: double precision)) :: integer                                                            AS dev_age_h,
         net.__id                                                                                             AS net_id,
         vrf.__id                                                                                             AS vrf_id,
         vrf.name                                                                                             AS vrf_name,
         dp."dnsName"                                                                                         AS dns
  FROM (((((((((equipment."dataPorts" dp
      LEFT JOIN equipment."dataPortTypes" dpt ON ((dp.__type_port_id = dpt.__id)))
      LEFT JOIN network.networks net ON ((dp.__network_id = net.__id)))
      LEFT JOIN network.vrfs vrf ON ((net.__vrf_id = vrf.__id)))
      JOIN equipment.appliances dev ON ((dp.__appliance_id = dev.__id)))
      JOIN equipment."applianceTypes" devt ON ((dev.__type_id = devt.__id)))
      JOIN equipment.vendors ven ON ((dev.__vendor_id = ven.__id)))
      JOIN equipment."platformItems" pli ON ((dev.__platform_item_id = pli.__id)))
      JOIN equipment.platforms pl ON ((pli.__platform_id = pl.__id)))
      JOIN company.offices geo ON ((dev.__location_id = geo.__id)))
        ';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop nets view'] = '
            DROP view ipam_view.host_ports
        ';
        $sql['create hosts_ports view'] = '
        create view ipam_view.hosts_ports as
  SELECT dp.__id                                                                                              AS port_id,
         dp."ipAddress"                                                                                       AS port_ip,
         dp.masklen                                                                                           AS port_masklen,
         netmask(set_masklen(dp."ipAddress", dp.masklen))                                                     AS port_mask,
         set_masklen(dp."ipAddress", COALESCE(dp.masklen, 32))                                                AS port_ip_cidr,
         dp."macAddress"                                                                                      AS port_mac,
         geo.__id                                                                                             AS location_id,
         geo.title                                                                                            AS dev_location,
         dp.comment                                                                                           AS port_comment,
         (dp.details ->> \'description\' :: text)                                                               AS port_desc,
         (dp.details ->> \'portName\' :: text)                                                                  AS port_name,
         dev.__id                                                                                             AS dev_id,
         concat_ws(\' \' :: text, ven.title, pl.title)                                                          AS dev_title,
         devt.type                                                                                            AS dev_type,
         (dev.details ->> \'hostname\' :: text)                                                                 AS dev_hostname,
         dev."lastUpdate"                                                                                     AS dev_last_update,
         (date_part(\'epoch\' :: text, dev."lastUpdate") * (1000) :: double precision)                          AS dev_last_update_ms,
         ((date_part(\'epoch\' :: text, age(now(), dev."lastUpdate")) /
           (3600) :: double precision)) :: integer                                                            AS dev_age_h,
         net.__id                                                                                             AS net_id,
         vrf.__id                                                                                             AS vrf_id,
         vrf.name                                                                                             AS vrf_name,
         NULL :: text                                                                                         AS dns
  FROM (((((((((equipment."dataPorts" dp
      LEFT JOIN equipment."dataPortTypes" dpt ON ((dp.__type_port_id = dpt.__id)))
      LEFT JOIN network.networks net ON ((dp.__network_id = net.__id)))
      LEFT JOIN network.vrfs vrf ON ((net.__vrf_id = vrf.__id)))
      JOIN equipment.appliances dev ON ((dp.__appliance_id = dev.__id)))
      JOIN equipment."applianceTypes" devt ON ((dev.__type_id = devt.__id)))
      JOIN equipment.vendors ven ON ((dev.__vendor_id = ven.__id)))
      JOIN equipment."platformItems" pli ON ((dev.__platform_item_id = pli.__id)))
      JOIN equipment.platforms pl ON ((pli.__platform_id = pl.__id)))
      JOIN company.offices geo ON ((dev.__location_id = geo.__id)))
        ';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
}