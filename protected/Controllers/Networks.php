<?php

namespace App\Controllers;


use App\Components\Sql\SqlSearcher;
use App\Models\DataPort;
use App\Models\Network;
use App\ViewModels\ApiView_IpSearch;
use App\ViewModels\ApiView_Networks;
use App\ViewModels\DevGeoNetMat;
use App\ViewModels\NetworksView;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Networks extends Controller
{
    public function actionIpam() {
    }
    public function actionFilteredSearch() {
        
        // respond to preflights
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = json_decode(file_get_contents('php://input'));
        $searcher = new SqlSearcher($filters);
        $expression = $searcher->expression;
        $params = $searcher->parameters;
        
        $query = 'SELECT *, network.ip_path(t1.ip, t1.rec_type) FROM api_view.ip_search t1
                    WHERE ' . $expression . '
                    ORDER BY ip';
    
        $con = ApiView_Networks::getDbConnection();
        $stm = $con->query($query, $params);
        $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
        
        
//        $dbh = $this->app->db->default;
//        $stmt = $dbh->prepare(new Query($query));
//        $result = ($stmt->execute($params) === true) ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
        $this->data->searchResult = $result;
    }

    /**
     * URL: voice.rs.ru/reduxTest/rootElements.json
     * return object like {rootElementsId: {netsId: "nets id as a string", hostsId: "hosts id as a string"}}
     */
    public function actionRootElements()
    {
        $connection = Network::getDbConnection();
        $rootSql = 'SELECT * FROM root_ids()';
        $this->data->rootElementsIds = $connection->query($rootSql)->fetch(\PDO::FETCH_ASSOC);

    }

    /**
     * @param string $netsIds nets ids
     * @param string $sortDirection
     * return json object like:
     * {data: [
     * {__id, address, comment, net_children, host_children},
     * ...
     * ]}
     */
    public function actionNetElementsByIds($netsIds = '')
    {
        /**
         * for test only!!!
         */
//        $netsIds = ' 4039,2995 ,3274,3146,4093,4094,3334,3275,26127';

        $netsIds = array_map('trim', explode(',', $netsIds));
        $connection = NetworksView::getDbConnection();
        $table = NetworksView::getTableName();
        $idField = '"' . NetworksView::PK . '"';
        $selectedFields = [
            $idField,
            'address',
            'netmask',
            'comment',
            '"vrfId"',
            '"vrfName"',
            '"vrfRd"',
            'network_locations(' . $idField . ') as "netLocations"',
            'net_children(' . $idField . ')',
            'host_children(' . $idField . ')',
        ];
        $joinSelect = 'SELECT '. array_pop($netsIds) .' AS src_id';
        foreach ($netsIds as $id) {
            $joinSelect .= "\n" . 'UNION SELECT ' . $id;
        }
        $sql = 'SELECT ' . implode(', ', $selectedFields) . "\n" .
            'FROM ' . $table . "\n" .
                'JOIN (' . $joinSelect .
                ') as subtable ON subtable.src_id = ' . $idField;

        try {
            $this->data->networksData = $res = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->data->error = $e->getMessage();
        }
    }


    public function actionHostElementsByIds($hostsIds='')
    {
        $hostsIds = array_map('trim', explode(',', $hostsIds));
        $connection = DataPort::getDbConnection();
        $table = 'equipment."dataPorts"';
        $orderedField = '"ipAddress"';
        $idField = DataPort::PK;
        $selectedFields = [
            $idField,
            '"ipAddress"',
            'details->>\'description\' as comment',
            '"macAddress"'
        ];
        $joinSelect = 'SELECT '. array_pop($hostsIds) .' AS src_id';
        foreach ($hostsIds as $id) {
            $joinSelect .= "\n" . 'UNION SELECT ' . $id;
        }
        $sql = 'SELECT ' . implode(', ', $selectedFields) . "\n" .
            'FROM ' . $table . "\n" .
            'JOIN (' . $joinSelect .
            ') as subtable ON subtable.src_id = ' . $idField .
            ' ORDER BY ' . $orderedField;
        try {
            $this->data->hostsData = $res = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->data->error = $e->getMessage();
        }
    }
}