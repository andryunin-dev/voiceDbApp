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
            'partners',
            'contact_book'
        ];

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
            __region_id    BIGINT,
            title VARCHAR(50),
            "diallingCode" VARCHAR(10),
            PRIMARY KEY (__id),
            CONSTRAINT fk_region_id FOREIGN KEY (__region_id)
                REFERENCES geolocation."regions" (__id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        )';

        $sql['geolocation.addresses'] = 'CREATE TABLE geolocation."addresses" (
            __id      SERIAL,
            __city_id    BIGINT,
            address TEXT, --{street: ,building} пока сделал text
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
            __address_id BIGINT,
            __office_status_id  BIGINT,
            title       VARCHAR(200),
            "lotusId"     INTEGER UNIQUE ,
            details JSONB,
            comment TEXT,
            PRIMARY KEY (__id),
            CONSTRAINT fk_address_id FOREIGN KEY (__address_id)
                REFERENCES geolocation."addresses" (__id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT,
            CONSTRAINT fk_status_id FOREIGN KEY (__office_status_id)
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
            comments TEXT,
            PRIMARY KEY (__id)
        )';

        $sql['equipment.vendors'] = 'CREATE TABLE equipment."vendors" (
            __id SERIAL,
            title VARCHAR(200),
            PRIMARY KEY (__id)
        )';

        $sql['equipment.platforms'] = 'CREATE TABLE equipment."platforms" (
            __id SERIAL,
            __vendor_id BIGINT NOT NULL,
            title VARCHAR(200),
            PRIMARY KEY (__id),
            CONSTRAINT fk_vendor_id FOREIGN KEY (__vendor_id)
            REFERENCES equipment."vendors" (__id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        )';

        $sql['equipment.platformItems'] = 'CREATE TABLE equipment."platformItems" (
            __id SERIAL,
            __platform_id BIGINT NOT NULL,
            "serialNumber" VARCHAR(40),
            "inventoryNumber" VARCHAR(40),
            version VARCHAR(200),
            details JSONB, -- some details about platform(i.e. memory size, CPU etc.)
            comment TEXT,
            PRIMARY KEY (__id),
            CONSTRAINT fk_platform_id FOREIGN KEY (__platform_id)
                REFERENCES equipment."platforms" (__id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        )';

        $sql['equipment.software'] = 'CREATE TABLE equipment."software" (
            __id SERIAL,
            __vendor_id BIGINT NOT NULL,
            title VARCHAR(200),
            PRIMARY KEY (__id),
            CONSTRAINT fk_vendor_id FOREIGN KEY (__vendor_id)
            REFERENCES equipment."vendors" (__id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        )';


        $sql['equipment.softwareItems'] = 'CREATE TABLE equipment."softwareItems" (
            __id SERIAL,
            __software_id BIGINT NOT NULL,
            version VARCHAR(200),
            details JSONB,
            comment TEXT,
            PRIMARY KEY (__id),
            CONSTRAINT fk_software_id FOREIGN KEY (__software_id)
            REFERENCES equipment."software" (__id)
                ON UPDATE CASCADE
                ON DELETE RESTRICT
        )';

        $sql['equipment.appliances'] = 'CREATE TABLE equipment."appliances" (
           __id SERIAL,
           __cluster_id BIGINT NOT NULL,
           __vendor_id BIGINT NOT NULL,
           __platform_item_id BIGINT NOT NULL,
           __software_item_id BIGINT NOT NULL,
           __location_id BIGINT NOT NULL,
           details JSONB,
           comment TEXT,
           PRIMARY KEY (__id),
           CONSTRAINT fk_cluster_id FOREIGN KEY (__cluster_id)
             REFERENCES equipment."clusters" (__id)
             ON UPDATE CASCADE
             ON DELETE RESTRICT,
           CONSTRAINT fk_vendor_id FOREIGN KEY (__vendor_id)
             REFERENCES equipment."vendors" (__id)
             ON UPDATE CASCADE
             ON DELETE RESTRICT,
           CONSTRAINT fk_platform_item_id FOREIGN KEY (__platform_item_id)
             REFERENCES equipment."platformItems" (__id)
             ON UPDATE CASCADE
             ON DELETE RESTRICT,
           CONSTRAINT fk_software_item_id FOREIGN KEY (__software_item_id)
             REFERENCES equipment."softwareItems" (__id)
             ON UPDATE CASCADE
             ON DELETE RESTRICT,
           CONSTRAINT fk_location_id FOREIGN KEY (__location_id)
             REFERENCES company."offices" (__id)
             ON UPDATE CASCADE
             ON DELETE RESTRICT
        )';

        $sql['equipment.modules'] = 'CREATE TABLE equipment."modules" (
            __id SERIAL,
            __vendor_id BIGINT NOT NULL,
            "partNumber" VARCHAR(200),
            comment TEXT,
            PRIMARY KEY (__id),
            CONSTRAINT fk_vendor_id FOREIGN KEY (__vendor_id)
              REFERENCES equipment."vendors" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT
        )';

        $sql['equipment.moduleItems'] = 'CREATE TABLE equipment."moduleItems" (
          __id SERIAL,
          __module_id BIGINT NOT NULL,
          __appliance_id BIGINT NOT NULL,
          "serialNumber" VARCHAR(40),
          "inventoryNumber" VARCHAR(40),
          details JSONB,
          comment TEXT,
          PRIMARY KEY (__id),
          CONSTRAINT fk_module_id FOREIGN KEY (__module_id)
            REFERENCES equipment."modules" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT,
          CONSTRAINT fk_appliance_id FOREIGN KEY (__appliance_id)
            REFERENCES equipment."appliances" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
        )';

        //--виртуальные порты для привязки pstn номеров и каналов к appliance
        $sql['equipment.dataPortTypes'] = 'CREATE TABLE equipment."dataPortTypes" (
            __id SERIAL,
            type VARCHAR(200),
            PRIMARY KEY (__id)
        )';

        $sql['equipment.dataPorts'] = 'CREATE TABLE equipment."dataPorts" (
            __id SERIAL,
            __appliance_id BIGINT NOT NULL,
            __type_port_id BIGINT NOT NULL,
            "ipAddress" INET,
            "macAddress" MACADDR,
            details JSONB,
            comment TEXT,
            PRIMARY KEY (__id),
            CONSTRAINT fk_appliance_id FOREIGN KEY (__appliance_id)
              REFERENCES equipment."appliances" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT, -- можно попробовать CASCADE(надо тестить)
            CONSTRAINT fk_typePort_id FOREIGN KEY (__type_port_id)
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
            __type_port_id BIGINT NOT NULL,
            details JSONB,
            comment TEXT,
            PRIMARY KEY (__id),
            CONSTRAINT fk_appliance_id FOREIGN KEY (__appliance_id)
              REFERENCES equipment."appliances" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT,-- можно попробовать CASCADE(надо тестить)
            CONSTRAINT fk_typePort_id FOREIGN KEY (__type_port_id)
              REFERENCES equipment."voicePortTypes" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT
        )';
        /**
         * create tables in telephony schema
         * PSTN numbers
         */
        $sql['telephony.pstnNumbers'] = 'CREATE TABLE telephony."pstnNumbers" (
          __id SERIAL,
          number CHAR(15) UNIQUE,
          "transferedTo"  CHAR(15) DEFAULT NULL,
          __voice_port_id BIGINT NOT NULL, -- что ставить если номер переадресован? Как вариант создать девайс FreePool.
          comment TEXT,
          PRIMARY KEY (__id),
          CONSTRAINT fk_voicePort_id FOREIGN KEY (__voice_port_id)
            REFERENCES equipment."voicePorts" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
        )';
        /**
         * create tables in partners schema
         */
        //--сторонние организации
        $sql['partners.organisations'] = 'CREATE TABLE partners."organisations" (
            __id SERIAL,
            title VARCHAR(200),
            PRIMARY KEY (__id)
        )';

        $sql['partners.offices'] = 'CREATE TABLE partners."offices" (
            __id SERIAL,
            __organisation_id BIGINT NOT NULL,
            __address_id BIGINT NOT NULL,
            PRIMARY KEY (__id),
            CONSTRAINT fk_organisation_id FOREIGN KEY (__organisation_id)
              REFERENCES partners."organisations" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT,
            CONSTRAINT fk_address_id FOREIGN KEY (__address_id)
              REFERENCES geolocation."addresses" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT
        )';

        //--договоры
        $sql['partners.contracts'] = 'CREATE TABLE partners."contracts" (
            __id SERIAL,
            __partner_id BIGINT NOT NULL,
            number VARCHAR(50),
            date DATE,
            "pathToScan" VARCHAR(255),
            PRIMARY KEY (__id),
            CONSTRAINT fk_partner_id FOREIGN KEY (__partner_id) --ссылка на конкретный офис партнера
              REFERENCES partners."offices" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT
        )';

        /**
         * create tables in contacts schema
         * контакты
         */
        $sql['contact_book.contacts'] = 'CREATE TABLE contact_book."contacts" (
          __id SERIAL,
          name VARCHAR(200),
          __workplace_id BIGINT NOT NULL,
          position VARCHAR(200), --должность или за что отвечает
          comment TEXT,
          PRIMARY KEY (__id),
          CONSTRAINT fk_workplace_id FOREIGN KEY (__workplace_id)
            REFERENCES partners."offices" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
        )';

        $sql['contact_book.types'] = 'CREATE TABLE contact_book.types (
            __id SERIAL,
            type VARCHAR(100), --рабочий , личный
            PRIMARY KEY (__id)
        )';

        $sql['contact_book.phones'] = 'CREATE TABLE contact_book."phones" (
            __id SERIAL,
            __contact_id BIGINT NOT NULL,
            __type_id BIGINT NOT NULL,
            phone VARCHAR(15),
            extention VARCHAR(10),
            PRIMARY KEY (__id),
            CONSTRAINT fk_phone_type_id FOREIGN KEY (__type_id)
              REFERENCES contact_book."types" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT,
            CONSTRAINT fk_contact_id FOREIGN KEY (__contact_id)
              REFERENCES contact_book."contacts" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT
        )';

        $sql['contact_book.emails'] = 'CREATE TABLE contact_book."emails" (
            __id SERIAL,
            __contact_id BIGINT NOT NULL,
            __type_id BIGINT NOT NULL,
            email VARCHAR(200),
            PRIMARY KEY (__id),
            CONSTRAINT fk_email_type_id FOREIGN KEY (__type_id)
              REFERENCES contact_book."types" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT,
            CONSTRAINT fk_contact_id FOREIGN KEY (__contact_id)
              REFERENCES contact_book."contacts" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT
        )';

        /**
         * ТАБЛИЦЫ СВЯЗЕЙ MANY TO MANY
         * привязка контактов к контрактам
         */
        $sql['partners.contracts_to_contacts'] = 'CREATE TABLE partners."contracts_to_contacts" (
            __contract_id BIGINT NOT NULL,
            __contact_id BIGINT NOT NULL,
            PRIMARY KEY (__contract_id, __contact_id),
            CONSTRAINT fk_contract_id FOREIGN KEY (__contract_id)
              REFERENCES partners."contracts" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT,
            CONSTRAINT fk_contact_id FOREIGN KEY (__contact_id)
              REFERENCES contact_book."contacts" (__id)
              ON UPDATE CASCADE
              ON DELETE RESTRICT
        )';

        /**
         * привязка pstn номеров к контрактам
         */
        $sql['telephony.pstnNumbers_to_contracts'] = 'CREATE TABLE telephony."pstnNumbers_to_contracts" (
          __pstn_number_id BIGINT NOT NULL,
          __contract_id BIGINT NOT NULL,
          PRIMARY KEY (__pstn_number_id, __contract_id),
          CONSTRAINT fk_pstn_number_id FOREIGN KEY (__pstn_number_id)
            REFERENCES telephony."pstnNumbers" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT,
          CONSTRAINT fk_contract_id FOREIGN KEY (__contract_id)
            REFERENCES partners."contracts" (__id)
            ON UPDATE CASCADE
            ON DELETE RESTRICT
        )';


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

        foreach ($schemas as $schema) {
            $sqlCreateSchema = 'CREATE SCHEMA IF NOT EXISTS ' . $schema;
            if (true === $this->db->execute($sqlCreateSchema)) {
                echo 'Schema ' . $schema . ' created' . "\n";
            }
        }

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Table ' . $key . ' is created' . "\n";
            }
        }

        $query = 'INSERT INTO company."officeStatuses" (title) VALUES (\'open\')';
        if (true === $this->db->execute($query)) {
            echo 'In table company."officeStatuses" inserted 1 record' . "\n";
        }

        /**
         * Create DB for phpUnit tests
         */
        $this->setDb('phpUnitTest');
        foreach ($schemas as $schema) {
            $sqlCreateSchema = 'CREATE SCHEMA IF NOT EXISTS ' . $schema;
            if (true === $this->db->execute($sqlCreateSchema)) {
                echo 'Schema ' . $schema . ' created' . "\n";
            }
        }

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Table ' . $key . ' is created' . "\n";
            }
        }

        $query = 'INSERT INTO company."officeStatuses" (title) VALUES (\'open\')';
        if (true === $this->db->execute($query)) {
            echo 'In table company."officeStatuses" inserted 1 record' . "\n";
        }

    }

    public function down()
    {
        $schemas = [
            'geolocation',
            'company',
            'telephony',
            'equipment',
            'partners',
            'contact_book'
        ];

        /**
         * Удаляем все схемы каскадно с таблицами
         */
        foreach ($schemas as $schema) {
            $sqlDropSchema = 'DROP SCHEMA IF EXISTS ' . $schema . ' CASCADE';
            if (true === $this->db->execute($sqlDropSchema)) {
                echo 'schema ' . $schema . ' with all tables is deleted' . "\n";
            }
        }

        $this->setDb('phpUnitTest');
        foreach ($schemas as $schema) {
            $sqlDropSchema = 'DROP SCHEMA IF EXISTS ' . $schema . ' CASCADE';
            if (true === $this->db->execute($sqlDropSchema)) {
                echo 'schema ' . $schema . ' with all tables is deleted' . "\n";
            }
        }

    }

}