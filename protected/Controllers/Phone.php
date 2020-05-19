<?php

namespace App\Controllers;

use App\Components\Cucm\UnRegisteredPhonesService;
use App\Components\DSPphones;
use App\Models\PhoneInfo;
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

    public function actionPhoneData($name = null)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        // Find the phone's data in the cucms
        $cmd = 'php '.ROOT_PATH.DS.'protected'.DS.'t4.php cucmsPhones/getPhoneByName2 --name='. $name;
        exec($cmd, $output);

        // Separate error from data
        $phoneData = [];
        $errors = [];
        foreach ($output as $item) {
            $item = json_decode($item, true);
            if (!empty($item['error'])) {
                $errors = array_merge($errors, $item['error']);
            } else {
                if ('Registered' == $item['status']) {
                    $phoneData = $item;
                }
            }
        }
        if (!empty($phoneData)) {
            $inventoryNumber = '';
            if (!empty($phoneData['serialNumber'])) {
                $items = PhoneInfo::getDbConnection()
                    ->query(self::SQL['phone_inventoryNumber'], [':serial_number' => '%' . $phoneData['serialNumber']])
                    ->fetchAll(\PDO::FETCH_ASSOC);
                if (count($items) > 0) {
                    $inventoryNumber = array_reduce($items,
                        function ($carry, $item) {
                            $mark = $carry == '' ? '' : ', ';
                            return $carry . $mark . $item['inventory_number'];
                        }, ''
                    );
                }
            }
//            $phoneData['inventoryNumber'] = 'InvN: ' . $inventoryNumber . '; SN: ' . $phoneData['serialNumber'];
            $phoneData['inventoryNumber'] = $inventoryNumber;
        }
        $this->data->result = [
            'errors' => $errors,
            'data' => $phoneData,
        ];
    }

    public function actionPhoneUpdate($phoneData)
    {
        $resultData['success'] = false;
        try {
            if (is_null($data = json_decode($phoneData, true))) {
                throw new \Exception('Json can not be converted');
            }
            $resultData['success'] = (new DSPphones())->persist(new Std($data));
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
     * Data on the phones connected to the switch but not existing in the database
     * @param int $switch_id
     */
    public function actionUnregisteredConnectedToSwitch(int $switch_id)
    {
        try {
            $this->data->data = (new UnRegisteredPhonesService())->dataOnUnregisteredPhonesConnectedToSwitch($switch_id);
        } catch (\Throwable $e) {
            $this->data->data = [];
            $this->data->error = 'Runtime error';
        }
    }

    /**
     * Data on the phones connected in the office but not existing in the database
     * @param int $office_id
     */
    public function actionUnregisteredInOffice(int $office_id)
    {
        try {
            $this->data->data = (new UnRegisteredPhonesService())->dataOnUnregisteredPhonesInOffice($office_id);
        } catch (\Throwable $e) {
            $this->data->data = [];
            $this->data->error = 'Runtime error';
        }
    }
}
