<?php

namespace App\Controllers;

use App\Components\Cucm\CdpPhoneService;
use App\Components\Cucm\CucmsService;
use App\Components\Inventory\DataSetType;
use App\Components\Inventory\UpdateService;
use App\Components\SimpleTableHelpers;
use App\Models\Office;
use App\Models\PhoneInfo;
use App\ViewModels\Geo_View;
use App\ViewModels\PhoneRecorder_View;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Http\Request;
use T4\Mvc\Controller;

class Phone extends Controller
{
    private const SQL = [
        'phone_css' => '
            WITH
                phones AS (
                    SELECT (prefix || "phoneDN") AS number, css
                    FROM equipment."phoneInfo"
                )
            SELECT phone.css
            FROM phones phone
            WHERE phone.number = :number',
        'phone_inventoryNumber' => '
            SELECT inventory_number
            FROM storage_1c.foreign_1c
            WHERE serial_number LIKE :serial_number',
        'phone_prefix' => 'SELECT name, prefix FROM equipment."phoneInfo"',
    ];

    /**
     * @param string $name
     * @throws \Exception
     */
    public function actionPhoneData(string $name)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $errors = [];
        $dataOfRegisteredPhone = [];
        foreach ((new CucmsService())->cucms() as $cucm) {
            try {
                $registeredPhone = $cucm->registeredPhone($name);
                if (false !== $registeredPhone) {
                    $dataOfRegisteredPhone = $registeredPhone->toArray();
                    $phoneInfo = PhoneInfo::findByColumn('name', $dataOfRegisteredPhone['name']);
                    $dataOfRegisteredPhone['inventoryNumber'] = false !== $phoneInfo ? $phoneInfo->inventoryNumber() : '';
                    break;
                }
            } catch (\Throwable $e) {
                $errors[] = 'Runtime error';
            }
        }
        $this->data->result = [
            'errors' => $errors,
            'data' => $dataOfRegisteredPhone,
        ];
    }

    public function actionPhoneUpdate($phoneData)
    {
        $resultData['success'] = false;
        try {
            if (is_null($data = json_decode($phoneData, true))) {
                throw new \Exception('Json can not be converted');
            }
            $data['dataSetType'] = DataSetType::PHONE;
            (new UpdateService())->update($data);
            $resultData['success'] = true;
        } catch (\Throwable $e) {
            $resultData['error'] = $e->getMessage();
        }
        $this->data->result = $resultData;
    }

    public function actionCss()
    {
        $arguments = (new Request())->get;
        if (0 === $arguments->count() || is_null($arguments->number) || !is_numeric($arguments->number)) {
            echo json_encode(['error' => 'Invalid request format']);
            die;
        }
        echo json_encode(['css' =>
            array_map(
                function ($item) {
                    return $item['css'];
                },
                PhoneInfo::getDbConnection()
                    ->query(self::SQL['phone_css'], [':number' => $arguments->number])
                    ->fetchAll(\PDO::FETCH_ASSOC)
            )
        ]);
        die;
    }

    public function actionPrefix()
    {
        echo json_encode(PhoneInfo::getDbConnection()
            ->query(self::SQL['phone_prefix'], [])
            ->fetchAll(\PDO::FETCH_ASSOC));
        die;
    }

    /**
     * Data on the phones connected in the office but not existing in the database
     * @param string $extFilters - Office's lotustID
     */
    public function actionUnregisteredInOffice($extFilters = null)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        try {
            $extFilters = new Std(json_decode($extFilters));
            $age = intval($extFilters->age);
            $lotusId = intval($extFilters->lotusId);
            if (empty($lotusId)) {
                $this->data->data = [];
                $this->data->counter = 0;
                return;
            }
            $office = Office::findByColumn('lotusId', $lotusId);
            if (false === $office) {
                throw new \Exception('Office (lotusId = ' . $lotusId . ') does not exist');
            }
            $this->data->data = (new CdpPhoneService())->dataOfUnregisteredPhonesConnectedInOffice($office, $age);
            $this->data->counter = count($this->data->data);
        } catch (\Throwable $e) {
            $this->data->error = 'Runtime error';
            $this->data->data = [];
            $this->data->counter = 0;
            http_response_code(201);
        }
    }

    public function actionOfficeList()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
//        $this->data->queryTime = microtime(true);
        $request = (new Request())->get;
        $table = 'view."geo"';
        $accessor = '"' . $request->accessor . '"';

        $query = 'SELECT DISTINCT "lotusId" val, office lab FROM view.geo';

        $where = SimpleTableHelpers::filtersToStatement($request->filters);
        $orderBy = SimpleTableHelpers::sortingToStatement($request->sorting);

        if ($where !== false) {
            $query .= ' WHERE ' . $where;
        }
        $query .= ' ORDER BY ' . $accessor;
        try {
            $con = Geo_View::getDbConnection();
            $stm = $con->query($query, []);
            $result = $stm->fetchAll(\PDO::FETCH_ASSOC);
            $this->data->data = $result;
//            $this->data->queryTime = microtime(true) - microtime(true);
        } catch (Exception $e) {
            $this->data->data = [];
            $this->data->error = $e->getMessage();
        }
    }

    public function actionRecordable()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        try {
            $phones = PhoneRecorder_View::getDbConnection()
                ->query('SELECT * FROM ' . PhoneRecorder_View::getTableName())
                ->fetchAll(\PDO::FETCH_ASSOC);
            $this->data->data = $phones;
            $this->data->counter = count($this->data->data);
        } catch (\Throwable $e) {
            $this->data->error = 'Runtime error';
            $this->data->data = [];
            $this->data->counter = 0;
            http_response_code(201);
        }
    }
}
