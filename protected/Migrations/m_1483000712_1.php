<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1483000712_1
    extends Migration
{

    public function up()
    {
        $schemas = [
            'geolocation',
            'company',
            'telephony',
            'equipment',
            'provider'
        ];

        foreach ($schemas as $schema) {
            $sqlCreateSchema = 'CREATE SCHEMA IF NOT EXISTS ' . $schema;
            if (true === $this->db->execute($sqlCreateSchema)) {
                echo 'schema ' . $schema . ' created' . "\n";
            }
        }
        
        if ($this->existsTable('geolocation.regions')) {
            $this->dropTable('geolocation.regions');
        }
        $this->createTable('geolocation.regions',
            [
                'region' => ['type' => 'string']
            ]);

        $this->createTable('geolocation.cities',
            [
                'city' => ['type' => 'string']
            ]);

        $this->createTable('geolocation.addresses',
            [
                'address' => ['type' => 'string']
            ]);

        $this->createTable('company.offices',
            [
                'title' => ['type' => 'string'],
                '__region_id' => ['type' => 'link'],
                '__city_id' => ['type' => 'link'],
                '__address_id' => ['type' => 'link'],
                'details' => ['type' => 'jsonb'],
                'comment' => ['type' => 'text']
            ]);

        $sqlAddColumnStatus = 'ALTER TABLE company.offices ADD COLUMN status  office_status';
        if (true === $this->db->execute($sqlAddColumnStatus)) {
            echo 'Column \'status\' added to table \"company.offices\"';
        }

    }

    public function down()
    {
        $this->dropTable('company.offices');

        $this->dropTable('geolocation.regions');
        $this->dropTable('geolocation.cities');
        $this->dropTable('geolocation.addresses');

        $schemas = [
            'geolocation',
            'company',
            'telephony',
            'equipment',
            'provider'
        ];

        foreach ($schemas as $schema) {
            $sqlCreateSchema = 'DROP SCHEMA ' . $schema;
            if (true === $this->db->execute($sqlCreateSchema)) {
                echo 'schema ' . $schema . ' deleted' . "\n";
            }
        }
    }
    
}