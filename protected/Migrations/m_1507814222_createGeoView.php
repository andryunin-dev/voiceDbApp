<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1507814222_createGeoView
    extends Migration
{

    public function up()
    {
        $sql['create_view_geo'] = '
        CREATE OR REPLACE VIEW view.geo AS
        SELECT
            CAST("lotusLoc".reg_center AS citext) AS "regCenter",
            region.title AS region,
            region.__id AS region_id,
            city.title AS city,
            city.__id AS city_id,
            offices.title AS office,
            offices.__id AS office_id,
            offices."lotusId" AS "lotusId",
            offices.comment AS "comment",
            offices.details AS "details",
            address.address AS "address",
            "lotusLoc".employees AS people
        
        FROM company.offices AS offices
            JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
            JOIN geolocation.cities AS city ON city.__id = address.__city_id
            JOIN geolocation.regions AS region ON region.__id = city.__region_id
            LEFT JOIN lotus.locations AS "lotusLoc" ON offices."lotusId" = "lotusLoc".lotus_id
        ';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

    }

    public function down()
    {
        $sql['drop_view_geo'] = 'DROP VIEW view.geo';

        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }

    }
    
}