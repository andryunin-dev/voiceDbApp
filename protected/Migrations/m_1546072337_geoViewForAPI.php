<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1546072337_geoViewForAPI
    extends Migration
{

    public function up()
    {
        $sql['create schema'] = 'CREATE SCHEMA IF NOT EXISTS api_view';
        $sql['create view'] = '
        CREATE VIEW api_view.geo AS (
  SELECT offices.__id           location_id,
         offices.title          office,
         offices."lotusId"      office_lotus_id,
         offices.details        office_details,
         offices.comment        office_comment,
         "officeStatuses".__id  office_status_id,
         "officeStatuses".title office_status,
         addresses.address      office_address,
         cities.__id            city_id,
         cities.title           city,
         regions.__id           region_id,
         regions.title          region
  FROM company.offices
         JOIN company."officeStatuses" ON offices.__office_status_id = "officeStatuses".__id
         JOIN geolocation.addresses ON offices.__address_id = addresses.__id
         JOIN geolocation.cities ON addresses.__city_id = cities.__id
         JOIN geolocation.regions ON cities.__region_id = regions.__id
)
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop view'] = 'DROP VIEW IF EXISTS api_view.geo';
        $sql['drop schema'] = 'DROP SCHEMA IF EXISTS api_view';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}