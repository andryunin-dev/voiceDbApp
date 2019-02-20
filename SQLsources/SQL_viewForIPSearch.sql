-- ====== view for search by ip
DROP VIEW IF EXISTS api_view.ip_search;
CREATE VIEW api_view.ip_search AS (
  SELECT net_id id, net_ip ip, 'network' rec_type
  FROM api_view.networks
  UNION
  SELECT port_id id, (CASE WHEN (port_mask_len = 32 OR port_mask_len ISNULL ) THEN host(port_ip)::citext ELSE host(port_ip)::citext || '/' || port_mask_len END)::inet ip, 'host' rec_type FROM api_view.dports
  );