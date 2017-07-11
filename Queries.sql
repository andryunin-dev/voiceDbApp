SELECT * FROM dataports WHERE network("ipAddress") = network(inet '192.168.1.0/25');
SELECT * FROM dataports WHERE inet '192.168.1.0/25' >> "ipAddress";

-- взять все подсети содержащие данный IP
WITH subnets AS (
    SELECT DISTINCT network("ipAddress") AS subnet FROM dataports
), parent_subnets AS (SELECT * FROM subnets WHERE subnet >>= inet '192.168.1.129/26')
SELECT subnet FROM parent_subnets;

-- взять ближайшую родительскую подсеть содержащую данный IP
WITH subnets AS (
    SELECT DISTINCT network("ipAddress") AS subnet FROM dataports
), parent_subnets AS (SELECT * FROM subnets WHERE subnet >> inet '192.168.1.129/26')
SELECT max(subnet) FROM parent_subnets;
-- статистика по статусам
SELECT devs."platformTitle", devs."platformVendor",
    count(devs.appliance_id) AS total,
    sum(CASE WHEN devs."appAge" < :max_age THEN 1 ELSE 0 END ) AS active,
    sum(CASE WHEN devs."appAge" < :max_age AND devs."appInUse" THEN 1 ELSE 0 END ) AS "active_inUse",
    sum(CASE WHEN devs."appAge" < :max_age AND NOT devs."appInUse" THEN 1 ELSE 0 END ) AS "active_notInUse",
    sum(CASE WHEN devs."appInUse" THEN 1 ELSE 0 END ) AS "inUse",
    sum(CASE WHEN NOT devs."appInUse" THEN 1 ELSE 0 END ) AS "notInUse"
FROM view.geo_dev AS devs
GROUP BY devs.platform_id ,devs."platformTitle", devs."platformVendor";
