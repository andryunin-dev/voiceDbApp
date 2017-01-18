<?php

namespace App\Migrations;

use T4\Dbal\QueryBuilder;
use T4\Orm\Migration;

class m_1484736766_insertInitialData
    extends Migration
{

    public function up()
    {
        $query = 'INSERT INTO company."officeStatuses" (title) VALUES (\'open\')';

        $this->db->execute($query);

    }

    public function down()
    {
        $query = 'DELETE FROM company."officeStatuses"';
        $this->db->execute($query);
    }
    
}