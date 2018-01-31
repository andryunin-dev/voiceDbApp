<?php

namespace App\Commands;

use T4\Console\Command;

class UpdateMatViews extends Command
{
    public function actionLotusDbData()
    {
        $sql = 'REFRESH MATERIALIZED VIEW "view"."lotus_db_data"';

        $dbList = [
            'default',
        ];
        foreach ($dbList as $db) {
            $conn = $this->app->db->$db;
            $res = $conn->execute($sql);
        }
    }
}