<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1506947365_changeTypeToCitext
    extends Migration
{

    public function up()
    {
        $sql = [
            'ALTER TABLE company.offices ALTER COLUMN title TYPE citext',
            'ALTER TABLE company.offices ALTER COLUMN comment TYPE citext',
            'ALTER TABLE company."officeStatuses" ALTER COLUMN title TYPE citext',
            'ALTER TABLE company."officeStatuses" ALTER COLUMN title TYPE citext',

            'ALTER TABLE contact_book.contacts ALTER COLUMN contact TYPE citext',
            'ALTER TABLE contact_book.contacts ALTER COLUMN extension TYPE citext',
            'ALTER TABLE contact_book.contacts ALTER COLUMN comment TYPE citext',
            'ALTER TABLE contact_book."contactTypes" ALTER COLUMN type TYPE citext',
            'ALTER TABLE contact_book.persons ALTER COLUMN name TYPE citext',
            'ALTER TABLE contact_book.persons ALTER COLUMN position TYPE citext',
            'ALTER TABLE contact_book.persons ALTER COLUMN comment TYPE citext',

            'ALTER TABLE equipment.appliances ALTER COLUMN comment TYPE citext',
            'ALTER TABLE equipment."applianceTypes" ALTER COLUMN type TYPE citext',
            'ALTER TABLE equipment.clusters ALTER COLUMN title TYPE citext',
            'ALTER TABLE equipment.clusters ALTER COLUMN comment TYPE citext',
            'ALTER TABLE equipment."dataPorts" ALTER COLUMN comment TYPE citext',
            'ALTER TABLE equipment."dataPortTypes" ALTER COLUMN type TYPE citext',
            'ALTER TABLE equipment."moduleItems" ALTER COLUMN "serialNumber" TYPE citext',
            'ALTER TABLE equipment."moduleItems" ALTER COLUMN "inventoryNumber" TYPE citext',
            'ALTER TABLE equipment."moduleItems" ALTER COLUMN "comment" TYPE citext',
            'ALTER TABLE equipment.modules ALTER COLUMN title TYPE citext',
            'ALTER TABLE equipment.modules ALTER COLUMN description TYPE citext',

            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN name TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN model TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN status TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN description TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN css TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "devicePool" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "alertingName" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN partition TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN timezone TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "domainName" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager1" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager2" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager3" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager4" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "userLocale" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "cdpNeighborDeviceId" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "cdpNeighborPort" TYPE citext',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "publisherIp" TYPE citext',

            'ALTER TABLE equipment."platformItems" ALTER COLUMN "serialNumber" TYPE citext',
            'ALTER TABLE equipment."platformItems" ALTER COLUMN "inventoryNumber" TYPE citext',
            'ALTER TABLE equipment."platformItems" ALTER COLUMN version TYPE citext',
            'ALTER TABLE equipment."platformItems" ALTER COLUMN comment TYPE citext',
            'ALTER TABLE equipment.platforms ALTER COLUMN title TYPE citext',
            'ALTER TABLE equipment.software ALTER COLUMN title TYPE citext',
            'ALTER TABLE equipment."softwareItems" ALTER COLUMN version TYPE citext',
            'ALTER TABLE equipment."softwareItems" ALTER COLUMN comment TYPE citext',
            'ALTER TABLE equipment.vendors ALTER COLUMN title TYPE citext',
            'ALTER TABLE equipment."voicePorts" ALTER COLUMN comment TYPE citext',
            'ALTER TABLE equipment."voicePortTypes" ALTER COLUMN type TYPE citext',

            'ALTER TABLE geolocation.addresses ALTER COLUMN address TYPE citext',
            'ALTER TABLE geolocation.cities ALTER COLUMN title TYPE citext',
            'ALTER TABLE geolocation.cities ALTER COLUMN "diallingCode" TYPE citext',
            'ALTER TABLE geolocation.regions ALTER COLUMN title TYPE citext',

            'ALTER TABLE network.networks ALTER COLUMN comment TYPE citext',
            'ALTER TABLE network.vlans ALTER COLUMN name TYPE citext',
            'ALTER TABLE network.vlans ALTER COLUMN comment TYPE citext',
            'ALTER TABLE network.vrfs ALTER COLUMN name TYPE citext',
            'ALTER TABLE network.vrfs ALTER COLUMN rd TYPE citext',
            'ALTER TABLE network.vrfs ALTER COLUMN comment TYPE citext',

            'ALTER TABLE partners.contracts ALTER COLUMN number TYPE citext',
            'ALTER TABLE partners.contracts ALTER COLUMN "pathToScan" TYPE citext',
            'ALTER TABLE partners."contractTypes" ALTER COLUMN title TYPE citext',

            'ALTER TABLE partners.offices ALTER COLUMN comment TYPE citext',

            'ALTER TABLE partners.organisations ALTER COLUMN title TYPE citext',

            'ALTER TABLE telephony."pstnNumbers" ALTER COLUMN number TYPE citext',
            'ALTER TABLE telephony."pstnNumbers" ALTER COLUMN comment TYPE citext'
        ];
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' OK' . PHP_EOL;
            }
        }

    }

    public function down()
    {
        $sql = [
            'ALTER TABLE company.offices ALTER COLUMN title TYPE VARCHAR(255)',
            'ALTER TABLE company.offices ALTER COLUMN comment TYPE text',
            'ALTER TABLE company."officeStatuses" ALTER COLUMN title TYPE VARCHAR(50)',

            'ALTER TABLE contact_book.contacts ALTER COLUMN contact TYPE VARCHAR(255)',
            'ALTER TABLE contact_book.contacts ALTER COLUMN extension TYPE VARCHAR(255)',
            'ALTER TABLE contact_book.contacts ALTER COLUMN comment TYPE text',
            'ALTER TABLE contact_book."contactTypes" ALTER COLUMN type TYPE VARCHAR(255)',
            'ALTER TABLE contact_book.persons ALTER COLUMN name TYPE VARCHAR(255)',
            'ALTER TABLE contact_book.persons ALTER COLUMN position TYPE VARCHAR(255)',
            'ALTER TABLE contact_book.persons ALTER COLUMN comment TYPE text',

            'ALTER TABLE equipment.appliances ALTER COLUMN comment TYPE text',
            'ALTER TABLE equipment."applianceTypes" ALTER COLUMN type TYPE VARCHAR(255)',
            'ALTER TABLE equipment.clusters ALTER COLUMN title TYPE VARCHAR(255)',
            'ALTER TABLE equipment.clusters ALTER COLUMN comment TYPE text',
            'ALTER TABLE equipment."dataPorts" ALTER COLUMN comment TYPE text',
            'ALTER TABLE equipment."dataPortTypes" ALTER COLUMN type TYPE VARCHAR(255)',
            'ALTER TABLE equipment."moduleItems" ALTER COLUMN "serialNumber" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."moduleItems" ALTER COLUMN "inventoryNumber" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."moduleItems" ALTER COLUMN "comment" TYPE text',
            'ALTER TABLE equipment.modules ALTER COLUMN title TYPE VARCHAR(255)',
            'ALTER TABLE equipment.modules ALTER COLUMN description TYPE text',

            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN name TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN model TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN status TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN description TYPE text',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN css TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "devicePool" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "alertingName" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN partition TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN timezone TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "domainName" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager1" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager2" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager3" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "callManager4" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "userLocale" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "cdpNeighborDeviceId" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "cdpNeighborPort" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."phoneInfo" ALTER COLUMN "publisherIp" TYPE VARCHAR(255)',

            'ALTER TABLE equipment."platformItems" ALTER COLUMN "serialNumber" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."platformItems" ALTER COLUMN "inventoryNumber" TYPE VARCHAR(255)',
            'ALTER TABLE equipment."platformItems" ALTER COLUMN version TYPE VARCHAR(255)',
            'ALTER TABLE equipment."platformItems" ALTER COLUMN comment TYPE text',
            'ALTER TABLE equipment.platforms ALTER COLUMN title TYPE VARCHAR(255)',
            'ALTER TABLE equipment.software ALTER COLUMN title TYPE VARCHAR(255)',
            'ALTER TABLE equipment."softwareItems" ALTER COLUMN version TYPE VARCHAR(255)',
            'ALTER TABLE equipment."softwareItems" ALTER COLUMN comment TYPE text',
            'ALTER TABLE equipment.vendors ALTER COLUMN title TYPE VARCHAR(255)',
            'ALTER TABLE equipment."voicePorts" ALTER COLUMN comment TYPE text',
            'ALTER TABLE equipment."voicePortTypes" ALTER COLUMN type TYPE VARCHAR(255)',

            'ALTER TABLE geolocation.addresses ALTER COLUMN address TYPE text',
            'ALTER TABLE geolocation.cities ALTER COLUMN title TYPE VARCHAR(255)',
            'ALTER TABLE geolocation.cities ALTER COLUMN "diallingCode" TYPE VARCHAR(255)',
            'ALTER TABLE geolocation.regions ALTER COLUMN title TYPE VARCHAR(255)',

            'ALTER TABLE network.networks ALTER COLUMN comment TYPE text',
            'ALTER TABLE network.vlans ALTER COLUMN name TYPE VARCHAR(255)',
            'ALTER TABLE network.vlans ALTER COLUMN comment TYPE text',
            'ALTER TABLE network.vrfs ALTER COLUMN name TYPE VARCHAR(255)',
            'ALTER TABLE network.vrfs ALTER COLUMN rd TYPE VARCHAR(255)',
            'ALTER TABLE network.vrfs ALTER COLUMN comment TYPE text',

            'ALTER TABLE partners.contracts ALTER COLUMN number TYPE VARCHAR(255)',
            'ALTER TABLE partners.contracts ALTER COLUMN "pathToScan" TYPE VARCHAR(1024)',
            'ALTER TABLE partners."contractTypes" ALTER COLUMN title TYPE VARCHAR(255)',

            'ALTER TABLE partners.offices ALTER COLUMN comment TYPE text',

            'ALTER TABLE partners.organisations ALTER COLUMN title TYPE VARCHAR(255)',

            'ALTER TABLE telephony."pstnNumbers" ALTER COLUMN number TYPE VARCHAR(255)',
            'ALTER TABLE telephony."pstnNumbers" ALTER COLUMN comment TYPE text'
        ];
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' OK' . PHP_EOL;
            }
        }
        // For test DB
        $this->setDb('phpUnitTest');
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Test DB: ' . $key . ' OK' . PHP_EOL;
            }
        }

    }
    
}