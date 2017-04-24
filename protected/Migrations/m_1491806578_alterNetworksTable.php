<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1491806578_alterNetworksTable
    extends Migration
{

    public function up()
    {
        $this->db->execute('ALTER TABLE network."networks" DROP CONSTRAINT networks_address_key');
        //for test DB
        $this->setDb('phpUnitTest');
        $this->db->execute('ALTER TABLE network."networks" DROP CONSTRAINT networks_address_key');
    }

    public function down()
    {
        $this->db->execute('ALTER TABLE network."networks" ADD CONSTRAINT networks_address_key UNIQUE (address)');
        //for test DB
        $this->setDb('phpUnitTest');
        $this->db->execute('ALTER TABLE network."networks" ADD CONSTRAINT networks_address_key UNIQUE (address)');
    }
}