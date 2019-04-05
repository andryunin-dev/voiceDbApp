<?php

namespace App\Controllers;

use App\Components\Export\ApplianceToExcel;
use App\Components\RLogger;
use App\ViewModels\DevModulePortGeo;
use App\ViewModels\MappedLocations_View;
use T4\Core\Exception;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Export extends Controller
{
    private const ROUTER = 'router';
    private const SWITCH = 'switch';
    private const VG = 'vg';
    private const NEXUS = 'Nexus';


    public function actionIpNexus($ip = null)
    {
        $tableColumns = ['hostname','portInfo','lotusId'];

        if (!is_null($ip)) {
            $query = (new Query())
                ->select($tableColumns)
                ->from(DevModulePortGeo::getTableName())
                ->where('"managementIp" = :ip AND "appType" IN (:nexus)')
                ->params([
                    ':ip' => $ip,
                    ':nexus' => self::NEXUS,
                ]);
        } else {
            $query = (new Query())
                ->select($tableColumns)
                ->from(DevModulePortGeo::getTableName())
                ->where('"appType" IN (:nexus)')
                ->params([
                    ':nexus' => self::NEXUS,
                ]);
        }
        $appliances = DevModulePortGeo::findAllByQuery($query);

        // Semicolon format
        $outputData = '';
        foreach ($appliances as $appliance) {
            $dataPorts = json_decode($appliance->portInfo);
            if (!is_null($dataPorts)) {
                foreach ($dataPorts as $dataPort) {
                    if (true == $dataPort->isManagement) {
                        $outputData .= $appliance->hostname . ',' . $dataPort->ipAddress . ',' . $appliance->lotusId . ';';
                    }
                }
            }
        }
        echo $outputData;
        die;
    }

    public function actionIpAppliances($ip = null)
    {
        $tableColumns = ['hostname','portInfo','lotusId'];

        if (!is_null($ip)) {
            $query = (new Query())
                ->select($tableColumns)
                ->from(DevModulePortGeo::getTableName())
                ->where('"managementIp" = :ip AND "appType" IN (:switch, :router, :vg)')
                ->params([
                    ':ip' => $ip,
                    ':switch' => self::SWITCH,
                    ':router' => self::ROUTER,
                    ':vg' => self::VG,
                ]);
        } else {
            $query = (new Query())
                ->select($tableColumns)
                ->from(DevModulePortGeo::getTableName())
                ->where('"appType" IN (:switch, :router, :vg)')
                ->params([
                    ':switch' => self::SWITCH,
                    ':router' => self::ROUTER,
                    ':vg' => self::VG,
                ]);
        }
        $appliances = DevModulePortGeo::findAllByQuery($query);

        // Semicolon format
        $outputData = '';
        foreach ($appliances as $appliance) {
            $dataPorts = json_decode($appliance->portInfo);
            if (!is_null($dataPorts)) {
                foreach ($dataPorts as $dataPort) {
                    if (true == $dataPort->isManagement) {
                        $outputData .= $appliance->hostname . ',' . $dataPort->ipAddress . ',' . $appliance->lotusId . ';';
                    }
                }
            }
        }
        echo $outputData;
        die;
    }


    public function actionPhonesErrorsLogs()
    {
        $errorLogFile = ROOT_PATH.DS.'Logs'.DS.Logs::PHONES_ERRORS_LOG_FILE_NAME;

        if (!file_exists($errorLogFile)) {
            throw new Exception("No errors logs file found");
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . Logs::PHONES_ERRORS_LOG_FILE_NAME);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: no-cache');
        header('Content-Length: ' . filesize($errorLogFile));
        readfile($errorLogFile);
        die;
    }


    /**
     * @throws \Exception
     */
    public function actionHardInvExcel()
    {
        $logFile = ROOT_PATH . DS . 'Logs' . DS . 'exportExcel.log';
        $logger = RLogger::getInstance('EXPORT_EXCEL', $logFile);

        try {
            (new ApplianceToExcel())->export();
        } catch (\Throwable $e) {
            $logger->error($e->getMessage() ?? '""');
        }
    }
    
    /**
     * Deprecated! Isn't used
     */
    public function actionGetMappedLocations()
    {
        $filename = 'export.csv';
        $delimeter = ';';
        $f = fopen('php://memory', 'w');
        
        $result = MappedLocations_View::findAll();
        $headerRow = array_keys(MappedLocations_View::getColumns());
//        write header row
        fputcsv($f, $headerRow, $delimeter);
        
        foreach ($result as $location) {
            if (! empty($location->Comment)) {
                $location->comment = preg_replace('/[\r\n]+/',' ', $location->Comment);
            }
            $locationArray = $location->toArray();
            fputcsv($f, array_values($locationArray), $delimeter);
        }
        
        fseek($f, 0);
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'";');
        fpassthru($f);
        die;
    }
}
