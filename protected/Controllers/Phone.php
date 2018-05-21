<?php

namespace App\Controllers;

use App\Components\DSPphones;
use App\Storage1CModels\InventoryItem1C;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Phone extends Controller
{
    public function actionPhoneData($name = null)
    {
        // Find the phone's data in the cucms
        $cmd = 'php '.ROOT_PATH.DS.'protected'.DS.'t4.php cucmsPhones'.DS.'getPhoneByName --name='. $name;
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
        if (!empty($phoneData) && isset($phoneData['serialNumber'])) {
            $query = (new Query())
                ->select('"inventoryNumber"')
                ->from(InventoryItem1C::getTableName())
                ->where('"serialNumber" LIKE :serialNumber')
                ->params([':serialNumber' => '%'.mb_substr($phoneData['serialNumber'], 1)]);
            $inventoryNumber = InventoryItem1C::findByQuery($query)->inventoryNumber;
            if (!is_null($inventoryNumber)) {
                $phoneData['inventoryNumber'] = $inventoryNumber;
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
}
