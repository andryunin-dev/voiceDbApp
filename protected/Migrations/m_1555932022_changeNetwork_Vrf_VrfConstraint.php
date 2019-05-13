<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1555932022_changeNetwork_Vrf_VrfConstraint
    extends Migration
{
    
    public function up()
    {
        $sql['drop old constrain'] =
            'ALTER TABLE network.vrfs
              DROP CONSTRAINT vrfs_rd_key';
        $sql['create Vrf name constraint'] = '
          ALTER TABLE network.vrfs
            ADD CONSTRAINT vrf_name UNIQUE (name)
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
    public function down()
    {
        $sql['drop Vrf name constraint'] = '
        ALTER TABLE network.vrfs
              DROP CONSTRAINT vrf_name
        ';
        $sql['restore old constraint for vrf rd'] = '
        ALTER TABLE network.vrfs
          ADD CONSTRAINT vrfs_rd_key UNIQUE (rd)
        ';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}