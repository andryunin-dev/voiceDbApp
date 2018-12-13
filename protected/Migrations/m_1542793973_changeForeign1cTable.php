<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1542793973_changeForeign1cTable
    extends Migration
{

    public function up()
    {
        $sql['alter table name'] = 'ALTER TABLE storage_1c.foreing_1c RENAME TO foreign_1c';
        $sql['rename constraint'] = 'ALTER TABLE storage_1c.foreign_1c rename constraint foreing_1c_pkey to foreign_1c_pkey';
        $sql['rename sequence'] = 'ALTER SEQUENCE storage_1c.foreing_1c___id_seq RENAME TO foreign_1c___id_seq';
        $sql['add column'] = 'ALTER TABLE storage_1c.foreign_1c
            ADD COLUMN nomenclature_id citext';
        $sql['rights for developers1c 1'] = 'GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON storage_1c.foreign_1c TO developers1c';
        $sql['rights for developers1c 2'] = 'GRANT USAGE, SELECT ON SEQUENCE storage_1c.foreign_1c___id_seq TO developers1c';
    
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }

    public function down()
    {
        $sql['drop column'] = 'ALTER TABLE storage_1c.foreign_1c
            DROP COLUMN nomenclature_id';
        $sql['rename sequence'] = 'ALTER SEQUENCE storage_1c.foreign_1c___id_seq RENAME TO foreing_1c___id_seq';
        $sql['rename constraint'] = 'ALTER TABLE storage_1c.foreign_1c rename constraint foreign_1c_pkey to foreing_1c_pkey';
        $sql['alter table name'] = 'ALTER TABLE storage_1c.foreign_1c RENAME TO foreing_1c';
        $sql['rights for developers1c 1'] = 'GRANT SELECT, INSERT, UPDATE, DELETE, TRUNCATE ON storage_1c.foreing_1c TO developers1c';
        $sql['rights for developers1c 2'] = 'GRANT USAGE, SELECT ON SEQUENCE storage_1c.foreing_1c___id_seq TO developers1c';
        // For main DB
        foreach ($sql as $key => $query) {
            if (true === $this->db->execute($query)) {
                echo 'Main DB: ' . $key . ' - OK' . PHP_EOL;
            }
        }
    }
    
}