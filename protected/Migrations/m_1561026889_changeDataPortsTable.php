<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1561026889_changeDataPortsTable
    extends Migration
{

    public function up()
    {
        $sql['change dataPorts table - ADD COLUMNS'] = '
            ALTER TABLE equipment."dataPorts"
              ADD COLUMN "dnsName" citext,
              ADD COLUMN "dnsLastUpdate" TIMESTAMP WITH TIME ZONE 
        ';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['change dataPorts table - DROP COLUMNS'] = '
            ALTER TABLE equipment."dataPorts"
              DROP COLUMN "dnsName",
              DROP COLUMN "dnsLastUpdate"
        ';

        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}
