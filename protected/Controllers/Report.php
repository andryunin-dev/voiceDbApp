<?php

namespace App\Controllers;


use App\Components\Reports\ApplianceTypeReport;
use App\Components\Reports\PlatformReport;
use App\Components\Reports\SoftReport;
use App\Components\Reports\VendorReport;
use App\Components\Sql\SqlFilter;
use App\Components\Tables\PivotTable;
use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\RecordItem;
use App\Components\Tables\Table;
use App\Components\Tables\TableConfig;
use App\Components\UrlExt;
use App\Models\ApplianceType;
use App\Models\Module;
use App\Models\Software;
use App\Models\Vendor;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Core\Url;
use T4\Http\Helpers;
use T4\Http\Request;
use T4\Mvc\Controller;

class Report extends Controller
{
    use DebugTrait;

    public function actionDefault()
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
        $this->data->software = Software::findAll(['order' => 'title']);
        $this->data->modules = Module::findAll(['order' => 'title']);
        $this->data->applianceTypes = ApplianceType::findAll(['order' => 'type']);
        $this->data->devsUrl = new UrlExt('/admin/devices');

        $this->data->platforms = PlatformReport::findAll();
        $this->data->types = ApplianceTypeReport::findAll();
        $this->data->softs = SoftReport::findAll();
        $this->data->vendors = VendorReport::findAll();

