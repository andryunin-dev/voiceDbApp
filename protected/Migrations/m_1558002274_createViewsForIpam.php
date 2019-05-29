<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1558002274_createViewsForIpam
    extends Migration
{

    public function up()
    {
        $sql['create extension '] = "CREATE EXTENSION pg_trgm";
        $sql['create btree index for ipAddress'] = 'CREATE INDEX idx_dport_ipaddress_inet ON equipment."dataPorts" ("ipAddress")';
        $sql['create gin index for ipAddress mask'] = '
        CREATE INDEX idx_dport_ipaddress_cidr_text ON equipment."dataPorts"
        USING gin ((set_masklen("ipAddress", coalesce(masklen, 32))::text) gin_trgm_ops)
        ';
        $sql['create schema ipam_view'] =
            'CREATE SCHEMA ipam_view';
        $sql['create hosts_ports view'] = '
          CREATE VIEW ipam_view.hosts_ports AS
            SELECT dp.__id port_id,
                   dp."ipAddress" port_ip,
                   dp.masklen port_masklen,
                   netmask(set_masklen(dp."ipAddress", dp.masklen)) port_mask,
                   set_masklen(dp."ipAddress", coalesce(dp.masklen, 32)) port_ip_cidr,
                   dp."macAddress" port_mac,
                   geo.__id location_id,
                   geo.title dev_location,
                   dp.comment port_comment,
                   dp.details->>\'description\' port_desc,
                   dp.details->>\'portName\' port_name,
                   dev.__id dev_id,
                   concat_ws(\' \', ven.title, pl.title) dev_title,
                   devt.type dev_type,
                   dev.details->>\'hostname\' dev_hostname,
                   dev."lastUpdate" dev_last_update,
                   EXTRACT(EPOCH FROM dev."lastUpdate") * 1000 dev_last_update_ms,
                   (EXTRACT(EPOCH FROM age(now(), dev."lastUpdate"))/3600)::int dev_age_h,
                   net.__id net_id,
                   vrf.__id vrf_id,
                   vrf.name vrf_name,
                   null dns
            FROM equipment."dataPorts" dp
                LEFT JOIN equipment."dataPortTypes" dpt ON dp.__type_port_id = dpt.__id
                LEFT JOIN network.networks net ON dp.__network_id = net.__id
                LEFT JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id
                JOIN equipment.appliances dev ON dp.__appliance_id = dev.__id
                JOIN equipment."applianceTypes" devt ON dev.__type_id = devt.__id
                JOIN equipment.vendors ven ON dev.__vendor_id = ven.__id
                JOIN equipment."platformItems" pli ON dev.__platform_item_id = pli.__id
                JOIN equipment.platforms pl ON pli.__platform_id = pl.__id
                JOIN company.offices geo ON dev.__location_id = geo.__id
        ';
        $sql['create nets view'] = '
        CREATE VIEW ipam_view.nets AS
            SELECT net.__id net_id,
                   net.address net_ip,
                   netmask(net.address) net_mask,
                   net.comment net_comment,
                   net.__vrf_id vrf_id,
                   vrf.name vrf_name,
                   vrf.rd vrf_rd,
                   vrf.comment vrf_comment,
                   usr_net_children(net.__id) net_children,
                   usr_host_children(net.__id) host_children,
                   usr_network_locations_json(net.__id) net_location,
                   usr_network_locations_string(net.__id) net_location_str,
                   null bgp_as
            FROM network.networks net
            JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id
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
        $sql['drop hosts_ports view'] = '
            DROP view if exists ipam_view.hosts_ports
        ';
        $sql['drop nets view'] = '
            DROP view if exists ipam_view.nets
        ';
        $sql['drop schema ipam'] = 'DROP SCHEMA IF EXISTS ipam_view';
        $sql['drop idx_dport_ipaddress_inet'] = 'DROP INDEX equipment.idx_dport_ipaddress_cidr_text';
        $sql['drop idx_dport_ipaddress_cidr_text'] = 'DROP INDEX equipment.idx_dport_ipaddress_inet';
        $sql['drop extention'] = 'DROP EXTENSION pg_trgm';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}