<?php

namespace App\ViewModels;

use App\Models\LotusLocation;
use T4\Core\Collection;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class DevModulePortGeo
 * @package App\ViewModels
 *
 * @property string $region
 * @property int $region_id
 * @property string $city
 * @property int $city_id
 * @property string $office
 * @property int $office_id
 * @property int $lotusId
 * @property int $appliance_id
 * @property int $location_id
 * @property string $appLastUpdate
 * @property string $appAge
 * @property bool $appInUse
 * @property int $hostname
 * @property string $appDetails
 * @property string $appComment
 * @property int $cluster_id
 * @property string $clusterTitle
 * @property string $clusterDetails
 * @property string $clusterComment
 * @property int $platformVendor_id
 * @property int $platformVendor
 * @property int $platformItem_id
 * @property int $platformTitle
 * @property int $softwareVendor_id
 * @property int $softwareVendor
 * @property int $softwareItem_id
 * @property int $softwareTitle
 * @property int $softwareVersion
 * @property string $moduleInfo
 * @property string $portInfo
 *
 * @property string $managementIp
 *
 * @property Collection|ModuleItem_View[] $modules
 * @property Collection|DataPort_View[] $dataPorts
 * @property Collection|DataPort_View[] $noManagementPorts
 */
class DevModulePortGeo extends Model
{
    use DevTypesTrait;
    use ViewHelperTrait;

    protected static $schema = [
        'table' => 'view.dev_module_port_geo',
        'columns' => [
            'region' => ['type' => 'string'],
            'region_id' => ['type' => 'int', 'length' => 'big'],
            'city' => ['type' => 'string'],
            'city_id' => ['type' => 'int', 'length' => 'big'],
            'office' => ['type' => 'string'],
            'office_id' => ['type' => 'int', 'length' => 'big'],
            'lotusId' => ['type' => 'int'],
            'officeAddress' => ['type' => 'string'],
            'officeComment' => ['type' => 'string'],
            'officeDetails' => ['type' => 'jsonb'],
            'appliance_id' => ['type' => 'int', 'length' => 'big'],
            'appLastUpdate' => ['type' => 'datetime'],
            'appAge' => ['type' => 'int'],
            'appInUse' => ['type' => 'boolean'],
            'hostname' => ['type' => 'string'],
            'appDetails' => ['type' => 'jsonb'],
            'appComment' => ['type' => 'string'],
            'appType_id' => ['type' => 'int', 'length' => 'big'],
            'appType' => ['type' => 'string'],
            'cluster_id' => ['type' => 'int', 'length' => 'big'],
            'clusterTitle' => ['type' => 'string'],
            'clusterDetails' => ['type' => 'jsonb'],
            'clusterComment' => ['type' => 'string'],
            'platformVendor_id' => ['type' => 'int', 'length' => 'big'],
            'platformVendor' => ['type' => 'string'],
            'platformItem_id' => ['type' => 'int', 'length' => 'big'],
            'platformSerial' => ['type' => 'string'],
            'platformTitle' => ['type' => 'string'],
            'platform_id' => ['type' => 'int', 'length' => 'big'],
            'softwareVendor_id' => ['type' => 'int', 'length' => 'big'],
            'softwareVendor' => ['type' => 'string'],
            'softwareItem_id' => ['type' => 'int', 'length' => 'big'],
            'software_id' => ['type' => 'int', 'length' => 'big'],
            'softwareTitle' => ['type' => 'string'],
            'softwareVersion' => ['type' => 'string'],
            'moduleInfo' => ['type' => 'jsonb'],
            'portInfo' => ['type' => 'jsonb'],
            'managementIp' => ['type' => 'string']
        ]
    ];
    public static $columnMap = [
        'reg_id' => 'region_id',
        'reg' => 'region',
        'loc_id' => 'office_id',
        'ven' => 'platformVendor',
        'ven_id' => 'platformVendor_id',
        'pl' => 'platformTitle',
        'pl_id' => 'platform_id',
        'soft' => 'softwareTitle',
        'soft_id' => 'software_id',
        'noActiveAge' => ['column' => 'appAge', 'predicate' => 'gt']
    ];
    public static $applianceTypeMap = [
        'switch' => 'SW',
        'router' => 'R',
        'cmp' => 'CMP',
        'cms' => 'CMS',
        'phone' => 'TEL'
    ];

    protected static $sortOrders = [
        'default' => 'region, city, office, "appType", hostname, appliance_id',
        'region' => 'region, city, office, appType, hostname, appliance_id',
        'city' => 'city, office appType, hostname, appliance_id',
        'office' => 'office, appType, hostname, appliance_id, city',
        'hostname' => ' hostname, appType, appliance_id',
    ];


    protected function beforeSave()
    {
        return false;
    }

    protected function getModules()
    {
        $src = json_decode($this->moduleInfo);
        $res = new Collection();
        if (empty($src)) {
            return $res;
        }
        foreach ($src as $item) {
            $moduleItem = new ModuleItem_View($item);
            $details = empty($moduleItem->details) ? new Std() : new Std($moduleItem->details->toArrayRecursive());
            $moduleItem->details = $details;
            $res->add($moduleItem);
        }
        return $res;
    }

    protected function getDataPorts()
    {
        $src = json_decode($this->portInfo);
        $res = new Collection();
        if (empty($src)) {
            return $res;
        }
        foreach ($src as $port) {
            $dataPort = new DataPort_View($port);
            $details = empty($dataPort->details) ? new Std() : new Std($dataPort->details->toArrayRecursive());
            $dataPort->details = $details;
            $res->add($dataPort);
        }
        return $res;
    }

