CREATE ROLE developers1c WITH LOGIN;
GRANT CONNECT ON DATABASE "phpVDB" TO developers1c;
GRANT USAGE ON SCHEMA view TO developers1c;
GRANT SELECT ON view.dev_geo_1c_info TO developers1c;


REVOKE SELECT ON view.dev_geo_1c_info FROM developers1c;
REVOKE USAGE ON SCHEMA view FROM developers1c;
REVOKE CONNECT ON DATABASE "phpVDB" FROM developers1c;
DROP ROLE developers1c;