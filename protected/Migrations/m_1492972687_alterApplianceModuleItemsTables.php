<?php

namespace App\Migrations;

use T4\Core\Exception;
use T4\Orm\Migration;

/**
 * Class m_1492972687_alterApplianceModuleItemsTables
 * @package App\Migrations
 *
 * add location column to ModuleItems and drop not null on moduleItems->__appliance_id
 */
class m_1492972687_alterApplianceModuleItemsTables
    extends Migration
{

    public function up()
    {
        $this->setDb('phpUnitTest');
        if ($this->db->query('SELECT COUNT(1) FROM equipment."moduleItems"')->fetchScalar() > 0) {
            throw new Exception('Before apply this migration you need delete all records from equipment."moduleItems" table in test DB.' . "\n");
        }
        $this->setDb('default');
        if ($this->db->query('SELECT COUNT(1) FROM equipment."moduleItems"')->fetchScalar() > 0) {
            throw new Exception('Before apply this migration you need delete all records from equipment."moduleItems" table in main DB.' . "\n");
        }

        $sqlAddColumn = 'ALTER TABLE equipment."moduleItems" ADD COLUMN "__location_id" BIGINT';
        $sqlAddConstraint = 'ALTER TABLE equipment."moduleItems" ADD 
          CONSTRAINT fk_location_id FOREIGN KEY (__location_id)
          REFERENCES company.offices (__id)
          ON UPDATE CASCADE
          ON DELETE RESTRICT';
        $sqlDropNotNull = 'ALTER TABLE equipment."moduleItems" ALTER COLUMN __appliance_id DROP NOT NULL ';
        echo 'main DB: adding column...' . "\n";
        if (true === $this->db->execute($sqlAddColumn)) {
            echo 'Done!' . "\n";
        }

        echo 'main DB: adding constraint...' . "\n";
        if (true === $this->db->execute($sqlAddConstraint)) {
            echo 'Done!' . "\n";
        }

        echo 'main DB: drop NOT NULL constraint...' . "\n";
        if (true === $this->db->execute($sqlDropNotNull)) {
            echo 'Done!' . "\n";
        }
//============================ for test DB =========================
        $this->setDb('phpUnitTest');

        echo 'test DB: adding column...' . "\n";
        if (true === $this->db->execute($sqlAddColumn)) {
            echo 'Done!' . "\n";
        }

        echo 'test DB: adding constraint...' . "\n";
        if (true === $this->db->execute($sqlAddConstraint)) {
            echo 'Done!' . "\n";
        }

        echo 'test DB: drop NOT NULL constraint...' . "\n";
        if (true === $this->db->execute($sqlDropNotNull)) {
            echo 'Done!' . "\n";
        }
    }

    public function down()
    {
        $this->setDb('phpUnitTest');
        if ($this->db->query('SELECT COUNT(1) FROM equipment."moduleItems"')->fetchScalar() > 0) {
            throw new Exception('Before apply this migration you need delete all records from equipment."moduleItems" table in test DB.' . "\n");
        }
        $this->setDb('default');
        if ($this->db->query('SELECT COUNT(1) FROM equipment."moduleItems"')->fetchScalar() > 0) {
            throw new Exception('Before apply this migration you need delete all records from equipment."moduleItems" table in main DB.' . "\n");
        }

        $sqlDropColumn = 'ALTER TABLE equipment."moduleItems" DROP COLUMN "__location_id"';
        $sqlDropConstraint = 'ALTER TABLE equipment."moduleItems" DROP 
          CONSTRAINT fk_location_id';
        $sqlSetNotNull = 'ALTER TABLE equipment."moduleItems" ALTER COLUMN __appliance_id SET NOT NULL ';


        echo 'main DB: drop constraint...' . "\n";
        if (true === $this->db->execute($sqlDropConstraint)) {
            echo 'Done!' . "\n";
        }

        echo 'main DB: drop column...' . "\n";
        if (true === $this->db->execute($sqlDropColumn)) {
            echo 'Done!' . "\n";
        }

        echo 'main DB: set NOT NULL constraint...' . "\n";
        if (true === $this->db->execute($sqlSetNotNull)) {
            echo 'Done!' . "\n";
        }
//============================ for test DB =========================
        $this->setDb('phpUnitTest');

        echo 'test DB: drop constraint...' . "\n";
        if (true === $this->db->execute($sqlDropConstraint)) {
            echo 'Done!' . "\n";
        }

        echo 'test DB: drop column...' . "\n";
        if (true === $this->db->execute($sqlDropColumn)) {
            echo 'Done!' . "\n";
        }

        echo 'test DB: set NOT NULL constraint...' . "\n";
        if (true === $this->db->execute($sqlSetNotNull)) {
            echo 'Done!' . "\n";
        }
    }
}