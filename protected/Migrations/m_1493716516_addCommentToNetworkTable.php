<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1493716516_addCommentToNetworkTable
    extends Migration
{

    public function up()
    {
        $query = 'ALTER TABLE network."networks" ADD COLUMN "comment" TEXT';
        if (true === $this->db->execute($query)) {
            echo 'main DB: added column "comment"' . "\n";
        }

        $this->setDb('phpUnitTest');
        if (true === $this->db->execute($query)) {
            echo 'test DB: added column "comment"' . "\n";
        }
    }

    public function down()
    {
        $query = 'ALTER TABLE network."networks" DROP COLUMN "comment"';
        if (true === $this->db->execute($query)) {
            echo 'main DB: dropped column "comment"' . "\n";
        }

        $this->setDb('phpUnitTest');
        if (true === $this->db->execute($query)) {
            echo 'test DB: droppped column "comment"' . "\n";
        }
    }
    
}