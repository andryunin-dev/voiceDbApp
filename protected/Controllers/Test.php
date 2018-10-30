<?php

namespace App\Controllers;

use App\Models\Appliance;
use App\ViewModels\DevGeo_View;
use T4\Dbal\Query;
use T4\Mvc\Controller;
use T4\Orm\Exception;

class Test extends Controller
{

    public function actionTt()
    {
        try {

            $connection = odbc_connect("Driver={SQL Server Native Client 10.0};Server=$server;Database=$database;", $user, $password);
            if ($connection === false) {
                throw new Exception('Database connection not established');
            }

            $query = 'SELECT * FROM dbo."Saratov_AgentLogOut" AS agent_statistics WHERE agent_statistics."LogoutDateTime" BETWEEN ? AND ?';
            $lastDay = (new \DateTime("last day"))->format('Y-m-d 00:00:00');
            $currentDay = (new \DateTime())->format('Y-m-d 00:00:00');
            $params = [$lastDay, $currentDay];

            $stmt = odbc_prepare($connection, $query);
            if ($stmt === false) {
                throw new Exception('SQL command was not prepared successfully');
            }

            $success = odbc_execute($stmt, $params);
            if ($success === false) {
                throw new Exception('SQL command execution error');
                // todo можно вытащить последнюю ошибку
            }

//            var_dump(odbc_num_rows($stmt));

            $result = [];
            while ($item = odbc_fetch_array($stmt)) {
                $result[] = $item['Extension'];
            }

            odbc_free_result($stmt);
            odbc_close($connection);

            $dialNumbers = array_unique($result);
            var_dump($dialNumbers);

        } catch (\Exception $e) {
            if ($connection !== false) {
                odbc_free_result($stmt);
                odbc_close($connection);
            }
            var_dump($e);
        }

        die;
    }


    public function actionGetPhone()
    {

        $name = '';
        $cmd = 'php '.ROOT_PATH.DS.'protected'.DS.'t4.php cucmsPhones'.DS.'getPhoneByName --name='. $name;
        exec($cmd, $result);

        var_dump($result);

        die;
    }


    public function actionDeleteVeryOldAnalogPhones()
    {
        $query = (new Query())
            ->select(['appliance_id', 'appAge', 'platformSerial'])
            ->from(DevGeo_View::getTableName())
            ->where('"appType" = :appType AND ("platformTitle" = :platform_title_1 OR "platformTitle" = :platform_title_2) AND "appAge" > 300')
            ->params([
                ':appType' => 'phone',
                ':platform_title_1' => 'Analog Phone',
                ':platform_title_2' => 'VGC Phone',
            ]);

        $res = DevGeo_View::findAllByQuery($query);
        $counter = 0;
        foreach ($res as $dev) {
            $item = Appliance::findByPK($dev->appliance_id);
            if ($item instanceof Appliance) {
                $item->delete();

                echo ++$counter . ' - ' . $item->platform->platform->title . ' - has been deleted' . "\n";
            }
        }
    }
}
