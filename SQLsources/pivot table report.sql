DROP VIEW IF EXISTS view.pivot_test;

CREATE OR REPLACE VIEW view.pivot_test AS
SELECT  offices.title AS office, pl.title AS platform, count(appliances.__id) AS quantity
FROM company.offices AS offices
  JOIN equipment.appliances AS appliances ON offices.__id = appliances.__location_id
  JOIN equipment."platformItems" AS "plItems" ON appliances.__platform_item_id = "plItems".__id
  JOIN equipment.platforms AS pl ON "plItems".__platform_id = pl.__id
GROUP BY offices.title, pl.title;

SELECT * FROM view.pivot_test;

SELECT array_to_string(array_agg(t1),' INT, ') FROM (SELECT DISTINCT platform FROM view.pivot_test ORDER BY platform) AS t1;
SELECT string_agg(t1::text, ',') FROM (SELECT title FROM company.offices) AS t1;