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
