CREATE EXTENSION IF NOT EXISTS postgres_fdw;
DROP EXTENSION IF EXISTS postgres_fdw;

CREATE SCHEMA IF NOT EXISTS "lotus";
DROP SCHEMA IF EXISTS "lotus";

CREATE SERVER lotus_data FOREIGN DATA WRAPPER  postgres_fdw OPTIONS (host 'localhost', dbname 'LotusData');
DROP SERVER lotus_data;

CREATE USER MAPPING FOR CURRENT_USER SERVER lotus_data OPTIONS (user 'postgres', password '');
ALTER USER MAPPING FOR CURRENT_USER SERVER lotus_data OPTIONS (SET user 'postgres', SET password '');
DROP USER MAPPING FOR CURRENT_USER SERVER lotus_data;

IMPORT FOREIGN SCHEMA "public" LIMIT TO ("locations") FROM SERVER lotus_data INTO "lotus";
DROP FOREIGN TABLE "lotus"."locations";
SELECT * FROM "lotus"."locations";