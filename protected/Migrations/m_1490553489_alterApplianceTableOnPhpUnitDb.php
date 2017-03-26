<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1490553489_alterApplianceTableOnPhpUnitDb
    extends Migration
{

    public function up()
    {
        $this->setDb('phpUnitTest');

        $sqlDropNotNull = 'ALTER TABLE equipment.appliances ALTER COLUMN __cluster_id DROP NOT NULL ';
        if (true === $this->db->execute($sqlDropNotNull)) {
            echo 'restriction NOT NUL on "__cluster_id" column "equipment.appliances" table dropped' . "\n";
        }
    }

    public function down()
    {
        $this->setDb('phpUnitTest');

        $sqlSetNotNull = 'ALTER TABLE equipment.appliances ALTER COLUMN __cluster_id SET NOT NULL ';
        if (true === $this->db->execute($sqlSetNotNull)) {
            echo 'set restriction NOT NUL on "__cluster_id column" "equipment.appliances" table' . "\n";
        }
    }
    
}