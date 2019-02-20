DROP VIEW IF EXISTS view.net_report;
CREATE VIEW view.net_report AS
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
             dv.__type_id dev_type_id,
             dv.__location_id location_id,
             net.__id network_id,
             net.address::inet network,
             regexp_replace(dv.details::jsonb->>'hostname', '([a-z_0-9]+)-([a-z0-9]+)-.*', '\1-\2') short_hostname,
             dp.masklen masklen,
             (EXTRACT(EPOCH FROM age(now(), dv."lastUpdate"))/3600)::INT AS app_age,
             dp."ipAddress",
             dv."inUse" dev_in_use,
             apt.type dev_type,
             (EXTRACT(EPOCH FROM age(now(), dp."lastUpdate"))/3600)::INT port_age
      FROM equipment.appliances dv
             JOIN equipment."applianceTypes" apt ON dv.__type_id = apt.__id
             JOIN equipment."dataPorts" dp ON dv.__id = dp.__appliance_id
             JOIN network.networks net ON dp.__network_id = net.__id
  )
SELECT
--        host(network(concat_ws('/', dp."ipAddress", dp.masklen)::inet))::inet  network,
       host(devs.network)::inet network,
-- devs.dev_type dev_type,
       devs.short_hostname netname,
       devs.masklen "range",
       locs.office office,
       locs.city city,
       locs.office_address "postAddress"
FROM devs
    JOIN locs USING (location_id)
WHERE devs.dev_type_id = 6 AND
      devs.masklen < 30 AND
      devs.port_age < 72 AND
      (
          devs.network <<= '10.100.0.0/16'::inet OR
          devs.network <<= '10.99.0.0/16'::inet OR
          devs.network <<= '10.9.0.0/16'::inet OR
          devs.network <<= '10.35.0.0/16'::inet OR
          devs.network <<= '10.12.4.0/24'::inet OR
          devs.network <<= '10.1.39.0/24'::inet OR
          devs.network <<= '10.4.1.0/24'::inet OR
          devs.network <<= '10.4.2.0/24'::inet OR
          devs.network <<= '10.4.4.0/24'::inet OR
          devs.network <<= '10.4.8.0/24'::inet OR
          devs.network <<= '10.4.12.0/24'::inet OR
          devs.network <<= '10.4.16.0/24'::inet OR
          devs.network <<= '10.4.20.0/24'::inet OR
          devs.network <<= '10.4.36.0/24'::inet OR
          devs.network <<= '10.4.50.0/24'::inet OR
          devs.network <<= '10.4.55.0/24'::inet OR
          devs.network <<= '10.4.56.0/24'::inet OR
          devs.network <<= '10.4.60.0/24'::inet OR
          devs.network <<= '10.4.62.0/24'::inet OR
          devs.network <<= '10.4.115.0/24'::inet OR
          devs.network <<= '10.4.197.0/24'::inet OR
          devs.network <<= '10.4.198.0/24'::inet OR
          devs.network <<= '10.36.0.0/24'::inet OR
          devs.network <<= '10.4.199.0/24'::inet
       )
GROUP BY (network, netname, range, office, city, "postAddress")
ORDER BY network;
