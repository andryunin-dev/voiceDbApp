<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1563953795_changeIpamViewNets
    extends Migration
{

    public function up()
    {
        $sql['drop old view'] = '
            DROP VIEW IF EXISTS ipam_view.nets
        ';
        $sql['drop index'] = '
          drop index if exists equipment.idx_gin_appl_details_bgp_networks
         ';
        $sql['drop function'] = '
            DROP FUNCTION IF EXISTS usr_get_bgp_as(cidr, OUT text)
        ';
        $sql['create index'] = '
            CREATE INDEX idx_gin_appl_details_bgp_networks
            ON equipment.appliances using gin ((details->\'bgp_networks\'))
        ';
        $sql['create function'] = '
            create function usr_get_bgp_as(net_ip cidr, OUT bgp_as text)
                returns text
            language plpgsql
            as $$
            BEGIN
                  bgp_as := (SELECT string_agg(dev.details->>\'bgp_as\', \',\')
                             FROM equipment.appliances dev
                             WHERE dev.details->\'bgp_networks\' @> to_jsonb(net_ip));
            END;
            $$
        ';
        $sql['change view'] = '
        CREATE VIEW ipam_view.nets AS
SELECT net.__id                       AS net_id,
       net.address                    AS net_ip,
       netmask((net.address) :: inet) AS net_mask,
       net.comment                    AS net_comment,
       net.__vrf_id                   AS vrf_id,
       vrf.name                       AS vrf_name,
       vrf.rd                         AS vrf_rd,
       vrf.comment                    AS vrf_comment,
       usr_get_bgp_as(net.address) bgp_as
FROM network.networks net
           JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id';
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop index'] = '
            DROP INDEX IF EXISTS equipment.idx_gin_appl_details_bgp_networks
        ';
        $sql['drop old view'] = '
            DROP VIEW IF EXISTS ipam_view.nets
        ';
        $sql['drop function'] = '
            DROP FUNCTION IF EXISTS usr_get_bgp_as(cidr, OUT text)
        ';
        $sql['restore previous version'] = '
            CREATE VIEW ipam_view.nets AS
      SELECT net.__id                       AS net_id,
             net.address                    AS net_ip,
             netmask((net.address) :: inet) AS net_mask,
             net.comment                    AS net_comment,
             net.__vrf_id                   AS vrf_id,
             vrf.name                       AS vrf_name,
             vrf.rd                         AS vrf_rd,
             vrf.comment                    AS vrf_comment,
             NULL bgp_as
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