        $this->data->settings->activeTab = (Helpers::issetCookie('netcmdb_report_tab')) ? Helpers::getCookie('netcmdb_report_tab') : 'platforms';
        $this->data->activeLink->report = true;
    }
    public function actionNew()
    {
        $this->data->vendors = Vendor::findAll(['order' => 'title']);
        $this->data->software = Software::findAll(['order' => 'title']);
        $this->data->modules = Module::findAll(['order' => 'title']);
        $this->data->applianceTypes = ApplianceType::findAll(['order' => 'type']);
        $this->data->devsUrl = new UrlExt('/device/info');

        $this->data->platforms = PlatformReport::findAll();
        $this->data->types = ApplianceTypeReport::findAll();
        $this->data->softs = SoftReport::findAll();
        $this->data->vendors = VendorReport::findAll();

        $this->data->settings->activeTab = (Helpers::issetCookie('netcmdb_report_tab')) ? Helpers::getCookie('netcmdb_report_tab') : 'platforms';
        $this->data->activeLink->reportNew = true;
    }

    /*====Обработка Ajax запроса конфигурации таблицы ====*/
    public function actionTableSettings()
    {
        if (isset($_GET['tableName'])) {
            try {
                $tableName = $_GET['tableName'];
                $tb = new PivotTable(new PivotTableConfig($tableName));
                $tb->rowsOnPage(50);
                $config = $tb->buildTableConfig();
                $config->tableName = $_GET['tableName'];
                return $this->data->config = $config;
            } catch (Exception $e) {
                return $this->data->config = new Std();
            }
        }
    }


    public function actionPhoneStatsReport()
    {
        $this->data->activeLink->phonesReports = true;
    }

    public function actionPhoneStatsReportHandler()
    {
        try {
            $headerTemplate = 'PhoneStatsReportByModelsHeader.html';
            $bodyTemplate = 'PhoneStatsReportByModelsBody.html';
            $lotusLocationConf = 'lotusLocation';
            $request = (new Request());
            $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
            foreach ($request as $key => $value ) {
                switch ($key) {
                    case 'header':
                        $data['columns'] = $value->columns->toArrayRecursive();
                        $data['user'] = $this->data->user;
                        $this->data->header->html = $this->view->render($headerTemplate, $data);
                        break;
                    case 'body':
                        $request = $request->body;
                        $tbConf = new PivotTableConfig($request->tableName);
                        $tabFilter = new SqlFilter($tbConf->className());
                        if (isset($request->tableFilter)) {

                            $filterSet = $request->tableFilter->toArray();
                            $tabFilter->setFilterFromArray($filterSet);
                        }
                        $tb = new PivotTable($tbConf);
                        $tb->addFilter($tabFilter, 'append');
                        $tb->paginationUpdate($request->pager->page, $request->pager->rowsOnPage);
                        $tbData = $tb->getRecordsByPage();

                        //==========lotusLocation=========
                        $tbLotusConf = new TableConfig($lotusLocationConf);
                        $tbLotus = new Table($tbLotusConf);
                        $lotusData = $tbLotus->getRecords();
                        $lotusDataByLotusId = array_reduce($lotusData, function ($carry, $item) {
                            $carry[$item['lotus_id']] = $item;
                            return $carry;
                        });
                        // ================concatenate data from pivot columns with glue '/' and unset array plTitleActive
                        $totalDevs = 'plTitle';
                        $activeDevs = 'plTitleActive';
                        foreach ($tbData as $dataKey => $values) {
                            if (! isset($values[$totalDevs])) {
                                continue;
                            }
                            array_walk($values[$totalDevs], function (&$counter, $platform) use($values, $totalDevs, $activeDevs) {
                                $counter = (isset($values[$activeDevs][$platform])) ? $counter . '/' . $values[$activeDevs][$platform] : $counter . '/0';
                            });
                            $tbData[$dataKey][$totalDevs] = $values[$totalDevs];
                            unset($tbData[$dataKey][$activeDevs]);
                            $tbData[$dataKey] = new RecordItem($tbData[$dataKey]);
                        }
                        //========end==============
                        //===========append info about people
                        $data['data'] = array_map(function ($dataItem) use($lotusDataByLotusId)  {
                            $dataItem->people = $lotusDataByLotusId[$dataItem->lotusId]['employees'];
                            return $dataItem;
                        }, $tbData);
                        //==========end================
                        $data['columns'] = $request->columns;
                        $this->data->body->html = $this->view->render($bodyTemplate, $data);
                        $this->data->body->tableFilter = $tabFilter;
                        $this->data->body->pager = $request->pager;
                        $this->data->body->pager->page = $tb->currentPage();
                        $this->data->body->pager->pages = $tb->numberOfPages();
                        $this->data->body->pager->records = $tb->numberOfRecords();
                        $info[] = 'Записей: ' . $tb->numberOfRecords();
                        $this->data->body->info = $info;
                        break;
                    case 'headerFilter':
                        $tb = new PivotTable(new PivotTableConfig($request->tableName));
                        $filter = $request->headerFilter->filter;
                        $column = $filter->column;
                        $values[] = $filter->value . '%';
                        $sqlFilter = (new SqlFilter($tb->config->className()))
                            ->setFilter($filter->column, $filter->statement, $values);
                        $tb->addFilter($sqlFilter, 'append');
                        $this->data->result = $tb->distinctColumnValues($column);
                        break;
                    default:
                        break;
                }
            }
        } catch (\Exception $e) {
            $this->data->exception = $e->getMessage();
        }

    }

    public function actionPhoneStatsByClustersReport()
    {
        $this->data->activeLink->phonesReports = true;
    }

    public function actionPhoneStatsByClustersReportHandler()
    {
        try {
            $headerTemplate = 'PhoneStatsReportByModelsHeader.html';
            $bodyTemplate = 'PhoneStatsReportByModelsBody.html';
            $lotusLocationConf = 'lotusLocation';
            $request = (new Request());
            $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
            foreach ($request as $key => $value ) {
                switch ($key) {
                    case 'header':
                        $data['columns'] = $value->columns->toArrayRecursive();
                        $data['user'] = $this->data->user;
                        $this->data->header->html = $this->view->render($headerTemplate, $data);
                        break;
                    case 'body':
                        $request = $request->body;
                        $tbConf = new PivotTableConfig($request->tableName);
                        $tabFilter = new SqlFilter($tbConf->className());
                        if (isset($request->tableFilter)) {

                            $filterSet = $request->tableFilter->toArray();
                            $tabFilter->setFilterFromArray($filterSet);
                        }
                        $tb = new PivotTable($tbConf);
                        $tb->addFilter($tabFilter, 'append');
                        $tb->paginationUpdate($request->pager->page, $request->pager->rowsOnPage);
                        $tbData = $tb->getRecordsByPage();

                        //==========lotusLocation=========
                        $tbLotusConf = new TableConfig($lotusLocationConf);
                        $tbLotus = new Table($tbLotusConf);
                        $lotusData = $tbLotus->getRecords();
                        $lotusDataByLotusId = array_reduce($lotusData, function ($carry, $item) {
                            $carry[$item['lotus_id']] = $item;
                            return $carry;
                        });
                        // ================concatenate data from pivot columns with glue '/' and unset array plTitleActive
                        $totalDevs = 'byPublishIp';
                        $activeDevs = 'byPublishIpActive';
                        foreach ($tbData as $dataKey => $values) {
                            if (! isset($values[$totalDevs])) {
                                continue;
                            }
                            array_walk($values[$totalDevs], function (&$counter, $platform) use($values, $totalDevs, $activeDevs) {
                                $counter = (isset($values[$activeDevs][$platform])) ? $counter . '/' . $values[$activeDevs][$platform] : $counter . '/0';
                            });
                            $tbData[$dataKey][$totalDevs] = $values[$totalDevs];
                            unset($tbData[$dataKey][$activeDevs]);
                            $tbData[$dataKey] = new RecordItem($tbData[$dataKey]);
                        }
                        //========end==============
                        //===========append info about people
                        $data['data'] = array_map(function ($dataItem) use($lotusDataByLotusId)  {
                            $dataItem->people = $lotusDataByLotusId[$dataItem->lotusId]['employees'];
                            return $dataItem;
                        }, $tbData);
                        //==========end================
                        $data['columns'] = $request->columns;
                        $this->data->body->html = $this->view->render($bodyTemplate, $data);
                        $this->data->body->tableFilter = $tabFilter;
                        $this->data->body->pager = $request->pager;
                        $this->data->body->pager->page = $tb->currentPage();
                        $this->data->body->pager->pages = $tb->numberOfPages();
                        $this->data->body->pager->records = $tb->numberOfRecords();
                        $info[] = 'Записей: ' . $tb->numberOfRecords();
                        $this->data->body->info = $info;
                        break;
                    case 'headerFilter':
                        $tb = new PivotTable(new PivotTableConfig($request->tableName));
                        $filter = $request->headerFilter->filter;
                        $column = $filter->column;
                        $values[] = $filter->value . '%';
                        $sqlFilter = (new SqlFilter($tb->config->className()))
                            ->setFilter($filter->column, $filter->statement, $values);
                        $tb->addFilter($sqlFilter, 'append');
                        $this->data->result = $tb->distinctColumnValues($column);
                        break;
                    default:
                        break;
                }
            }
        } catch (\Exception $e) {
            $this->data->exception = $e->getMessage();
        }

    }
}