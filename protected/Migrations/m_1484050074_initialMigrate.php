<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1484050074_initialMigrate
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
         * @var array $sql массив запросов для миграции
         */
        $sql = [];
        /**
         * Create tables in geolocation and company schemas
         */
        $sql['geolocation.regions'] = 'CREATE TABLE geolocation."regions" (
                  __id   SERIAL,
                  title VARCHAR(50),
                  PRIMARY KEY (__id)
                )';

        $sql['geolocation.cities'] = 'CREATE TABLE geolocation."cities" (
                  __id   SERIAL,
                  title VARCHAR(50),
                  __region_id    BIGINT,
                  PRIMARY KEY (__id),
                  CONSTRAINT fk_region_id FOREIGN KEY (__region_id)
                    REFERENCES geolocation."regions" (__id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
                  )';

        $sql['geolocation.addresses'] = 'CREATE TABLE geolocation."addresses" (
                  __id      SERIAL,
                  __city_id    BIGINT,
                  address TEXT, --{"street": ,"building"} пока сделал text
                  PRIMARY KEY (__id),
                  CONSTRAINT fk_city_id FOREIGN KEY (__city_id)
                    REFERENCES geolocation."cities" (__id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
                  )';

        $sql['company.officeStatuses'] = 'CREATE TABLE company."officeStatuses" (
                  __id SERIAL,
                  title varchar(50),
                  PRIMARY KEY (__id)
                )';

        $sql['company.offices'] = 'CREATE TABLE company."offices" (
                  __id         SERIAL,
                  title       VARCHAR(200),
                  "lotusId"     INTEGER UNIQUE ,
                  __address_id BIGINT,
                  "__officeStatus_id"  BIGINT,
                  details JSONB,
                  comment TEXT,
                  PRIMARY KEY (__id),
                  CONSTRAINT fk_address_id FOREIGN KEY (__address_id)
                    REFERENCES geolocation."addresses" (__id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT,
                  CONSTRAINT fk_status_id FOREIGN KEY ("__officeStatus_id")
                    REFERENCES company."officeStatuses" (__id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
                )';

        /**
         * Create tables in equipment schema
         */
        $sql['equipment.clusters'] = 'CREATE TABLE equipment."clusters" (
                  __id SERIAL,
                  title VARCHAR(200),
                  details JSONB,
                  comment TEXT,
                  PRIMARY KEY (__id)
                )';

        $sql['equipment.vendors'] = 'CREATE TABLE equipment."vendors" (
		  __id SERIAL,
		  name VARCHAR(200),
		  PRIMARY KEY (__id)
		)';

        $sql['equipment.platforms'] = 'CREATE TABLE equipment."platforms" (
		  __id SERIAL,
		  title VARCHAR(200),
		  details JSONB, -- some details about platform(i.e. memory size, CPU etc.)
		  comment TEXT,
		  PRIMARY KEY (__id)
		)';

        $sql['equipment.software'] = 'CREATE TABLE equipment."software" (
		  __id SERIAL,
		  title VARCHAR(200),
		  version VARCHAR(200),
		  details JSONB,
		  comment TEXT,
		  PRIMARY KEY (__id)
		)';

        $sql['equipment.appliances'] = 'CREATE TABLE equipment."appliances" (
		  __id SERIAL,
		  __cluster_id BIGINT NOT NULL,
		  __vendor_id BIGINT NOT NULL,
		  __platform_id BIGINT NOT NULL,
		  __software_id BIGINT NOT NULL,
		  details JSONB,
		  comment TEXT,
		  __location_id BIGINT NOT NULL,
		  PRIMARY KEY (__id),
		  CONSTRAINT fk_cluster_id FOREIGN KEY (__cluster_id)
		    REFERENCES equipment."clusters" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT,
		  CONSTRAINT fk_vendor_id FOREIGN KEY (__vendor_id)
		    REFERENCES equipment."vendors" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT,
		  CONSTRAINT fk_platform_id FOREIGN KEY (__platform_id)
		    REFERENCES equipment."platforms" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT,
		  CONSTRAINT fk_software_id FOREIGN KEY (__software_id)
		    REFERENCES equipment."software" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT,
		  CONSTRAINT fk_location_id FOREIGN KEY (__location_id)
		    REFERENCES company."offices" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT
		)';

        $sql['equipment.dataPortTypes'] = 'CREATE TABLE equipment."dataPortTypes" (
		  __id SERIAL,
		  type VARCHAR(200),
		  PRIMARY KEY (__id)
		)';

        $sql['equipment.dataPorts'] = 'CREATE TABLE equipment."dataPorts" (
		  __id SERIAL,
		  __appliance_id BIGINT NOT NULL,
		  "__typePort_id" BIGINT NOT NULL,
		  "ipAddress" INET,
		  details JSONB,
		  comment TEXT,
		  PRIMARY KEY (__id),
		  CONSTRAINT fk_appliance_id FOREIGN KEY (__appliance_id)
		    REFERENCES equipment."appliances" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT, -- можно попробовать CASCADE(надо тестить)
		  CONSTRAINT fk_typePort_id FOREIGN KEY ("__typePort_id")
		    REFERENCES equipment."dataPortTypes" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT
		)';

        $sql['equipment.voicePortTypes'] = 'CREATE TABLE equipment."voicePortTypes" (
		  __id SERIAL,
		  type VARCHAR(200),
		  PRIMARY KEY (__id)
		)';

        $sql['equipment.voicePorts'] = 'CREATE TABLE equipment."voicePorts" (
		  __id SERIAL,
		  __appliance_id BIGINT NOT NULL,
		  "__typePort_id" BIGINT NOT NULL,
		  details JSONB,
		  comment TEXT,
		  PRIMARY KEY (__id),
		  CONSTRAINT fk_appliance_id FOREIGN KEY (__appliance_id)
		    REFERENCES equipment."appliances" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT,-- можно попробовать CASCADE(надо тестить)
		
		  CONSTRAINT fk_typePort_id FOREIGN KEY ("__typePort_id")
		    REFERENCES equipment."voicePortTypes" (__id)
		    ON UPDATE CASCADE
		    ON DELETE RESTRICT
		)';

        /**
         * create tables in telephony schema
         */
        $sql['telephony.pstnNumbers'] = 'CREATE TABLE telephony."pstnNumbers" (
              __id SERIAL,
              number CHAR(15) UNIQUE,
              "transferedTo"  CHAR(15) DEFAULT NULL,
              "__voicePort_id" BIGINT NOT NULL, -- что ставить если номер переадресован? Как вариант создать девайс FreePool.
              comment TEXT,
              PRIMARY KEY (__id),
              CONSTRAINT fk_voicePort_id FOREIGN KEY ("__voicePort_id")
                REFERENCES equipment."voicePorts" (__id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
            )';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Table ' . $key . ' is created' . "\n";
            }
        }
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
                echo 'schema ' . $schema . ' with all tables is deleted' . "\n";
            }
        }
    }

}