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

    public function actionDevPhoneInfoGeo()
    {
        $sql = 'REFRESH MATERIALIZED VIEW "view"."dev_phone_info_geo_mat"';

        $dbList = [
            'default',
        ];
        foreach ($dbList as $db) {
            $conn = $this->app->db->$db;
            $res = $conn->execute($sql);
        }
    }

    public function actionDevCallsStats()
    {
        $sql = 'REFRESH MATERIALIZED VIEW view.dev_calls_stats';

        $dbList = [
            'default',
        ];
        foreach ($dbList as $db) {
            $conn = $this->app->db->$db;
            $res = $conn->execute($sql);
        }
    }
}
