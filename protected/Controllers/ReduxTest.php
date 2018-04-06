<?php


namespace App\Controllers;


use App\Components\Fixtures\ObjItem;
use T4\Core\Collection;
use T4\Core\Config;
use T4\Core\Std;
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

    /**
     * URL: voice.rs.ru/reduxTest/rootElements.json
     */
    public function actionRootElements()
    {
        $fromFile = new Config($this->path);
        $this->data = new Std($fromFile->rootElements->toArray());
    }
}