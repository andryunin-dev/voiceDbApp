CREATE EXTENSION IF NOT EXISTS tablefunc;

SELECT * FROM crosstab(
  'SELECT office, region, "platformTitle", count(appliance_id) as count  ' ||
  'FROM view.geo_dev  ' ||
  'WHERE "platformTitle" = ''CP-7942G'' OR "platformTitle" = ''CP-6921''  GROUP BY office, "platformTitle"'
) AS pvt(office citext , "7942" BIGINT, tel69 BIGINT);

SELECT office, "platformTitle", count(appliance_id)
FROM view.geo_dev
-- WHERE "platformTitle" = 'CP-7942G'
GROUP BY office, "platformTitle";

CREATE TABLE ct(id SERIAL, rowid TEXT, attribute TEXT, value TEXT);
INSERT INTO ct(rowid, attribute, value) VALUES('test1','att1','val1');
INSERT INTO ct(rowid, attribute, value) VALUES('test1','att2','val2');
INSERT INTO ct(rowid, attribute, value) VALUES('test1','att3','val3');
INSERT INTO ct(rowid, attribute, value) VALUES('test1','att4','val4');
INSERT INTO ct(rowid, attribute, value) VALUES('test2','att1','val5');
INSERT INTO ct(rowid, attribute, value) VALUES('test2','att2','val6');
INSERT INTO ct(rowid, attribute, value) VALUES('test2','att3','val7');
INSERT INTO ct(rowid, attribute, value) VALUES('test2','att4','val8');

SELECT * FROM ct;

SELECT *
FROM crosstab(
         'select rowid, attribute, value
          from ct
          where attribute = ''att2'' or attribute = ''att3''
          order by 1,2')
  AS ct2(row_name text, category_1 text, category_2 text, category_3 text);

-- source SQL query
SELECT office, region, "platformTitle", count(appliance_id) as count
FROM view.geo_dev
WHERE "appType" = 'phone'  
GROUP BY office, region, "platformTitle";

-- query with packing platform Title info into JSOB object
SELECT
  region,
  office,
  (SELECT jsonb_object_agg(t2."platformTitle", t2.numbers) FROM
    (SELECT "platformTitle", count("platformTitle") AS numbers
     FROM view.dev_geo AS t3
     WHERE "appType" = 'phone' AND t3.region = t1.region AND t3.office = t1.office
     GROUP BY "platformTitle"
     ORDER BY "platformTitle") AS t2) AS "platformTitle"
FROM view.dev_geo AS t1
WHERE "appType" = 'phone'
GROUP BY office, region
ORDER BY region, office;

-- query that was generated by PivotReport class' object
SELECT
  "region",
  "office",
  (SELECT jsonb_object_agg(t3."platformTitle", t3.numbers)
   FROM (
          SELECT
            "platformTitle",
            count("platformTitle") AS numbers
          FROM "view"."geo_dev"  AS t2
          WHERE "appType" = :appType_0 AND "platformTitle" NOTNULL AND t2."region" = t1."region" AND t2."office" = t1."office" AND t2."officeAddress" = t1."officeAddress"
          GROUP BY t2."platformTitle"
          ORDER BY t2."platformTitle" ASC
        ) AS t3
  ) AS "platformTitle"
  ,
  "officeAddress"
FROM "view"."geo_dev" AS t1
GROUP BY "region", "office", "officeAddress", "appType"
HAVING "appType" = :appType_0
ORDER BY "region", "office", "officeAddress"
OFFSET 0 LIMIT 20;


SELECT count(*) FROM (
SELECT
  "region",
  "office",
  "officeAddress"
FROM "view"."geo_dev"
GROUP BY "region", "office", "officeAddress", "appType"
HAVING "appType" = :appType_0) as t1;

SELECT count(*) FROM (
                       SELECT
                         region,
                         city,
                         office
                       FROM "view"."dev_module_port_geo"
                       WHERE "appType" = :appType_eq_0
                       GROUP BY region, city, office
                     ) as t1;


SELECT
  region,
  city,
  office,
  (SELECT jsonb_object_agg(t2."platformTitle", t2.numbers)
   FROM (
          SELECT
            "platformTitle",
            count("platformTitle") AS numbers
          FROM "view"."dev_module_port_geo"  AS t3
          WHERE "appType" = :appType_eq_0 AND t3.region = t1.region AND t3.city = t1.city AND t3.office = t1.office
          GROUP BY "platformTitle"
          ORDER BY "platformTitle" DESC
        ) AS t2
  ) AS "plTitle"

FROM "view"."dev_module_port_geo" AS t1
WHERE "appType" = :appType_eq_0
GROUP BY region, city, office
ORDER BY "region", "city"
LIMIT 50