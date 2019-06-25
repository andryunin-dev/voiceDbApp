<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 16.05.2019
 * Time: 17:10
 */

namespace App\Controllers;


use App\Models\Network;
use App\ViewModels\IpamView_Hosts;
use App\ViewModels\IpamView_Networks;
use App\ViewModels\NetworksView;
use T4\Mvc\Controller;

class Ipam extends Controller
{
    const SQL = [
        'searchTemplate' => "
        SELECT * FROM (
              SELECT port_id id, port_ip_cidr ip, 'host' rec_type, usr_ip_path(port_ip_cidr, 'host') ip_path FROM ipam_view.hosts_ports
              WHERE %s
              union
              SELECT net_id id, net_ip ip, 'network' rec_type, usr_ip_path(net_ip, 'network') ip_path FROM ipam_view.nets
              WHERE %s
        ) t1
        ORDER BY ip",
    ];

    public function actionRootElements()
    {
        $connection = Network::getDbConnection();
        $rootSql = 'SELECT * FROM usr_root_ids()';
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
        // respond to preflights
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $netsIds = array_map('trim', explode(',', $netsIds));
        $connection = IpamView_Networks::getDbConnection();
        $table = IpamView_Networks::getTableName();
        //PK = 'net_id'
        $pk = IpamView_Networks::PK;
        $selectedFields = [
            $pk,
            'net_ip',
            'net_mask',
            'net_comment',
            'vrf_id',
            'vrf_name',
            'vrf_rd',
            'vrf_comment',
            'usr_net_children(' . $pk . ') net_children',
            'usr_host_children(' . $pk . ') host_children',
            'usr_network_locations_json(' . $pk . ') net_location',
            'bgp_as'
        ];
        $joinSelect = 'SELECT '. array_pop($netsIds) .' AS src_id';
        foreach ($netsIds as $id) {
            $joinSelect .= "\n" . 'UNION SELECT ' . $id;
        }
        $sql = 'SELECT ' . implode(', ', $selectedFields) . "\n" .
            'FROM ' . $table . "\n" .
            'JOIN (' . $joinSelect .
            ') as subtable ON subtable.src_id = ' . $pk;
        try {
            $this->data->networksData = $res = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->data->error = $e->getMessage();
        }

    }
    public function actionHostElementsByIds($hostsIds='')
    {
        // respond to preflights
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $hostsIds = array_map('trim', explode(',', $hostsIds));
        $connection = IpamView_Hosts::getDbConnection();
        $table = IpamView_Hosts::getTableName();
        $pk = IpamView_Hosts::PK;
        $selectedFields = [
            $pk,
            'port_ip',
            'port_masklen',
            'port_mask',
            'port_ip_cidr',
            'dev_location',
            'port_comment',
            'port_desc',
            'port_name',
            'dev_title',
            'dev_type',
            'dev_hostname',
            'dev_last_update',
            'dev_last_update_ms',
            'dev_age_h',
            'vrf_name',
            'dns'
        ];
        $joinSelect = 'SELECT '. array_pop($hostsIds) .' AS src_id';
        foreach ($hostsIds as $id) {
            $joinSelect .= "\n" . 'UNION SELECT ' . $id;
        }
        $sql = 'SELECT ' . implode(', ', $selectedFields) . "\n" .
            'FROM ' . $table . "\n" .
            'JOIN (' . $joinSelect .
            ') as subtable ON subtable.src_id = ' . $pk;
        try {
            $this->data->hostsData = $res = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->data->error = $e->getMessage();
        }
    }

    public function actionSearch()
    {
        $pk = IpamView_Networks::PK;
        $hostClause = 'concat_ws(\',\', port_ip_cidr, dev_location, port_name, port_desc, dev_title, dev_type, dev_hostname, vrf_name, dns)::citext LIKE :arg';
        $netClause = 'concat_ws(\',\', net_ip, net_comment, vrf_name, vrf_comment, usr_network_locations_string(' . $pk . '))::citext LIKE :arg';
        // respond to preflights
        if($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = json_decode(file_get_contents('php://input'));
        $filter = array_pop($filters);
        if (!empty($filter->value)) {
            $params = [':arg' => '%' . $filter->value . '%'];
            $sql = sprintf(self::SQL['searchTemplate'], $hostClause, $netClause);
            $con = Network::getDbConnection();
            $stm = $con->query($sql, $params);
            $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $result = [];
        }

        $this->data->searchResult = $result;
    }

}