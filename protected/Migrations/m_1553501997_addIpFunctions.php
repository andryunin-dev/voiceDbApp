<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1553501997_addIpFunctions
    extends Migration
{
    
    public function up()
    {
        
        $sql['drop ip_path function'] = 'DROP FUNCTION IF EXISTS network.ip_path(inet, text, OUT text) ';
        $sql['create ip_path function'] =
            '
CREATE OR REPLACE FUNCTION network.ip_path(IN ip inet, IN rec_type text, OUT path text) AS $$
BEGIN
  IF rec_type = \'network\' THEN
    path := (SELECT string_agg(t2.id::citext, \',\')
             FROM (
                  SELECT t1.__id id, t1.address ip
                  FROM network.networks t1
                  WHERE ip << t1.address
                  ORDER BY t1.address
                  ) t2
            );
  ELSEIF rec_type = \'host\' THEN
      path := (SELECT string_agg(t2.id::citext, \',\')
               FROM (
                    SELECT t1.__id id, t1.address ip
                    FROM network.networks t1
                    WHERE ip <<= t1.address
                    ORDER BY t1.address
                    ) t2
              );
  ELSE path := NULL ;
  END IF;
END;
$$
LANGUAGE plpgsql';
        $sql['drop old api_view.ip_search'] = 'DROP VIEW IF EXISTS api_view.ip_search';
        $sql['drop old api_view.networks'] = 'DROP VIEW IF EXISTS api_view.networks';
        $sql['drop ip_path function'] = 'DROP FUNCTION IF EXISTS network.ip_path(inet, text, OUT text) ';
        $sql['create api_view.networks'] = '
        CREATE VIEW api_view.networks AS (
  SELECT
         nets.__id net_id,
         nets.__vlan_id vlan_id,
         nets.__vrf_id vrf_id,
         nets.address net_ip,
         nets.comment net_comment,
         vlans.id vlan_number,
         vlans.name vlan_name,
         vlans.comment vlan_comment,
         vrfs.rd vrf_rd,
         vrfs.name vrf_name,
         vrfs.comment vrf_comment
  FROM network.networks nets
           FULL JOIN network.vlans vlans ON nets.__vlan_id = vlans.__id
           FULL JOIN network.vrfs vrfs ON nets.__vrf_id = vrfs.__id
  )';
        $sql['create api_view.ip_search'] = '
        CREATE VIEW api_view.ip_search AS (
  SELECT __id id, address ip, \'network\' rec_type
  FROM network.networks
  UNION
  SELECT __id id, (CASE WHEN (masklen = 32 OR masklen ISNULL ) THEN host("ipAddress")::citext ELSE host("ipAddress")::citext || \'/\' || masklen END)::inet ip, \'host\' rec_type FROM equipment."dataPorts"
  )';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
    public function down()
    {
        $sql['drop old api_view.ip_search'] = 'DROP VIEW IF EXISTS api_view.ip_search';
        $sql['drop api_view.networks'] = 'DROP VIEW IF EXISTS api_view.networks';
        $sql['drop ip_path function'] = 'DROP FUNCTION IF EXISTS network.ip_path(inet, text, OUT text) ';
        
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}