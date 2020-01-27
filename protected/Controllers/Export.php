<?php

namespace App\Controllers;

use App\Components\Export\ApplianceToExcel;
use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
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


    public function actionIpAppliances($ip = null)
    {
        $types = [self::ROUTER, self::SWITCH, self::VG];
        echo $this->outputManagementIpByApplianceTypes($types, $ip);
        die;
    }

    public function actionIpNexus($ip = null)
    {
        $types = [self::NEXUS];
        echo $this->outputManagementIpByApplianceTypes($types, $ip);
        die;
    }

    /**
     * Output all managementIp by Appliances Types and Ip
     *
     * @param array|null $types - Appliances Types
     * @param null $ip - ip address
     * @return string
     */
    private function outputManagementIpByApplianceTypes(array $types = null, $ip = null): string
    {
        $ipAddresses = $this->findAllManagementIpByApplianceTypes($types, $ip);
        return $this->formatOutputManagementIp($ipAddresses);
    }

    /**
     * Format output managementIp
     *
     * @param array $data
     * @return string
     */
    private function formatOutputManagementIp(array $data): string
    {
        $output = '';
        if (count($data) > 0) {
            foreach ($data as $item) {
                $output .= trim($item['hostname'],'"').','.$item['ipAddress'].'/'. ($item['masklen'] ?? 32) .','.$item['lotusId'].','.$item['vrf_name'].';';
            }
        }
        return $output;
    }

    /**
     * Find all managementIp by Appliances Types and Ip
     *
     * @param array|null $types - Appliances Types
     * @param null $ip - ip address
     * @return mixed
     */
    private function findAllManagementIpByApplianceTypes(array $types = null, $ip = null): array
    {
        $ipCondition = !is_null($ip) ? 'dP."ipAddress" = :ip AND ' : '';

        $typesCondition = '';
        if (!is_null($types)) {
            foreach ($types as $type) {
                $comma = empty($typesCondition) ? '' : ',';
                $typesCondition .= $comma.':'.$type;
            }
        }

        $sql = '
            SELECT
                appl.details->\'hostname\' AS hostname,
                dP."ipAddress",
                dP."masklen",
                office."lotusId",
                vrf.name AS vrf_name
            FROM equipment."dataPorts" dP
            JOIN network.networks nt ON nt.__id = dP.__network_id
            JOIN network.vrfs vrf ON vrf.__id = nt.__vrf_id
            JOIN equipment.appliances appl ON appl.__id = dP.__appliance_id
            JOIN equipment."applianceTypes" applType ON applType.__id = appl.__type_id
            JOIN company.offices office ON office.__id = appl.__location_id
            WHERE '.$ipCondition.'dP."isManagement" IS TRUE AND applType.type IN ('.$typesCondition.')
        ';

        $params = [];
        if (!is_null($types)) {
            foreach ($types as $type) {
                $params[':'.$type] = $type;
            }
        }
        if (!is_null($ip)) {
            $params[':ip'] = $ip;
        }

        $dbh = $this->app->db->default;
        $stm = $dbh->prepare(new Query($sql));
        $stm->execute($params);
        return $stm->fetchAll(\PDO::FETCH_ASSOC);
    }


    public function actionPhonesLog()
    {
        $errorLogFile = ROOT_PATH.DS.'Logs'.DS.Log::PHONES_ERRORS_LOG_FILE_NAME;

        if (!file_exists($errorLogFile)) {
            throw new Exception("No errors logs file found");
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . Log::PHONES_ERRORS_LOG_FILE_NAME);
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
        $logFile = ROOT_PATH . DS . 'Log' . DS . 'exportExcel.log';
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

    /**
     * Return Cucm-Publisher's Ip Addresses
     */
    public function actionCucmPublishersIp()
    {
        echo json_encode(
            array_map(
                function ($cucm) {
                    return $cucm->managementIp;
                },
                Appliance::findAllByType(ApplianceType::CUCM_PUBLISHER)->toArray()
            )
        );
        die;
    }
}
