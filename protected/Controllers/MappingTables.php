<?php
/**
 * Created by IntelliJ IDEA.
 * User: karasev-dl
 * Date: 14.11.2018
 * Time: 17:38
 */

namespace App\Controllers;


use App\Components\SimpleTableHelpers;
use App\MappingModels\LotusLocation;
use App\MappingModels\RoutersSwitches;
use App\ViewModels\MappedLocations_View;
use App\ViewModels\MappedLotusLocations_1CLocations_View;
use App\ViewModels\MappedPcData;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Http\Request;
use T4\Mvc\Controller;

class MappingTables extends Controller
{
    public function actionLocationMapping()
    {
        $this->data->locations = MappedLocations_View::findAll();
        
    }
    
    public function actionLotusAnd1CLocations()
    {
        $this->data->locations = MappedLotusLocations_1CLocations_View::findAll();
        
    }

    public function actionRoutersSwitches()
    {
        $this->data->devs = RoutersSwitches::findAll();
    }

    public function actionPcData()
    {
        $this->data->pcData = MappedPcData::findAllWithMappedDivision();
    }
//    Reg centers mapping table
    public function actionSqlQueryExample()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
//        $this->data->time = microtime(true);
        $request = (new Request());
//        $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
        $request = $request->get;


        $conditions = SimpleTableHelpers::filtersToStatement($request->filters);
        $conditions = empty($conditions) ? '' : ' WHERE ' . $conditions;
        $pagination = SimpleTableHelpers::paginationToStatement($request->pagination);
        $sorting = SimpleTableHelpers::sortingToStatement($request->sorting);

        $dataQuery = 'SELECT * FROM view."mappedLotusLocations" ' .  $conditions . ' ' . $sorting  . ' ' . $pagination;
        $counterQuery = 'SELECT count(1) FROM view."mappedLotusLocations" ' .  $conditions;
        $params = [];
        $con = MappedLocations_View::getDbConnection();

        $counterStm = $con->query($counterQuery, $params);
        $counterResult = $counterStm->fetchColumn();

        $dataStm = $con->query($dataQuery, $params);
        $dataResult = $dataStm->fetchAll(\PDO::FETCH_ASSOC);
        $this->data->data = $dataResult;
        $this->data->counter = $counterResult;
        $this->data->time = microtime(true) - $this->data->time;
    }
    protected function saveRegCenter($postParams)
    {
        $accessor = $postParams->accessor;
        $newValue = $postParams->cellData;
        $lotusId = $postParams->rowData->lotus_id;
        if (!(is_string($newValue) && is_numeric($lotusId)))
        {
            throw new Exception('Неверный тип данных');
        }
        try {
            $current = LotusLocation::findByPK($lotusId);
            if ($current === false)
            {
                $res = (new LotusLocation())
                    ->fill([
                        'lotus_id' => $lotusId,
                        'reg_center' => $newValue
                    ])
                    ->save();
            } else {
                $res = $current
                    ->fill(['reg_center' => $newValue])
                    ->save();
            }
            if ($res === false)
            {
                throw new Exception('Ошибка сохранения данных');
            }
        } catch (\Exception $e) {
            $this->data->error = $e->getMessage();
//            http_response_code(201);
        }
    }
    protected function getRegCentersTableData($getParams)
    {
        $table = 'view."mappedLotusLocations"';

        $dataQuery = 'SELECT *  FROM ' . $table;
        $counterQuery = 'SELECT count(1) FROM ' .  $table;

        $where = SimpleTableHelpers::filtersToStatement($getParams->filters);
        $offsetLimit = SimpleTableHelpers::paginationToOffsetLimitStatement($getParams->pagination);
        $orderBy = SimpleTableHelpers::sortingToStatement($getParams->sorting);

        if ($where !== false) {
            $dataQuery .= ' WHERE ' . $where;
            $counterQuery .= ' WHERE ' . $where;
        }
        if ($orderBy !== false) {
            $dataQuery .= ' ORDER BY ' . $orderBy;
        }
        if ($offsetLimit !== false) {
            $dataQuery .= ' ' . $offsetLimit;
        }


        try {
            $con = MappedLocations_View::getDbConnection();
            $stm = $con->query($dataQuery, []);
            $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
            $this->data->data = $result;

            $stm = $con->query($counterQuery, []);
            $result = $stm->fetchColumn();
            $this->data->counter = $result;
            $this->data->queryTime = microtime(true) - microtime(true);
//            throw new \T4\Http\Exception('test error', 220);
        } catch (Exception $e) {
            $this->data->data = [];
            $this->data->error = $e->getMessage();
            $this->data->errorCode = $e->getCode();
        }
    }
    public function actionRegCentersTableData()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $this->data->queryTime = microtime(true);
        try {
            $request = new Request();
            if ($request->method === 'get')
            {
                $this->getRegCentersTableData($request->get);
            } else {
                $this->saveRegCenter($request->body);
            }
            if (isset($this->data->error)) {
                throw new Exception();
            }
        } catch (\Exception $e) {
            http_response_code(201);
        }

    }
    public function actionRegCentersFilterData()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $this->data->queryTime = microtime(true);
        $request = (new Request())->get;
        $table = 'view."mappedLotusLocations"';
        $accessor = '"' . $request->accessor . '"';

        $query = 'SELECT DISTINCT ' . $accessor . ' FROM ' . $table;

        $where = SimpleTableHelpers::filtersToStatement($request->filters);
        $orderBy = SimpleTableHelpers::sortingToStatement($request->sorting);

        if ($where !== false) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY ' . $accessor;
        try {
            $con = MappedLocations_View::getDbConnection();
            $stm = $con->query($query, []);
            $result = $stm->fetchAll(\PDO::FETCH_COLUMN,0);
            $this->data->data = $result;
            $this->data->queryTime = microtime(true) - microtime(true);
        } catch (Exception $e) {
            $this->data->data = [];
            $this->data->error = $e->getMessage();
        }
    }
}
