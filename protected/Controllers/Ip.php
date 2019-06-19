<?php
namespace App\Controllers;

use App\Models\DataPort;
use T4\Core\Std;
use T4\Http\Request;
use T4\Mvc\Controller;

class Ip extends Controller
{
    private const SQL = [
        'search_ip' => 'SELECT "ipAddress" AS ip FROM equipment."dataPorts" ORDER BY "ipAddress" ASC LIMIT :limit OFFSET :offset',
    ];

    public function actionDefault()
    {
        $request = new Request();
        if (!mb_ereg_match(".+\.json$", $request->url->path)) {
            echo json_encode(['error' => 'Request has wrong format']);
            die;
        }
        try {
            $requestType = '';
            if (0 != $request->get->count()) {
                $requestType = 'get';
            }
            if (0 != $request->post->count()) {
                $requestType = 'post';
            }
            switch ($requestType) {
                case 'get':
                    $this->data->ip = $this->get($request->get);
                    break;
                case 'post':
                    $this->post($request->post);
                    break;
                default:
                    $this->data->ip = $this->get(new Std(['limit' => null, 'page' => 0]));
            }
        } catch (\Throwable $e) {
            $this->data->error = $e->getMessage();
        }
    }

    /**
     * @param Std $args
     * @return array
     * @throws \Exception
     */
    private function get(Std $args): array
    {
        if (!is_null($args->limit) && !is_numeric($args->limit)) {
            throw new \Exception('Request has wrong format');
        }
        if (is_null($args->page) || !is_numeric($args->page)) {
            throw new \Exception('Request has wrong format');
        }
        if ($args->limit < 0) {
            throw new \Exception('LIMIT must not be negative');
        }
        if ($args->page < 0) {
            throw new \Exception('PAGE must not be negative');
        }
        $params = [
            'limit' => $args->limit,
            'offset' => $args->limit * $args->page,
        ];
        $dataPorts = DataPort::getDbConnection()->query(self::SQL['search_ip'], $params)->fetchAll(\PDO::FETCH_ASSOC);
        $ip = [];
        foreach ($dataPorts as $dataPort) {
            $ip[] = $dataPort['ip'];
        }
        return $ip;
    }

    private function post()
    {

    }
}
