<?php


namespace App\Controllers;


use App\Components\Fixtures\ObjItem;
use App\Models\DataPort;
use App\Models\Network;
use T4\Core\Collection;
use T4\Core\Config;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class ReduxTest extends Controller
{
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
    public function actionNetElementsByIds($netsIds = '', $sortDirection = 'asc')
    {
        /**
         * for test only!!!
         */
//        $netsIds = ' 4039,2995 ,3274,3146,4093,4094,3334,3275,26127';

        $netsIds = array_map('trim', explode(',', $netsIds));
        $connection = Network::getDbConnection();
        $table = Network::getTableName();
        $address = 'address';
        $idField = Network::PK;
        $selectedFields = [$idField, $address, 'comment'];
        $joinSelect = 'SELECT '. array_pop($netsIds) .' AS src_id';
        foreach ($netsIds as $id) {
            $joinSelect .= "\n" . 'UNION SELECT ' . $id;
        }
        $sql = 'SELECT ' . implode(', ', $selectedFields) . ', ' . 'net_children(' . $idField . ') as net_children, host_children(' . $idField . ') as host_children' . "\n" .
            'FROM ' . $table . "\n" .
            'JOIN (' . $joinSelect .
            ') as subtable ON subtable.src_id = ' . $idField .
            ' ORDER BY ' . $address . ' ' . $sortDirection;

        try {
            $this->data->networksData = $res = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->data->error = $e->getMessage();
        }
    }


    public function actionHostElementsByIds($hostsIds='', $sortDirection = 'asc')
    {
        $hostsIds = array_map('trim', explode(',', $hostsIds));
        $connection = DataPort::getDbConnection();
        $table = 'equipment."dataPorts"';
        $orderedField = '"ipAddress"';
        $idField = DataPort::PK;
        $selectedFields = [$idField, '"ipAddress"', 'comment', '"macAddress"'];
        $joinSelect = 'SELECT '. array_pop($hostsIds) .' AS src_id';
        foreach ($hostsIds as $id) {
            $joinSelect .= "\n" . 'UNION SELECT ' . $id;
        }
        $sql = 'SELECT ' . implode(', ', $selectedFields) . "\n" .
            'FROM ' . $table . "\n" .
            'JOIN (' . $joinSelect .
            ') as subtable ON subtable.src_id = ' . $idField .
            ' ORDER BY ' . $orderedField . ' ' . $sortDirection;
        try {
            $this->data->hostsData = $res = $connection->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            $this->data->error = $e->getMessage();
        }
    }
//    ======================================= NOT READY TO USE =============
    public function actionRootElements2()
    {
        $connection = Network::getDbConnection();
        $rootSql = 'SELECT * FROM root_ids_array()';
        $this->data->ids = $connection->query($rootSql)->fetch(\PDO::FETCH_ASSOC);
    }

    public function getRootElements()
    {
        $connection = Network::getDbConnection();
        $rootSql = 'SELECT * FROM root_ids_array()';
        $res = $connection->query($rootSql)->fetch(\PDO::FETCH_ASSOC);
        return $res;
    }










    const NUMBER_OF_OBJECTS = 5000;

    protected  $idx = 0;
    protected $count = 0;
    protected $result;
    protected $objects;
    protected $rootElements;
    protected $path = ROOT_PATH_PROTECTED . DS . 'Fixtures' . DS . 'fixtures.txt';
    protected $lastParentIdx = 0;
    protected $lastChildrenIdx = 0;

    public function generateObj($count)
    {
        $this->objects = new Collection();

        $innerCounter = 0;
        while ($innerCounter < $count) {
            $item = (new ObjItem());
            $item->id = strval($this->idx);
            $item->ip = strval($this->idx);

            $innerCounter += 1;
            $this->idx += 1;
            $this->objects->append($item);
        }
        return $this->objects;
    }

    public function fillWithChildren($startParentIdx, $parentsAmount, $startChildrenIdx, $childrenAmount, $lvl) {
        $parentCounter = 0;
        $childrenCounter = 0;
        $this->lastChildrenIdx = $startChildrenIdx;
        $this->lastParentIdx = $startParentIdx;
        $objArray = $this->objects->toArray();

        while ($parentCounter < $parentsAmount && $this->lastChildrenIdx < self::NUMBER_OF_OBJECTS) {
            $obj = $objArray[$this->lastParentIdx];
            while($childrenCounter < $childrenAmount && $this->lastChildrenIdx < self::NUMBER_OF_OBJECTS) {
                $obj->children->append(strval($this->lastChildrenIdx));
                $this->lastChildrenIdx += 1;
                $childrenCounter += 1;
            }
            $obj->ip = '0' . $lvl . '.' . $obj->ip;
            $childrenCounter = 0;
            $parentCounter +=1;
            $this->lastParentIdx +=1;
        }
        $this->objects = new Collection($objArray);
        if ($this->lastChildrenIdx === self::NUMBER_OF_OBJECTS) {
            echo 'Have reached NUMBER_OF_OBJECTS';
        }
    }
    
    public function fillRootElements($startIdx, $numberOfElements)
    {
        $counter = 0;
        $this->rootElements = new Collection();
        while ($counter < $numberOfElements) {
            $this->rootElements->append($startIdx);
            $counter += 1;
            $startIdx += 1;
        }
    }

    public function actionGenerateFixtures()
    {
        $this->result = new Config();
        $this->result->setPath($this->path);
        $acc = new Std();

        $this->generateObj(self::NUMBER_OF_OBJECTS);
        $this->fillRootElements(0,5);
        $this->fillWithChildren($this->lastParentIdx, 5, $this->lastParentIdx + 5, 5, 1);
        $this->fillWithChildren($this->lastParentIdx, 25, $this->lastChildrenIdx, 5, 2);
        $this->fillWithChildren($this->lastParentIdx, 125, $this->lastChildrenIdx, 5, 3);
        $this->fillWithChildren($this->lastParentIdx, 725, $this->lastChildrenIdx, 5, 4);
        $this->fillWithChildren($this->lastParentIdx, 3625, $this->lastChildrenIdx, 5, 5);

        $acc->objects = $this->objects;
        $acc->rootElements = $this->rootElements;
        $this->result->fromArray($acc->toArrayRecursive());

        $this->result->save();
        var_dump($this->result->objects->count());die;

    }

    public function actionElementsById($id = '')
    {
        $ids = explode(',', $id);
        $fromFile = new Config($this->path);
        $objects = $fromFile->objects->toArray();
        $res = new Std();
        foreach ($ids as $id) {
            if($id === '') {
                continue;
            }
            $response = new Std();
            if (array_key_exists(intval($id), $objects)) {
                $response = new Std($objects[intval($id)]);
            } else {
                $response->error = 'Object not found';
            }
            $res->$id = $response;
        }
        $this->data = $res;
    }


    public function actionElementsById2($netIds = [], $hostIds = [], $sortDirection = 'asc', $delim = ',')
    {
        $connection = Network::getDbConnection();

        $netIds = [
            4039,2995,3274,3146,4093,4094,3334,3275,26127
        ];

//        $rootIds = $this->getRootElements();

        $delim = '\'' . $delim . '\'';

        //$netIds = explode($netIds, ',');
        //$hostIds = explode($hostIds, ',');

        $networksTable = Network::getTableName();
        $netId = Network::PK;
        $netAddress = 'address';
        $netFields = [$netId, $netAddress];

        $hostTable = 'equipment."dataPorts"';
        $hostId = DataPort::PK;
        $hostAddress = '"ipAddress"';
        $hostMaskLen = 'masklen';
        $hostFileds = [];

        $joinExpression = 'SELECT '. array_pop($netIds) .' AS src_id';
        foreach ($netIds as $id) {
            $joinExpression .= "\n" . 'UNION SELECT ' . $id;
        }


        $netSql = '
        SELECT '. implode($netFields, ', ') .',
        (
              SELECT string_agg(t_net.'. $netId .'::text, '. $delim .') FROM (
                WITH all_children AS (
                    SELECT '. $netId .', '. $netAddress .' FROM '. $networksTable .' WHERE
                      '. $netAddress .' << t0.'. $netAddress .'
                )
                SELECT '. $netId .', '. $netAddress .' FROM all_children AS t1 WHERE
                  NOT EXISTS(SELECT '. $netAddress .' FROM all_children AS t2 WHERE t2.'. $netAddress .' >> t1.'. $netAddress .')
                ORDER BY '. $netAddress .' '. $sortDirection .'
              ) AS t_net
        ) AS net_children,
        (
          SELECT string_agg(host_t.'. $hostId .'::text, '. $delim .') 
          FROM (
          
            SELECT '. $hostId .' FROM
             (
               SELECT * FROM '. $hostTable .'
               WHERE t0.'. $netAddress .' >>= (abbrev('. $hostAddress .') || \'/\' || coalesce('. $hostMaskLen .', 32))::inet
             ) AS t
           WHERE (SELECT max('. $netAddress .') FROM '. $networksTable .' WHERE
             address >>= (abbrev('. $hostAddress .') || \'/\' || coalesce('. $hostMaskLen .', 32))::inet AND
             address != (abbrev('. $hostAddress .') || \'/\' || coalesce('. $hostMaskLen .', 32))::inet) = t0.'. $netAddress .'
          ) AS host_t
        ) AS host_children
        FROM '. $networksTable .' AS t0
        INNER JOIN (
        '. $joinExpression .'
        ) as subtable ON subtable.src_id = t0.'. $netId;

//        $this->data->sql = $netSql;
        $this->data->res = $res = $connection->query($netSql)->fetchAll(\PDO::FETCH_ASSOC);
    }
    public function actionElementsById3($netIds = [], $hostIds = [], $sortDirection = 'asc', $delim = ',')
    {
        $connection = Network::getDbConnection();

        $netIds = [
            4039,2995,3274,3146,4093,4094,3334,3275,26127
        ];
//        $netIds = $this->actionRootElements2();
        $rootSql = 'SELECT * FROM root_ids_array()';
        $netIds = $connection->query($rootSql)->fetch(\PDO::FETCH_ASSOC);
//        $netIds2 = '{' . implode(',', $netIds['rootNetId']) .'}';

        $netSql = 'SELECT * FROM children_id(:ids)';
        $params = [':ids' => $netIds['netId']];
        $this->data->res = $res = $connection->query($netSql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }


//    public function actionNetElementsById($nets)
//    {
//        $connection = Network::getDbConnection();
//        $sqlNetData = 'SELECT ' . Network::PK . ' AS id, address AS ip, comment AS title, net_children(__id) AS nets, host_children(__id) AS hosts FROM ' . Network::getTableName() . ' WHERE __id IN ('. $nets .')';
//        $this->data->netData = $connection->query($sqlNetData)->fetchAll(\PDO::FETCH_ASSOC);
//    }

//    public function actionHostElementsById($hosts)
//    {
//        $connection = DataPort::getDbConnection();
//        $driver = DataPort::getDbDriver();
//
//        $sqlHostData = 'SELECT ' . DataPort::PK . ' AS id, "ipAddress" AS ip,  comment AS title FROM ' .  $driver->quoteName(DataPort::getTableName()) . ' WHERE __id IN ('. $hosts .')';
//        $this->data->hostData = $connection->query($sqlHostData)->fetchAll(\PDO::FETCH_ASSOC);
//    }


    public function actionTestApi()
    {
        $sql = 'SELECT * FROM ';
        $query = new Query($sql);
        $res = Network::findAllByQuery($query);
        var_dump($res);
    }

}