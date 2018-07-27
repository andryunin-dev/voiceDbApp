<?php

namespace App\Controllers;


use App\Components\Reports\ApplianceTypeReport;
use App\Components\Reports\ApplianceTypeWithoutInventoryReport;
use App\Components\Reports\PlatformReport;
use App\Components\Reports\SoftReport;
use App\Components\Reports\VendorReport;
use App\Components\Sql\SqlFilter;
use App\Components\Tables\PivotTable;
use App\Components\Tables\PivotTableConfig;
use App\Components\Tables\RecordItem;
use App\Components\Tables\Table;
use App\Components\UrlExt;
use App\Models\ApplianceType;
use App\Models\Module;
use App\Models\Software;
use App\Models\Vendor;
use App\ViewModels\DevCallsStats;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Core\Url;
use T4\Http\Helpers;
use T4\Http\Request;
use T4\Mvc\Controller;

class Report extends Controller
{
    private const APP_TYPES = [ApplianceType::PHONE, ApplianceType::ROUTER, ApplianceType::SWITCH]; // список типов устройств для которых высчитывается процент устройств без инвентарного номера

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

        $this->data->typesHasNotInventory = ApplianceTypeWithoutInventoryReport::getAppliancesPercents(self::APP_TYPES);
    }

    /*====Обработка Ajax запроса конфигурации таблицы ====*/
    public function actionTableSettings()
    {
        if (isset($_GET['tableName'])) {
            try {
                $tableName = $_GET['tableName'];
                $config = Table::buildConfig($tableName);

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
            $bodyFooterTemplate = 'PhoneStatsReportByModelsBodyFooter.html';
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
                        $tbConf = Table::getTableConfig($request->tableName);
                        $tb = Table::getTable($tbConf);
                        $tabFilter = new SqlFilter($tbConf->className());
                        if (isset($request->tableFilter)) {
                            $filterSet = $request->tableFilter->toArray();
                            $tabFilter->setFilterFromArray($filterSet);
                        }
                        $tb->addFilter($tabFilter, 'append');
                        $tb->paginationUpdate($request->pager->page, $request->pager->rowsOnPage);
                        $tbData = $tb->getRecordsByPage();

                        // ================concatenate data from pivot columns with glue '/' and unset array plTitleActive
                        $totalDevs = 'plTitle';
                        $activeDevs = 'plTitleActive';
                        foreach ($tbData as $dataKey => $values) {
                            if (! isset($values[$totalDevs])) {
                                $tbData[$dataKey] = new RecordItem($tbData[$dataKey]);
                                continue;
                            }
                            array_walk($values[$totalDevs], function (&$counter, $platform) use($values, $totalDevs, $activeDevs) {
                                $counter = (isset($values[$activeDevs][$platform])) ? $counter . '/' . $values[$activeDevs][$platform] : $counter . '/0';
                            });
                            $tbData[$dataKey][$totalDevs] = $values[$totalDevs];
                            unset($tbData[$dataKey][$activeDevs]);
                            $tbData[$dataKey] = new RecordItem($tbData[$dataKey]);
                        }

                        //==========end================
                        $data['data'] = $tbData;
                        $data['columns'] = $request->columns;
                        $data['columnsBF'] = $request->bodyFooter;
                        //============get body footer data==============
                        $tbBF = $tb->getBodyFooterTable();
                        $tbDataBF = [];
                        if (false !== $tbBF) {
                            $tbBF->addFilter($tabFilter, 'append');
                            $tbDataBF = $tbBF->getRecords();
                        }
                        foreach ($tbDataBF as $dataKey => $values) {
                            if (! isset($values[$totalDevs]) || is_null($values[$totalDevs])) {
                                $tbDataBF[$dataKey] = new RecordItem($tbDataBF[$dataKey]);
                                continue;
                            }
                            array_walk($values[$totalDevs], function(&$counter, $platform) use($values, $totalDevs, $activeDevs){
                                $counter = (isset($values[$activeDevs][$platform])) ? $counter . '/' . $values[$activeDevs][$platform] : $counter . '/0';
                            });
                            $tbDataBF[$dataKey][$totalDevs] = $values[$totalDevs];
                            unset($tbDataBF[$dataKey][$activeDevs]);
                            $tbDataBF[$dataKey] = new RecordItem($tbDataBF[$dataKey]);
                        }
                        $data['dataBF'] = $tbDataBF;

                        //общее кол-во сотрудников
                        $tbPeopleConf = Table::getTableConfig('devGeoEmployeesLotusIdDistinct');
                        $tbPeople = Table::getTable($tbPeopleConf);
                        $tbPeople->addFilter($tabFilter, 'append');
                        $people = $tbPeople->getRecords(null,null,null,true);
                        $people = array_reduce($people, function ($acc, $item) {
                            return $acc += $item['lotus_employees'];
                        });
                        $data['lotusEmployeesTotal'] = $people;
                        //=============render templates=============
                        $this->data->body->html = $this->view->render($bodyTemplate, $data);
                        $this->data->bodyFooter->html = $this->view->render($bodyFooterTemplate, $data);
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

                        $tbFilter = new SqlFilter($tb->config->className());
                        if (isset($request->headerFilter->tableFilter) && $request->headerFilter->tableFilter instanceof Std) {
                            $tbFilter ->addFilterFromArray($request->headerFilter->tableFilter->toArray());
                        }
                        $tbFilter->removeFilter($column);
                        $tb->addFilter($tbFilter, 'append');

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
            $headerTemplate = 'PhoneStatsReportByClustersHeader.html';
            $bodyTemplate = 'PhoneStatsReportByClustersBody.html';
            $bodyFooterTemplate = 'PhoneStatsReportByClustersBodyFooter.html';
            $request = (new Request());
            $request = (0 == $request->get->count()) ? $request = $request->post : $request->get;
            foreach ($request as $key => $value ) {
                switch ($key) {
                    case 'header':
                        $data['columns'] = $value->columns->toArrayRecursive();
                        $data['user'] = $this->data->user;
                        //==============CUCM names=============
                        $tbCucmConf = Table::getTableConfig('devGeoCUCMPublishers');
                        $tbCucm = Table::getTable($tbCucmConf);
                        $cucmNames = $tbCucm->getRecords(null,null,null,true);
                        $data['cucmNames'] = array_reduce($cucmNames, function ($carry, $item) {
                            $appDetails = json_decode($item['appDetails'], true);
                            $cucmName = isset($appDetails['reportName']) ? $appDetails['reportName'] : null;
                            $carry[$item['managementIp']] = $cucmName;
                            return $carry;
                        });

                        $this->data->header->html = $this->view->render($headerTemplate, $data);
                        break;
                    case 'body':
                        $request = $request->body;
                        $tbConf = Table::getTableConfig($request->tableName);
                        $tb = Table::getTable($tbConf);

                        $tabFilter = new SqlFilter($tbConf->className());
                        if (isset($request->tableFilter)) {
                            $filterSet = $request->tableFilter->toArray();
                            $tabFilter->setFilterFromArray($filterSet);
                        }

                        $tb
                            ->addFilter($tabFilter, 'append');
                        $tb->paginationUpdate($request->pager->page, $request->pager->rowsOnPage);
                        $tbData = $tb->getRecordsByPage();

                        // ================concatenate data from pivot columns with glue '/' and unset array plTitleActive
                        $totalDevs = 'byPublishIp';
                        $activeDevs = 'byPublishIpActive';

                        array_walk($tbData, function (&$item, $key) use(&$tbData) {
                            if (is_null($item['byPublishIpActive'])) {
                                return;
                            }
                            array_walk($item['byPublishIpActive'], function (&$item, $key2) use(&$tbData, $key) {
                                $item = isset($tbData[$key]['byPublishIpActiveHW'][$key2]) ?
                                    $item . '/' . $tbData[$key]['byPublishIpActiveHW'][$key2] :
                                    $item . '/' . 0;
                            });
                        });

                        foreach ($tbData as $dataKey => $values) {
                        if (! isset($values[$totalDevs])) {
                            continue;
                        }
                        $tbData[$dataKey][$totalDevs] = $tbData[$dataKey][$activeDevs];
                        unset($tbData[$dataKey][$activeDevs]);
                        $tbData[$dataKey] = new RecordItem($tbData[$dataKey]);
                    }
                        //==========end================
                        $data['data'] = $tbData;
                        $data['columns'] = $request->columns;
                        $data['columnsBF'] = $request->bodyFooter;
                        //============get body footer data==============
                        $tbBF = $tb->getBodyFooterTable();
                        $tbDataBF = [];
                        if (false !== $tbBF) {
                            $tbBF->addFilter($tabFilter, 'append');
                            $tbDataBF = $tbBF->getRecords();
                        }

                        array_walk($tbDataBF, function (&$item, $key) use(&$tbDataBF) {
                            if (is_null($item['byPublishIpActive'])) {
                                return;
                            }
                            array_walk($item['byPublishIpActive'], function (&$item, $key2) use(&$tbDataBF, $key) {
                                $item = isset($tbDataBF[$key]['byPublishIpActiveHW'][$key2]) ?
                                    $item . '/' . $tbDataBF[$key]['byPublishIpActiveHW'][$key2] :
                                    $item . '/' . 0;
                            });
                        });

                        foreach ($tbDataBF as $dataKey => $values) {
                            if (! isset($values[$totalDevs]) || is_null($values[$totalDevs])) {
                                continue;
                            }
                            $tbDataBF[$dataKey][$totalDevs] = $tbDataBF[$dataKey][$activeDevs];
                            unset($tbDataBF[$dataKey][$activeDevs]);
                            $tbDataBF[$dataKey] = new RecordItem($tbDataBF[$dataKey]);
                        }
                        $data['dataBF'] = $tbDataBF;

                        //общее кол-во сотрудников
                        $tbPeopleConf = Table::getTableConfig('devGeoEmployeesLotusIdDistinct');
                        $tbPeople = Table::getTable($tbPeopleConf);
                        $tbPeople->addFilter($tabFilter, 'append');
                        $people = $tbPeople->getRecords(null,null,null,true);
                        $people = array_reduce($people, function ($acc, $item) {
                            return $acc += $item['lotus_employees'];
                        });
                        $data['lotusEmployeesTotal'] = $people;

                        //=============render templates=============

                        $this->data->body->html = $this->view->render($bodyTemplate, $data);
                        $this->data->bodyFooter->html = $this->view->render($bodyFooterTemplate, $data);
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

                        $tbFilter = new SqlFilter($tb->config->className());
                        if (isset($request->headerFilter->tableFilter) && $request->headerFilter->tableFilter instanceof Std) {
                            $tbFilter ->addFilterFromArray($request->headerFilter->tableFilter->toArray());
                        }
                        $tbFilter->removeFilter($column);
                        $tb->addFilter($tbFilter, 'append');

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



    public function actionPhoneStatsByNotUsedReport()
    {
        $this->data->activeLink->phonesReports = true;
    }

    public function actionPhoneStatsByNotUsedReportHandler()
    {
        try {
            $notUsedPhonesUrl = new Url('/device/info');

            $headerTemplate = 'PhoneStatsReportByNotUsedHeader.html';
            $bodyTemplate = 'PhoneStatsReportByNotUsedBody.html';
            $bodyFooterTemplate = 'PhoneStatsReportByNotUsedBodyFooter.html';
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
                        $tbConf = Table::getTableConfig($request->tableName);
                        $tb = Table::getTable($tbConf);
                        $tabFilter = new SqlFilter($tbConf->className());
                        if (isset($request->tableFilter)) {
                            $filterSet = $request->tableFilter->toArray();
                            $tabFilter->setFilterFromArray($filterSet);
                        }
                        $tb->addFilter($tabFilter, 'append');
                        $tb->paginationUpdate($request->pager->page, $request->pager->rowsOnPage);
                        $tbData = $tb->getRecordsByPage();

                        //========== concatenate data from non calling dev statictics================
                        $nonCallingDevicesByOffices = DevCallsStats::getAmountOfNonCallingDevicesByOffices();
                        foreach ($tbData as $k => $item) {
                            $officeNonCallingStats = $nonCallingDevicesByOffices['offices'][$item['office_id']];
                            if (!is_null($officeNonCallingStats)) {
                                foreach ($officeNonCallingStats as $kindStats => $valueStats) {
                                    $tbData[$k][$kindStats] = $valueStats;
                                }
                            }
                        }
                        foreach ($tbData as $dataKey => $values) {
                            $tbData[$dataKey] = new RecordItem($tbData[$dataKey]);
                        }

                        //==========end================
                        $data['data'] = $tbData;
                        $data['columns'] = $request->columns;
                        $data['columnsBF'] = $request->bodyFooter;
                        $data['notUsedPhonesUrl'] = $notUsedPhonesUrl;
                        //============get body footer data==============
                        $tbBF = $tb->getBodyFooterTable();
                        $tbDataBF = [];
                        if (false !== $tbBF) {
                            $tbBF->addFilter($tabFilter, 'append');
                            $tbDataBF = $tbBF->getRecords();
                        }
                        //========== concatenate data from non calling dev statictics for body footer ================
                        foreach ($nonCallingDevicesByOffices['total'] as $kindStats => $valueStats) {
                            $tbDataBF[0][$kindStats] = $valueStats;
                        }
                        foreach ($tbDataBF as $dataKey => $values) {
                            $tbDataBF[$dataKey] = new RecordItem($tbDataBF[$dataKey]);
                        }
                        $data['dataBF'] = $tbDataBF;

                        //общее кол-во сотрудников
                        $tbPeopleConf = Table::getTableConfig('devGeoEmployeesLotusIdDistinct');
                        $tbPeople = Table::getTable($tbPeopleConf);
                        $tbPeople->addFilter($tabFilter, 'append');
                        $people = $tbPeople->getRecords(null,null,null,true);
                        $people = array_reduce($people, function ($acc, $item) {
                            return $acc += $item['lotus_employees'];
                        });
                        $data['lotusEmployeesTotal'] = $people;
                        //=============render templates=============
                        $this->data->body->html = $this->view->render($bodyTemplate, $data);
                        $this->data->bodyFooter->html = $this->view->render($bodyFooterTemplate, $data);
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

                        $tbFilter = new SqlFilter($tb->config->className());
                        if (isset($request->headerFilter->tableFilter) && $request->headerFilter->tableFilter instanceof Std) {
                            $tbFilter ->addFilterFromArray($request->headerFilter->tableFilter->toArray());
                        }
                        $tbFilter->removeFilter($column);
                        $tb->addFilter($tbFilter, 'append');

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
