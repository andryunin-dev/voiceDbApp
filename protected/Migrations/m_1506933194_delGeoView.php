<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1506933194_delGeoView
    extends Migration
{

    public function up()
    {
        //drop view to change columns type to citext
        $sql['drop_old'] = 'DROP VIEW view.geo';

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
        $sql['geo'] = '
        CREATE OR REPLACE VIEW view.geo AS
            SELECT
              region.title AS region,
              region.__id AS region_id,
              city.title AS city,
              city.__id AS city_id,
              offices.title AS office,
              offices.__id AS office_id,
              offices."lotusId" AS "lotusId",
              offices.comment AS "officeComment",
              offices.details AS "officeDetails",
              address.address AS "officeAddress"
            
            FROM company.offices AS offices
              JOIN geolocation.addresses AS address ON address.__id = offices.__address_id
              JOIN geolocation.cities AS city ON city.__id = address.__city_id
              JOIN geolocation.regions AS region ON region.__id = city.__region_id;
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
    
}