<?php

namespace App\Controllers;

use T4\Dbal\Query;
use T4\Mvc\Controller;

class HdsStats extends Controller
{
    public function actionWeeklyStatsByPrefixes()
    {
        $table = 'view.hds_weekly_stats_by_prefixes';

        // Get Weekly Stats By Prefixes
        $dbh = $this->app->db->default;
        $query = 'SELECT * FROM '. $table;
        $params = [];
        $stmt = $dbh->prepare(new Query($query));
        $weeklyStatsByPrefixes = ($stmt->execute($params) === true) ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        // Define table's columns
        $columns = [];
        if (count($weeklyStatsByPrefixes) > 0) {
            foreach ($weeklyStatsByPrefixes[0] as $column => $val) {
                $columns[] = $column;
            }
        }

        $this->data->rows = $weeklyStatsByPrefixes;
        $this->data->columns = $columns;
    }

    public function actionWeeklyStatsByOfficesOnPrefix559()
    {
        $table = 'view.hds_weekly_stats_by_offices_on_prefix_559';

        // Get Weekly Stats By Prefixes
        $dbh = $this->app->db->default;
        $query = 'SELECT * FROM '. $table;
        $params = [];
        $stmt = $dbh->prepare(new Query($query));
        $weeklyStatsByPrefixes = ($stmt->execute($params) === true) ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        // Define table's columns
        $columns = [];
        if (count($weeklyStatsByPrefixes) > 0) {
            foreach ($weeklyStatsByPrefixes[0] as $column => $val) {
                $columns[] = $column;
            }
        }

        $this->data->rows = $weeklyStatsByPrefixes;
        $this->data->columns = $columns;
    }

    public function actionWeeklyStatsByOfficesOnPrefix558()
    {
        $table = 'view.hds_weekly_stats_by_offices_on_prefix_558';

        // Get Weekly Stats By Prefixes
        $dbh = $this->app->db->default;
        $query = 'SELECT * FROM '. $table;
        $params = [];
        $stmt = $dbh->prepare(new Query($query));
        $weeklyStatsByPrefixes = ($stmt->execute($params) === true) ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        // Define table's columns
        $columns = [];
        if (count($weeklyStatsByPrefixes) > 0) {
            foreach ($weeklyStatsByPrefixes[0] as $column => $val) {
                $columns[] = $column;
            }
        }

        $this->data->rows = $weeklyStatsByPrefixes;
        $this->data->columns = $columns;
    }
}
