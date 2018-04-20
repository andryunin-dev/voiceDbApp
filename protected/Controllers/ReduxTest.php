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

    /**
     * @param string $id
     *
     * return objects by id
     */
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
        $netIds = $this->actionRootElements2();

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
        $netIds2 = '{' . implode(',', $netIds['rootNetId']) .'}';

        $netSql = 'SELECT * FROM children_id(:ids)';
        $params = [':ids' => $netIds['rootNetId']];
        $this->data->res = $res = $connection->query($netSql, $params)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * URL: voice.rs.ru/reduxTest/rootElements.json
     */
    public function actionRootElements()
    {
        $connection = Network::getDbConnection();
        $rootSql = 'SELECT * FROM root_ids_string()';
        $this->data->ids = $connection->query($rootSql)->fetch(\PDO::FETCH_ASSOC);
    }

    public function actionRootElements2()
    {
        $networksTable = Network::getTableName();
        $addressColumn = 'address';
        $id = Network::PK;
        $order = 'ASC';

        $connection = Network::getDbConnection();

        $sql = 'SELECT '. $id .' FROM '. $networksTable .' AS net1 WHERE
            NOT EXISTS(SELECT '. $addressColumn .' from '. $networksTable .' AS net2 WHERE net2.'. $addressColumn .' >> net1.'. $addressColumn .')
            ORDER BY '. $addressColumn .' '. $order;

        $ids = [1,10,18];
        $params = '{' . implode(',',$ids) . '}';
        $sql = 'SELECT * FROM test(:ids )';

        $res = $connection->query($sql, [':ids' => $params])->fetchAll(\PDO::FETCH_ASSOC);
//        $res = $connection->query($sql)->fetchAll(\PDO::FETCH_COLUMN, 0);
        $this->data->rootIds = $res;
        //return $res;
    }

    public function actionTestApi()
    {
        $sql = '(SELECT string_agg(t::text, \',\') FROM (SELECT txt_column FROM testip WHERE
    address >> \'10.0.0.0/16\' ORDER BY address) AS t)';
        $query = new Query($sql);
        $res = Network::findAllByQuery($query);
        var_dump($res);
    }
}