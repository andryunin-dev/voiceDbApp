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

        /**
         * Удаляем все схемы каскадно с таблицами
         */
        foreach ($schemas as $schema) {
            $sqlCreateSchema = 'DROP SCHEMA IF EXISTS ' . $schema . ' CASCADE';
            if (true === $this->db->execute($sqlCreateSchema)) {
                echo 'schema ' . $schema . ' deleted' . "\n";
            }
        }

        /**
         *Создание схем
         */
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
                echo 'Schema ' . $schema . ' created' . "\n";
            }
        }

        /**
         * Create tables in geolocation and company schemas
         */
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
                'address' => ['type' => 'text']
            ]);

        $this->createTable('company.officeStatuses',
            [
                'status' => ['type' => 'string']
            ]);

        $this->createTable('company.offices',
            [
                'title' => ['type' => 'string'],
                '__region_id' => ['type' => 'link'],
                '__city_id' => ['type' => 'link'],
                '__address_id' => ['type' => 'link'],
                '__status_id' => ['type' => 'link'],
                'details' => ['type' => 'jsonb'],
                'comment' => ['type' => 'text']
            ]);
        $sql_fk_region_id = '
        ALTER TABLE company."offices" ADD 
        CONSTRAINT fk_region_id FOREIGN KEY (__region_id)
              REFERENCES geolocation."regions" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT';
        $sql_fk_city_id = '
        ALTER TABLE company."offices" ADD 
        CONSTRAINT fk_city_id FOREIGN KEY (__city_id)
              REFERENCES geolocation."cities" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT';
        $sql_fk_address_id = '
        ALTER TABLE company."offices" ADD
        CONSTRAINT fk_address_id FOREIGN KEY (__address_id)
          REFERENCES geolocation."addresses" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT';
        $sql_fk_status_id = '
        ALTER TABLE company."offices" ADD
        CONSTRAINT fk_status_id FOREIGN KEY (__status_id)
          REFERENCES company."officeStatuses" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT';
        $this->db->execute($sql_fk_region_id);
        $this->db->execute($sql_fk_city_id);
        $this->db->execute($sql_fk_address_id);
        $this->db->execute($sql_fk_status_id);

        /**
         * Create tables in equipment schema
         */
        $this->createTable('equipment.dataPortTypes',
            [
                'type' => ['type' => 'string'],

            ]);
        $this->createTable('equipment.dataPorts',
            [
                '__appliance_id' => ['type' => 'link'],
                '__typePort_id' => ['type' => 'link'],
                'ipAddress' => ['type' => 'inet'],
                'details' => ['type' => 'jsonb'],
                'comment' => ['type' => 'text']
            ]);
        $sql_alter_ipAddress_type = '
          ALTER TABLE equipment."dataPorts" ALTER COLUMN "ipAddress" TYPE INET USING "ipAddress"::inet';
        $this->db->execute($sql_alter_ipAddress_type);
    }

    public function down()
    {
        $schemas = [
            'geolocation',
            'company',
            'telephony',
            'equipment',
            'provider'
        ];

        /**
         * Удаляем все схемы каскадно с таблицами
         */
        foreach ($schemas as $schema) {
            $sqlCreateSchema = 'DROP SCHEMA IF EXISTS ' . $schema . ' CASCADE';
            if (true === $this->db->execute($sqlCreateSchema)) {
                echo 'schema ' . $schema . ' deleted' . "\n";
            }
        }
    }
    
}