-- ====create api_view for networks
DROP VIEW IF EXISTS api_view.networks;
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
                                 );
--   ==============


-- ====== view for search by ip
DROP VIEW IF EXISTS api_view.ip_search;
CREATE VIEW api_view.ip_search AS (
  SELECT __id id, address ip, 'network' rec_type
  FROM network.networks
  UNION
  SELECT __id id, (CASE WHEN (masklen = 32 OR masklen ISNULL ) THEN host("ipAddress")::citext ELSE host("ipAddress")::citext || '/' || masklen END)::inet ip, 'host' rec_type FROM equipment."dataPorts"
  );



-- usage
SELECT * FROM api_view.ip_search
WHERE ip::citext LIKE '10.1.3%'
ORDER BY ip;

-- usage with ip_path function
SELECT *, network.ip_path(t1.ip, t1.rec_type) FROM api_view.ip_search t1
WHERE ip::citext LIKE '10.1.3%'
ORDER BY ip;