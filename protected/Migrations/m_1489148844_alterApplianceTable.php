<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1489148844_alterApplianceTable
    extends Migration
{

    public function up()
    {
        $sqlDropNotNull = 'ALTER TABLE equipment.appliances ALTER COLUMN __cluster_id DROP NOT NULL ';
        if (true === $this->db->execute($sqlDropNotNull)) {
            echo 'restriction NOT NUL on \"__cluster_id\" \"column dropped\"' . "\n";
        }
    }

    public function down()
    {
        $sqlSetNotNull = 'ALTER TABLE equipment.appliances ALTER COLUMN __cluster_id SET NOT NULL ';
        if (true === $this->db->execute($sqlSetNotNull)) {
            echo 'set restriction NOT NUL on \"__cluster_id column\" \"equipment.appliances\" table' . "\n";
        }
    }
    
}