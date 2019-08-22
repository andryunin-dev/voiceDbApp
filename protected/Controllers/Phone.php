<?php

namespace App\Controllers;

use App\Components\DSPphones;
use App\Models\PhoneInfo;
use App\Storage1CModels\InventoryItem1C;
use T4\Core\Std;
use T4\Dbal\Query;
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
    ];

    public function actionPhoneData($name = null)
    {
        // Find the phone's data in the cucms
        $cmd = 'php '.ROOT_PATH.DS.'protected'.DS.'t4.php cucmsPhones'.DS.'getPhoneByName2 --name='. $name;
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

        // Find inventory number
        if (!empty($phoneData)) {
            $inventoryNumber = null;
            if (isset($phoneData['serialNumber'])) {
                $query = (new Query())
                    ->select('"inventoryNumber"')
                    ->from(InventoryItem1C::getTableName())
                    ->where('"serialNumber" LIKE :serialNumber')
                    ->params([':serialNumber' => '%'.mb_substr($phoneData['serialNumber'], 1)]);
                $inventoryNumber = InventoryItem1C::findByQuery($query)->inventoryNumber;
            }
            $phoneData['inventoryNumber'] = (!is_null($inventoryNumber)) ? $inventoryNumber : '';
            if (! empty($phoneData['serialNumber'])) {
                $phoneData['inventoryNumber'] = $phoneData['inventoryNumber'] . ', SN: ' . $phoneData['serialNumber'];
            }
        }

        // Return result
        $result = [
            'errors' => $errors,
            'data' => $phoneData,
        ];
        $this->data->result = $result;
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
}
