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