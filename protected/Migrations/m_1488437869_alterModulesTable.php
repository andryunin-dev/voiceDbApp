<?php

namespace App\Migrations;

use T4\Orm\Migration;

class m_1488437869_alterModulesTable
    extends Migration
{

    public function up()
    {
        $sqlRenamePartNumber = 'ALTER TABLE equipment.modules RENAME COLUMN "partNumber" TO title';
        $sqlRenameComment = 'ALTER TABLE equipment.modules RENAME COLUMN comment TO description';
        if (true === $this->db->execute($sqlRenamePartNumber)) {
            echo 'column "PartNumber" renamed to "title"' . "\n";
        }
        if (true === $this->db->execute($sqlRenameComment)) {
            echo 'column "comment" renamed to "description"' . "\n";
        }

        $this->setDb('phpUnitTest');
        if (true === $this->db->execute($sqlRenamePartNumber)) {
            echo 'column "PartNumber" renamed to "title" in testDB' . "\n";
        }
        if (true === $this->db->execute($sqlRenameComment)) {
        echo 'column "comment" renamed to "description" in testDB' . "\n";
        }

    }

    public function down()
    {
        $sqlRenameTitle = 'ALTER TABLE equipment.modules RENAME COLUMN title TO "partNumber"';
        $sqlRenameDescription = 'ALTER TABLE equipment.modules RENAME COLUMN description TO comment';
        if (true === $this->db->execute($sqlRenameTitle)) {
            echo 'column "title" renamed to "PartNumber"' . "\n";
        }
        if (true === $this->db->execute($sqlRenameDescription)) {
        echo 'column "description" renamed to "comment"' . "\n";
        }

        $this->setDb('phpUnitTest');
        if (true === $this->db->execute($sqlRenameTitle)) {
            echo 'column "title" renamed to "PartNumber" in testDB' . "\n";
        }
        if (true === $this->db->execute($sqlRenameDescription)) {
            echo 'column "description" renamed to "comment" in testDB' . "\n";
        }

    }
    
}