    protected function getNoManagementPorts()
    {
        $res = $this->dataPorts->filter(
            function ($port) {
                return false === $port->isManagement;
            }
        );

        return $res;
    }

    protected function getPeople()
    {
        return LotusLocation::employeesByLotusId($this->lotusId);
    }

    public function lastUpdateDate()
    {
        return $this->appLastUpdate ? (new \DateTime($this->appLastUpdate))->format('Y-m-d') : null;
    }

    public function lastUpdateDateTime()
    {
        return $this->appLastUpdate ? ('last update: ' . ((new \DateTime($this->appLastUpdate))->setTimezone(new \DateTimeZone('Europe/Moscow')))->format('d.m.Y H:i \M\S\K(P)')) : null;
    }

    /**
     * @param Std $params
     *
     * @return Std $result результаты поиска вместе с параметрами поиска и страниц
     */
    public static function findAllByParams(Std $params)
    {
        //Todo - реализовать поиск по параметрам $search

        $params->resultAsArray = is_bool($params->resultAsArray) ? $params->resultAsArray : false;
        $params->currentPage = empty($params->currentPage) ? 1 : $params->currentPage;
        $params->rowsOnPage = empty($params->rowsOnPage) ? -1 : $params->rowsOnPage;
        $params->order = empty($params->order) ? 'default' : $params->order;
        $params->filters = ($params->filters instanceof Std) ? $params->filters : new Std();
        $params->filters->appTypes = empty($params->filters->appTypes) ? self::appTypeFilter() : self::appTypeFilter($params->filters->appTypes);
        $params->search = ($params->search instanceof Std) ? $params->search->toArray() : [];
        $params->columns = empty($params->columns) ?  self::findColumns() : self::findColumns($params->columnList);
        /**
         * @var Std $result
         * properties:
         * int currentPage
         * string $order
         * array $filters
         * int $recordsCount
         * int $rowsOnPage
         * int $pagesCount
         * int $currentPage
         * int $offset
         * array $columns
         *
         */

        $params->order = self::sortOrder($params->order);
        $where[] = '"appType_id" IN (' . implode(',', $params->filters->appTypes) . ')';
        // собираем search
        if (! empty($params->search)) {
            foreach ($params->search as $key => $val) {
                if ($key == 'noActiveAge') {
                    $where[] =  '("appAge"' . '>=' . $val . ' OR "appAge" ISNULL)';
                } elseif ($key == 'activeAge') {
                    $where[] = '"appAge" ' . '< ' . $val;
                } else {
                    $where[] = $key . '=' . $val;
                }
            }
        }
        //получаем количество записей и кол-во страниц с учетом фильтров
        $queryRecordsCount = (new Query())
            ->select()
            ->from(self::getTableName())
            ->where(implode(' AND ', $where));
        $params->recordsCount = self::countAllByQuery($queryRecordsCount);

        //если rowsOnPage не задан или rowsOnPage = -1 (выводим все на одной странице)
        $params->rowsOnPage = ($params->rowsOnPage < 0) ? $params->recordsCount : $params->rowsOnPage;
        $params->pagesCount = ceil($params->recordsCount / $params->rowsOnPage);

        //если в параметрах запроса номер страницы больше максимального, то устанавливаем его в макс.
        $params->currentPage = ($params->currentPage <= $params->pagesCount) ? $params->currentPage : $params->pagesCount;
        $params->offset = ($params->currentPage - 1) * $params->rowsOnPage;

        //создаем запрос данных
        $queryData = (new Query())
            ->select($params->columns)
            ->from(self::getTableName())
            ->where(implode(' AND ', $where))
            ->order($params->order)
            ->limit($params->rowsOnPage)
            ->offset($params->offset);

        $params->data = self::findAllByQuery($queryData);
        if (true === $params->resultAsArray) {
            $params->data = $params->data->toArrayRecursive();
        }
        return $params;
    }
    public static function findAllLotusIdByParams(Std $params)
    {
        //Todo - реализовать поиск по параметрам $search
        $params->filters = ($params->filters instanceof Std) ? $params->filters : new Std();
        if (is_string($params->filters->appTypes)) {
            $params->filters->appTypes = empty($params->filters->appTypes) ? self::appTypeFilter() : self::appTypeFilter($params->filters->appTypes);
        }
        $params->search = ($params->search instanceof Std) ? $params->search->toArray() : $params->search;
        $params->columns = 'lotusId';
        /**
         * @var Std $result
         * properties:
         * int currentPage
         * string $order
         * array $filters
         * int $recordsCount
         * int $rowsOnPage
         * int $pagesCount
         * int $currentPage
         * int $offset
         * array $columns
         *
         */

        $where[] = '"appType_id" IN (' . implode(',', $params->filters->appTypes) . ')';
        // собираем search
        if (! empty($params->search)) {
            foreach ($params->search as $key => $val) {
                if ($key == 'noActiveAge') {
                    $where[] = '("appAge"' . '>=' . $val . ' OR "appAge" ISNULL)';
                } elseif ($key == 'activeAge') {
                    $where[] = '"appAge"' . '<' . $val;
                } else {
                    $where[] = $key . '=' . $val;
                }
            }
        }

        //создаем запрос данных
        $queryData = (new Query())
            ->select($params->columns)
            ->from(self::getTableName())
            ->group('"' . $params->columns . '"')
            ->where(implode(' AND ', $where));

        $params->locations = self::findAllByQuery($queryData);
        return $params;
    }
}