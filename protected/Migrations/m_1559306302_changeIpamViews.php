<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1559306302_changeIpamViews
    extends Migration
{

    public function up()
    {
        $sql['drop nets view'] = '
            DROP view ipam_view.nets
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
                   null bgp_as
            FROM network.networks net
            JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id
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
            DROP view if exists ipam_view.nets
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

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}