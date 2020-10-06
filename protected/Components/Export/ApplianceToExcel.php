<?php
namespace App\Components\Export;

use App\ViewModels\DevPhoneInfoGeo;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Query;

class ApplianceToExcel
{

    const MEMORY_LIMIT = '256M';
    const TIME_LIMIT = 60; // sec
    const PHONE = 'phone';
    const MAX_APP_AGE = 72;
    const SELL_STYLE_TYPE_DEFAULT = 3;
    const SELL_STYLE_TYPE_APPLIANCE_MAX_AGE = 6;
    const SELL_STYLE_TYPE_APPLIANCE_NOT_IN_USE = 4;


    public function export()
    {
        ini_set('memory_limit', self::MEMORY_LIMIT);
        ini_set('max_execution_time', self::TIME_LIMIT);

        // create excel file
        $excel = $this->createHardInventoryExcel();

        // Redirect excel output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Hard inventory__' . gmdate('d M Y') . '.xlsx"');
        header('Cache-Control: max-age=0');
        readfile($excel);

        // Delete excel file
        unlink($excel);
        exit;
    }

    /**
     * Create excel file
     *
     * @return string
     * @throws Exception
     */
    private function createHardInventoryExcel()
    {
        $excelFileName = ROOT_PATH . DS . 'Logs' . DS . 'tempExcel_' . mb_ereg_replace('\.', '_', microtime(true)) . '.xlsx';
        $zip = new \ZipArchive();

        // Delete exists excel file
        if (file_exists($excelFileName)) {
            unlink($excelFileName);
        }

        // Try opening the ZIP excel file
        if ($zip->open($excelFileName, \ZipArchive::OVERWRITE) !== true) {
            if ($zip->open($excelFileName, \ZipArchive::CREATE) !== true) {
                throw new Exception('Could not open ' . $excelFileName . ' for writing.');
            }
        }

        // Init Excel data
        $charPosition = 0;
        $sharedStringsSI = '';

        // Create "Appliances" worksheet
        $this->createWorksheetAppliances($zip, $charPosition, $sharedStringsSI);

        // Create "Appliances with modules" worksheet
        $this->createWorksheetAppliancesWithModules($zip, $charPosition, $sharedStringsSI);

        // Create "Phones" worksheet
        $this->createWorksheetPhones($zip, $charPosition, $sharedStringsSI);

        // Export Workbook to the file
        $this->exportToExcel($zip, $charPosition, $sharedStringsSI);

        // Close excel file
        if ($zip->close() === false) {
            throw new Exception("Could not close zip file $excelFileName.");
        }

        return $excelFileName;
    }

    /**
     * Define Type of Appliance sell's style
     *
     * @param $appliance
     * @return int
     */
    private function getApplianceSellStyleType($appliance)
    {
        $styleType = self::SELL_STYLE_TYPE_DEFAULT;
        if ($appliance->app_age > self::MAX_APP_AGE || is_null($appliance->app_age)) {
            $styleType = self::SELL_STYLE_TYPE_APPLIANCE_MAX_AGE;
        } elseif (false === $appliance->app_inUse) {
            $styleType = self::SELL_STYLE_TYPE_APPLIANCE_NOT_IN_USE;
        }
        return $styleType;
    }

    /**
     * Define Type of Module sell's style
     *
     * @param $appliance
     * @param int $styleApplianceType
     * @return int
     */
    private function getModuleSellStyleType($appliance, int $styleApplianceType)
    {
        $styleType = self::SELL_STYLE_TYPE_DEFAULT;
        if ($styleApplianceType != self::SELL_STYLE_TYPE_DEFAULT) {
            $styleType = $styleApplianceType;
        } elseif ($appliance->module_age > self::MAX_APP_AGE) {
            $styleType = self::SELL_STYLE_TYPE_APPLIANCE_MAX_AGE;
        } elseif (false === $appliance->module_inUse) {
            $styleType = self::SELL_STYLE_TYPE_APPLIANCE_NOT_IN_USE;
        }
        return $styleType;
    }

    /**
     * Define Type of Phone sell's style
     *
     * @param $phone
     * @return int
     */
    private function getPhoneSellStyleType($phone)
    {
        $styleType = self::SELL_STYLE_TYPE_DEFAULT;
        if ($phone->appAge > self::MAX_APP_AGE) {
            $styleType = self::SELL_STYLE_TYPE_APPLIANCE_MAX_AGE;
        } elseif (false === $phone->appInUse) {
            $styleType = self::SELL_STYLE_TYPE_APPLIANCE_NOT_IN_USE;
        }
        return $styleType;
    }

    /**
     * Create "Appliances" worksheet
     *
     * @param \ZipArchive $zip
     * @param $charPosition
     * @param $sharedStringsSI
     */
    private function createWorksheetAppliances(\ZipArchive $zip, &$charPosition, &$sharedStringsSI)
    {
        // Get all Appliances except Phones
        $sql = '
        SELECT
            app."lotus_regCenter" AS "app_regCenter",
            app.region AS "app_region",
            app.city AS "app_city",
            app.office AS "app_office",
            app."lotusId" AS "app_lotusId",
            app."appType" AS "app_type",
            (app."platformVendor" || \' \' || app."platformTitle")  AS "app_title",
            app."platformSerial" AS "app_serialNumber",
            app."platformSerialAlt" AS "app_serialNumberAlt",
            app."platformDetails" AS "app_platformDetails",
            app."softwareTitle" AS "app_softwareTitle",
            app."softwareVersion" AS "app_softwareVersion",
            app."appDetails" AS "app_details",
            app."appLastUpdate" AS "app_lastUpdate",
            app."appComment" AS "app_comment",
            app."appInUse" AS "app_inUse",
            app."managementIp" AS "app_managementIp",
            app."appAge" AS "app_age",
            app.hostname AS "app_hostname",
            app."inventoryNumber" AS "app_inventoryNumber",
            app."responsiblePerson" AS "app_molFio"
        FROM view.dev_phone_info_geo AS app
        WHERE app."appType" != :phone
        ';
        $params = [':phone' => self::PHONE];
        $appliances = DevPhoneInfoGeo::findAllByQuery(new Query($sql), $params);

        // Init worksheet data
        $sheet = 'sheet1';
        $currentRow = 1;
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X'];

        // Add Header to the worksheet
        $header = ['№п/п', 'Рег. центр', 'Регион', 'Город', 'Офис', 'LotusId', 'Hostname', 'Management Ip', 'Type', 'Device', 'Device ser', 'Device alt ser', 'Inv. Number', 'Mol', 'Software', 'Software ver.', 'Appl. last update', 'Comment', 'Этаж', 'Ряд', 'Стойка', 'Сторона стойки', 'Unit', 'Высота Unit'];
        $rows = '<row r="' . $currentRow . '" spans="1:' . count($columns) . '" x14ac:dyDescent="0.25">';
        for ($i = 0; $i < count($columns); $i++) {
            $sharedStringsSI .= '<si><t>' . $header[$i] . '</t></si>';
            $rows .= '<c r="' . $columns[$i] . $currentRow . '" s="2" t="s"><v>' . $charPosition++ . '</v></c>';
        }
        $rows .= '</row>';
        $currentRow++;

        // Add Appliances to the worksheet
        foreach ($appliances as $appliance) {

            // define sell style type for the appliance
            $styleType = $this->getApplianceSellStyleType($appliance);

            // Cast Appliance Details
            $applianceDetails = empty(json_decode($appliance->app_details)) ? new Std() : json_decode($appliance->app_details);
            //site
            $site = empty($applianceDetails->site) ? new Std() : $applianceDetails->site;
            // Cast Platform Details
            $platformDetails = empty(json_decode($appliance->app_platformDetails)) ? new Std() : json_decode($appliance->app_platformDetails);

            // define appliance columns
            $applianceColumns = [
                $appliance->app_regCenter,
                $appliance->app_region,
                $appliance->app_city,
                $appliance->app_office,
                $appliance->app_lotusId,
                $appliance->app_hostname,
                $appliance->app_managementIp,
                $appliance->app_type,
                $appliance->app_title,
                $appliance->app_serialNumber,
                $appliance->app_serialNumberAlt,
                $appliance->app_inventoryNumber,
                $appliance->app_molFio,
                $appliance->app_softwareTitle,
                $appliance->app_softwareVersion,
                $appliance->app_lastUpdate,
                strip_tags($appliance->app_comment),
                strip_tags($site->floor),
                strip_tags($site->row),
                strip_tags($site->rack),
                strip_tags($site->rackSide),
                strip_tags($site->unit),
                strip_tags($platformDetails->units),
            ];

            // Open row for the appliance to the worksheet
            $rows .= '<row r="' . $currentRow . '" spans="1:' . count($columns) . '" x14ac:dyDescent="0.25">';

            // add first column (порядковый номер строки) to the row
            $rows .= '<c r="A' . $currentRow . '" s="' . $styleType . '"><v>' . ($currentRow - 1) . '</v></c>';

            // add appliance columns to the row
            for ($i = 1; $i < count($columns); $i++) {
                $sharedStringsSI .= '<si><t>' . $applianceColumns[$i - 1] . '</t></si>';
                $rows .= '<c r="' . $columns[$i] . $currentRow . '" s="' . $styleType . '" t="s"><v>' . $charPosition++ . '</v></c>';
            }

            // Close row
            $rows .= '</row>';
            $currentRow++;
        }

        // Add worksheet to workbook
        $dimensionSheet1 = '<dimension ref="A1:' . end($columns) . ($currentRow - 1) . '"/>';
        $autoFilter = '<autoFilter ref="B1:' . end($columns) . ($currentRow - 1) . '"/>';
        $frozenPane1 = '<sheetView tabSelected="1" workbookViewId="0"><pane state="frozen" activePane="bottomLeft" topLeftCell="A2" ySplit="1"/><selection sqref="A2" activeCell="A2" pane="bottomLeft"/></sheetView>';
        $zip->addFromString('xl/worksheets/'.$sheet.'.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">' . $dimensionSheet1 . '<sheetViews>' . $frozenPane1 . '</sheetViews><sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/><sheetData>' . $rows . '</sheetData>' . $autoFilter . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
    }


    /**
     * Create "Appliances with Modules" worksheet
     *
     * @param \ZipArchive $zip
     * @param $charPosition
     * @param $sharedStringsSI
     */
    private function createWorksheetAppliancesWithModules(\ZipArchive $zip, &$charPosition, &$sharedStringsSI)
    {
        // Get all Appliances with modules except Phones
        $sql = '
            SELECT
                app."lotus_regCenter" AS "app_regCenter",
                app.region AS "app_region",
                app.city AS "app_city",
                app.office AS "app_office",
                app."lotusId" AS "app_office_lotusId",
                app."appType" AS "app_type",
                (app."platformVendor" || \' \' || app."platformTitle")  AS "app_title",
                app."platformSerial" AS "app_serialNumber",
                app."platformSerialAlt" AS "app_serialNumberAlt",
                CAST(app."appDetails"::jsonb->>\'hostname\' AS citext) AS app_hostname,
                app."softwareTitle" AS "app_softwareTitle",
                app."softwareVersion" AS "app_softwareVersion",
                app."appLastUpdate" AS "app_lastUpdate",
                app."appComment" AS "app_comment",
                app."appInUse" AS "app_inUse",
                app."managementIp" AS "app_managementIp",
                app."appAge" AS "app_age",
                app.hostname AS "app_hostname",
                app."inventoryNumber" AS "app_inventoryNumber",
                app."responsiblePerson" AS "app_molFio",
                module.title AS "module_title",
                moduleItem."serialNumber" AS "module_serialNumber",
                moduleItem."lastUpdate" AS "module_lastUpdate",
                ((date_part(\'epoch\' :: TEXT, age(now(), moduleItem."lastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER AS "module_age",
                moduleItem."inUse" AS "module_inUse",
                module1c."invItem_inventoryNumber" AS "module_inventoryNumber",
                module1c.mol_fio AS "module_molFio"
            FROM view.dev_phone_info_geo AS app
                LEFT JOIN equipment."moduleItems" AS moduleItem ON moduleItem.__appliance_id = app.appliance_id
                LEFT JOIN equipment.modules AS module ON module.__id = moduleItem.__module_id
                LEFT JOIN view.dev_module1c AS module1c ON module1c.module_id = moduleItem.__id
            WHERE app."appType" != :phone
        ';
        $params = [':phone' => self::PHONE];
        $appliances = DevPhoneInfoGeo::findAllByQuery(new Query($sql), $params);

        // Init worksheet data
        $sheet = 'sheet2';
        $currentRow = 1;
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W'];

        // Add Header to the worksheet
        $header = ['№п/п', 'Рег. центр', 'Регион', 'Город', 'Офис', 'LotusId', 'Hostname', 'Management Ip', 'Type', 'Device', 'Device ser', 'Device alt ser', 'Inv. Number', 'Mol', 'Software', 'Software ver.', 'Appl. last update', 'Module', 'Module ser', 'Module\'s Inv. Number', 'Module\'s Mol', 'Module last update', 'Comment'];
        $rows = '<row r="' . $currentRow . '" spans="1:' . count($columns) . '" x14ac:dyDescent="0.25">';
        for ($i = 0; $i < count($columns); $i++) {
            $sharedStringsSI .= '<si><t>' . $header[$i] . '</t></si>';
            $rows .= '<c r="' . $columns[$i] . $currentRow . '" s="2" t="s"><v>' . $charPosition++ . '</v></c>';
        }
        $rows .= '</row>';
        $currentRow++;

        // Add Appliances to the worksheet
        foreach ($appliances as $appliance) {

            // define sell style type for the appliance
            $styleApplianceType = $this->getApplianceSellStyleType($appliance);

            // define sell style type for the module
            $styleModuleType = $this->getModuleSellStyleType($appliance, $styleApplianceType);

            // define appliance columns
            $applianceColumns = [
                ['value' => $appliance->app_regCenter, 'style' => $styleApplianceType],
                ['value' => $appliance->app_region, 'style' => $styleApplianceType],
                ['value' => $appliance->app_city, 'style' => $styleApplianceType],
                ['value' => $appliance->app_office, 'style' => $styleApplianceType],
                ['value' => $appliance->app_office_lotusId, 'style' => $styleApplianceType],
                ['value' => $appliance->app_hostname, 'style' => $styleApplianceType],
                ['value' => $appliance->app_managementIp, 'style' => $styleApplianceType],
                ['value' => $appliance->app_type, 'style' => $styleApplianceType],
                ['value' => $appliance->app_title, 'style' => $styleApplianceType],
                ['value' => $appliance->app_serialNumber, 'style' => $styleApplianceType],
                ['value' => $appliance->app_serialNumberAlt, 'style' => $styleApplianceType],
                ['value' => $appliance->app_inventoryNumber, 'style' => $styleApplianceType],
                ['value' => $appliance->app_molFio, 'style' => $styleApplianceType],
                ['value' => $appliance->app_softwareTitle, 'style' => $styleApplianceType],
                ['value' => $appliance->app_softwareVersion, 'style' => $styleApplianceType],
                ['value' => $appliance->app_lastUpdate, 'style' => $styleApplianceType],
                ['value' => $appliance->module_title, 'style' => $styleModuleType],
                ['value' => $appliance->module_serialNumber, 'style' => $styleModuleType],
                ['value' => $appliance->module_inventoryNumber, 'style' => $styleModuleType],
                ['value' => $appliance->module_molFio, 'style' => $styleModuleType],
                ['value' => $appliance->module_lastUpdate, 'style' => $styleModuleType],
                ['value' => $appliance->app_comment, 'style' => $styleModuleType],
            ];

            // Open row for the appliance to the worksheet
            $rows .= '<row r="' . $currentRow . '" spans="1:' . count($columns) . '" x14ac:dyDescent="0.25">';

            // add first column (порядковый номер строки) to the row
            $rows .= '<c r="A' . $currentRow . '" s="' . $styleApplianceType . '"><v>' . ($currentRow - 1) . '</v></c>';

            // add appliance columns to the row
            for ($i = 1; $i < count($columns); $i++) {
                $sharedStringsSI .= '<si><t>' . $applianceColumns[$i - 1]['value'] . '</t></si>';
                $rows .= '<c r="' . $columns[$i] . $currentRow . '" s="' . $applianceColumns[$i - 1]['style'] . '" t="s"><v>' . $charPosition++ . '</v></c>';
            }

            // Close row
            $rows .= '</row>';
            $currentRow++;
        }

        // Add worksheet to workbook
        $dimensionSheet1 = '<dimension ref="A1:' . end($columns) . ($currentRow - 1) . '"/>';
        $autoFilter = '<autoFilter ref="B1:' . end($columns) . ($currentRow - 1) . '"/>';
        $frozenPane1 = '<sheetView tabSelected="1" workbookViewId="0"><pane state="frozen" activePane="bottomLeft" topLeftCell="A2" ySplit="1"/><selection sqref="A2" activeCell="A2" pane="bottomLeft"/></sheetView>';
        $zip->addFromString('xl/worksheets/'.$sheet.'.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">' . $dimensionSheet1 . '<sheetViews>' . $frozenPane1 . '</sheetViews><sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/><sheetData>' . $rows . '</sheetData>' . $autoFilter . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
    }

    /**
     * Create "Phones" worksheet
     *
     * @param \ZipArchive $zip
     * @param $charPosition
     * @param $sharedStringsSI
     */
    private function createWorksheetPhones(\ZipArchive $zip, &$charPosition, &$sharedStringsSI)
    {
        // Get all Phones
        $sql = '
        SELECT
            phone."lotus_regCenter",
            phone.region,
            phone.city,
            phone.office,
            phone."lotusId",
            phone."publisherIp",
            publisher."appDetails"::jsonb->>\'reportName\'  AS publisher_description,
            (phone."platformVendor" || \' \' || phone."platformTitle")  AS device,
            phone.name,
            phone."managementIp",
            phone.partition,
            phone.css,
            phone.prefix,
            phone."phoneDN",
            phone."e164mask",
            phone."platformSerial",
            phone."softwareTitle",
            phone."softwareVersion",
            phone."appComment",
            phone."phoneDescription",
            phone."devicePool",
            phone."alertingName",
            phone.timezone,
            phone."dhcpEnabled",
            phone."dhcpServer",
            phone."domainName",
            phone."tftpServer1",
            phone."tftpServer2",
            phone."defaultRouter",
            phone."dnsServer1",
            phone."dnsServer2",
            phone."callManager1",
            phone."callManager2",
            phone."callManager3",
            phone."callManager4",
            phone."vlanId",
            phone."userLocale",
            phone."cdpNeighborDeviceId",
            phone."cdpNeighborIP",
            phone."cdpNeighborPort",
            app."platformTitle" AS "cdpNeighborDevice_title",
            app."inventoryNumber" AS "cdpNeighborDevice_inventoryNumber",
            CAST(phone."appDetails"::jsonb->>\'hostname\' AS citext) AS phone_hostname,
            phone."clusterTitle",
            phone."appType",
            phone."appInUse",
            phone."appAge",
            phone."appLastUpdate",
            phone.last_call_day,
            phone.d0_calls_amount,
            phone.m0_calls_amount,
            phone.m1_calls_amount,
            phone.m2_calls_amount,
            phone."inventoryNumber",
            phone."responsiblePerson"
        FROM view.dev_phone_info_geo AS phone
            LEFT JOIN view.dev_phone_info_geo AS app ON app."managementIp" = phone."cdpNeighborIP"
            LEFT JOIN view.dev_phone_info_geo AS publisher ON publisher."managementIp" = CAST(phone."publisherIp" AS inet) 
        WHERE phone."appType" = :phone
        ';
        $params = [':phone' => self::PHONE];
        $phones = DevPhoneInfoGeo::findAllByQuery(new Query($sql), $params);

        // Init worksheet data
        $sheet = 'sheet3';
        $currentRow = 1;
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ', 'BA', 'BB'];

        // Add Header to the worksheet
        $header = ['№п/п', 'Рег. центр', 'Регион', 'Город', 'Офис', 'LotusId', 'Cluster', 'Hostname', 'Type', 'Device', 'Name', 'IP', 'Publisher', 'Publisher Description', 'Partion', 'CSS', 'Prefix', 'DN', 'Ext Phone Num Mask', 'Device ser', 'Inv. Number', 'Mol', 'Software', 'Software ver.', 'Last update', 'Last call', 'Calls (тек. день)', 'Calls (тек. месяц)', 'Calls (прошлый месяц)', 'Calls (позапрошлый месяц)', 'Comment', 'Description', 'Device Pool', 'Alerting Name', 'Timezone', 'DHCP enable', 'DHCP server', 'Domain name', 'TFTP server 1', 'TFTP server 2', 'Default Router', 'DNS server 1', 'DNS server 2', 'Call manager 1', 'Call manager 2', 'Call manager 3', 'Call manager 4', 'VLAN ID', 'User locale', 'CDP neighbor device ID', 'CDP neighbor IP', 'CDP neighbor Port', 'CDP neighbor Device Title', 'CDP neighbor Device Inventory Number'];
        $rows = '<row r="' . $currentRow . '" spans="1:' . count($columns) . '" x14ac:dyDescent="0.25">';
        for ($i = 0; $i < count($columns); $i++) {
            $sharedStringsSI .= '<si><t>' . $header[$i] . '</t></si>';
            $rows .= '<c r="' . $columns[$i] . $currentRow . '" s="2" t="s"><v>' . $charPosition++ . '</v></c>';
        }
        $rows .= '</row>';
        $currentRow++;

        // Add Phones to the worksheet
        foreach ($phones as $phone) {

            // define sell style type for the appliance
            $styleType = $this->getPhoneSellStyleType($phone);

            // define appliance columns
            $phoneColumns = [
                $phone->lotus_regCenter,
                $phone->region,
                $phone->city,
                $phone->office,
                $phone->lotusId,
                $phone->clusterTitle,
                $phone->phone_hostname,
                $phone->appType,
                $phone->device,
                $phone->name,
                $phone->managementIp,
                $phone->publisherIp,
                $phone->publisher_description,
                $phone->partition,
                $phone->css,
                $phone->prefix,
                $phone->phoneDN,
                $phone->e164mask,
                $phone->platformSerial,
                $phone->inventoryNumber,
                $phone->responsiblePerson,
                $phone->softwareTitle,
                $phone->softwareVersion,
                $phone->appLastUpdate,
                $phone->last_call_day,
                $phone->d0_calls_amount,
                $phone->m0_calls_amount,
                $phone->m1_calls_amount,
                $phone->m2_calls_amount,
                $phone->appComment,
                $phone->phoneDescription,
                $phone->devicePool,
                $phone->alertingName,
                $phone->timezone,
                $phone->dhcpEnabled,
                $phone->dhcpServer,
                $phone->domainName,
                $phone->tftpServer1,
                $phone->tftpServer2,
                $phone->defaultRouter,
                $phone->dnsServer1,
                $phone->dnsServer2,
                $phone->callManager1,
                $phone->callManager2,
                $phone->callManager3,
                $phone->callManager4,
                $phone->vlanId,
                $phone->userLocale,
                $phone->cdpNeighborDeviceId,
                $phone->cdpNeighborIP,
                $phone->cdpNeighborPort,
                $phone->cdpNeighborDevice_title,
                $phone->cdpNeighborDevice_inventoryNumber,
            ];

            // Open row for the phone to the worksheet
            $rows .= '<row r="' . $currentRow . '" spans="1:' . count($columns) . '" x14ac:dyDescent="0.25">';

            // add first column (порядковый номер строки) to the row
            $rows .= '<c r="A' . $currentRow . '" s="' . $styleType . '"><v>' . ($currentRow - 1) . '</v></c>';

            // add phone columns to the row
            for ($i = 1; $i < count($columns); $i++) {
                $sharedStringsSI .= '<si><t>' . $phoneColumns[$i - 1] . '</t></si>';
                $rows .= '<c r="' . $columns[$i] . $currentRow . '" s="' . $styleType . '" t="s"><v>' . $charPosition++ . '</v></c>';
            }

            // Close row
            $rows .= '</row>';
            $currentRow++;
        }

        // Add worksheet to workbook
        $dimensionSheet1 = '<dimension ref="A1:' . end($columns) . ($currentRow - 1) . '"/>';
        $autoFilter = '<autoFilter ref="B1:' . end($columns) . ($currentRow - 1) . '"/>';
        $frozenPane1 = '<sheetView tabSelected="1" workbookViewId="0"><pane state="frozen" activePane="bottomLeft" topLeftCell="A2" ySplit="1"/><selection sqref="A2" activeCell="A2" pane="bottomLeft"/></sheetView>';
        $zip->addFromString('xl/worksheets/'.$sheet.'.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">' . $dimensionSheet1 . '<sheetViews>' . $frozenPane1 . '</sheetViews><sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/><sheetData>' . $rows . '</sheetData>' . $autoFilter . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
    }

    /**
     * Export Workbook to the file
     *
     * @param \ZipArchive $zip
     * @param $charPosition
     * @param $sharedStringsSI
     */
    private function exportToExcel(\ZipArchive $zip,  &$charPosition, &$sharedStringsSI)
    {
        // define worksheets amount into workbook
        $worksheetsAmount = 3;

        $sharedStringsCount = 'count="' . $charPosition . '" uniqueCount="' . $charPosition . '">';
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' . $sharedStringsCount . $sharedStringsSI . '</sst>');
        unset($sharedStringsSI);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');

        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId6" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/><Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/></Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x15" xmlns:x15="http://schemas.microsoft.com/office/spreadsheetml/2010/11/main"><fileVersion appName="xl" lastEdited="6" lowestEdited="4" rupBuild="14420"/><workbookPr filterPrivacy="1" defaultThemeVersion="124226"/><bookViews><workbookView xWindow="240" yWindow="105" windowWidth="14805" windowHeight="8010"/></bookViews>
<sheets>
    <sheet name="Appliances" sheetId="1" r:id="rId1"/> 
    <sheet name="Appliances with modules" sheetId="2" r:id="rId2"/>
    <sheet name="Phones" sheetId="3" r:id="rId3"/> 
</sheets>
<calcPr calcId="122211"/></workbook>');

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac"><fonts count="1" x14ac:knownFonts="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/><scheme val="minor"/></font></fonts><fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFFF00"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor tint="-0.14999847407452621" theme="0"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFF3B3B"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="7"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf><xf borderId="0" fillId="2" fontId="0" numFmtId="0" xfId="0" applyAlignment="1" applyFill="1"><alignment vertical="center" horizontal="left"/></xf><xf borderId="0" fillId="3" fontId="0" numFmtId="0" xfId="0" applyAlignment="1" applyFill="1"><alignment vertical="center" horizontal="left"/></xf><xf borderId="0" fillId="4" fontId="0" numFmtId="0" xfId="0" applyAlignment="1" applyFill="1"><alignment vertical="center" horizontal="left"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Обычный" xfId="0" builtinId="0"/></cellStyles><dxfs count="0"/><tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleMedium9"/><extLst><ext uri="{EB79DEF2-80B8-43e5-95BD-54CBDDF9020C}" xmlns:x14="http://schemas.microsoft.com/office/spreadsheetml/2009/9/main"><x14:slicerStyles defaultSlicerStyle="SlicerStyleLight1"/></ext><ext uri="{9260A510-F301-46a8-8635-F512D64BE5F5}" xmlns:x15="http://schemas.microsoft.com/office/spreadsheetml/2010/11/main"><x15:timelineStyles defaultTimelineStyle="TimeSlicerStyleLight1"/></ext></extLst></styleSheet>');

        $zip->addFromString('xl/theme/theme1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Тема Office"><a:themeElements><a:clrScheme name="Стандартная"><a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1><a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1><a:dk2><a:srgbClr val="1F497D"/></a:dk2><a:lt2><a:srgbClr val="EEECE1"/></a:lt2><a:accent1><a:srgbClr val="4F81BD"/></a:accent1><a:accent2><a:srgbClr val="C0504D"/></a:accent2><a:accent3><a:srgbClr val="9BBB59"/></a:accent3><a:accent4><a:srgbClr val="8064A2"/></a:accent4><a:accent5><a:srgbClr val="4BACC6"/></a:accent5><a:accent6><a:srgbClr val="F79646"/></a:accent6><a:hlink><a:srgbClr val="0000FF"/></a:hlink><a:folHlink><a:srgbClr val="800080"/></a:folHlink></a:clrScheme><a:fontScheme name="Стандартная"><a:majorFont><a:latin typeface="Cambria" panose="020F0302020204030204"/><a:ea typeface=""/><a:cs typeface=""/><a:font script="Jpan" typeface="ＭＳ Ｐゴシック"/><a:font script="Hang" typeface="맑은 고딕"/><a:font script="Hans" typeface="宋体"/><a:font script="Hant" typeface="新細明體"/><a:font script="Arab" typeface="Times New Roman"/><a:font script="Hebr" typeface="Times New Roman"/><a:font script="Thai" typeface="Tahoma"/><a:font script="Ethi" typeface="Nyala"/><a:font script="Beng" typeface="Vrinda"/><a:font script="Gujr" typeface="Shruti"/><a:font script="Khmr" typeface="MoolBoran"/><a:font script="Knda" typeface="Tunga"/><a:font script="Guru" typeface="Raavi"/><a:font script="Cans" typeface="Euphemia"/><a:font script="Cher" typeface="Plantagenet Cherokee"/><a:font script="Yiii" typeface="Microsoft Yi Baiti"/><a:font script="Tibt" typeface="Microsoft Himalaya"/><a:font script="Thaa" typeface="MV Boli"/><a:font script="Deva" typeface="Mangal"/><a:font script="Telu" typeface="Gautami"/><a:font script="Taml" typeface="Latha"/><a:font script="Syrc" typeface="Estrangelo Edessa"/><a:font script="Orya" typeface="Kalinga"/><a:font script="Mlym" typeface="Kartika"/><a:font script="Laoo" typeface="DokChampa"/><a:font script="Sinh" typeface="Iskoola Pota"/><a:font script="Mong" typeface="Mongolian Baiti"/><a:font script="Viet" typeface="Times New Roman"/><a:font script="Uigh" typeface="Microsoft Uighur"/><a:font script="Geor" typeface="Sylfaen"/></a:majorFont><a:minorFont><a:latin typeface="Calibri" panose="020F0502020204030204"/><a:ea typeface=""/><a:cs typeface=""/><a:font script="Jpan" typeface="ＭＳ Ｐゴシック"/><a:font script="Hang" typeface="맑은 고딕"/><a:font script="Hans" typeface="宋体"/><a:font script="Hant" typeface="新細明體"/><a:font script="Arab" typeface="Arial"/><a:font script="Hebr" typeface="Arial"/><a:font script="Thai" typeface="Tahoma"/><a:font script="Ethi" typeface="Nyala"/><a:font script="Beng" typeface="Vrinda"/><a:font script="Gujr" typeface="Shruti"/><a:font script="Khmr" typeface="DaunPenh"/><a:font script="Knda" typeface="Tunga"/><a:font script="Guru" typeface="Raavi"/><a:font script="Cans" typeface="Euphemia"/><a:font script="Cher" typeface="Plantagenet Cherokee"/><a:font script="Yiii" typeface="Microsoft Yi Baiti"/><a:font script="Tibt" typeface="Microsoft Himalaya"/><a:font script="Thaa" typeface="MV Boli"/><a:font script="Deva" typeface="Mangal"/><a:font script="Telu" typeface="Gautami"/><a:font script="Taml" typeface="Latha"/><a:font script="Syrc" typeface="Estrangelo Edessa"/><a:font script="Orya" typeface="Kalinga"/><a:font script="Mlym" typeface="Kartika"/><a:font script="Laoo" typeface="DokChampa"/><a:font script="Sinh" typeface="Iskoola Pota"/><a:font script="Mong" typeface="Mongolian Baiti"/><a:font script="Viet" typeface="Arial"/><a:font script="Uigh" typeface="Microsoft Uighur"/><a:font script="Geor" typeface="Sylfaen"/></a:minorFont></a:fontScheme><a:fmtScheme name="Стандартная"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="50000"/><a:satMod val="300000"/></a:schemeClr></a:gs><a:gs pos="35000"><a:schemeClr val="phClr"><a:tint val="37000"/><a:satMod val="300000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:tint val="15000"/><a:satMod val="350000"/></a:schemeClr></a:gs></a:gsLst><a:lin ang="16200000" scaled="1"/></a:gradFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:shade val="51000"/><a:satMod val="130000"/></a:schemeClr></a:gs><a:gs pos="80000"><a:schemeClr val="phClr"><a:shade val="93000"/><a:satMod val="130000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="94000"/><a:satMod val="135000"/></a:schemeClr></a:gs></a:gsLst><a:lin ang="16200000" scaled="0"/></a:gradFill></a:fillStyleLst><a:lnStyleLst><a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"><a:shade val="95000"/><a:satMod val="105000"/></a:schemeClr></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="25400" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="38100" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="20000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="38000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle><a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="23000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="35000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle><a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="23000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="35000"/></a:srgbClr></a:outerShdw></a:effectLst><a:scene3d><a:camera prst="orthographicFront"><a:rot lat="0" lon="0" rev="0"/></a:camera><a:lightRig rig="threePt" dir="t"><a:rot lat="0" lon="0" rev="1200000"/></a:lightRig></a:scene3d><a:sp3d><a:bevelT w="63500" h="25400"/></a:sp3d></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="40000"/><a:satMod val="350000"/></a:schemeClr></a:gs><a:gs pos="40000"><a:schemeClr val="phClr"><a:tint val="45000"/><a:shade val="99000"/><a:satMod val="350000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="20000"/><a:satMod val="255000"/></a:schemeClr></a:gs></a:gsLst><a:path path="circle"><a:fillToRect l="50000" t="-80000" r="50000" b="180000"/></a:path></a:gradFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="80000"/><a:satMod val="300000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="30000"/><a:satMod val="200000"/></a:schemeClr></a:gs></a:gsLst><a:path path="circle"><a:fillToRect l="50000" t="50000" r="50000" b="50000"/></a:path></a:gradFill></a:bgFillStyleLst></a:fmtScheme></a:themeElements><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>');

        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Microsoft Excel</Application><DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop><HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Листы</vt:lpstr></vt:variant><vt:variant><vt:i4>3</vt:i4></vt:variant></vt:vector></HeadingPairs>
<TitlesOfParts>
    <vt:vector size="'.$worksheetsAmount.'" baseType="lpstr">
        <vt:lpstr>Appliances</vt:lpstr> 
        <vt:lpstr>Appliances with modules</vt:lpstr>
        <vt:lpstr>Phones</vt:lpstr>  
    </vt:vector>
</TitlesOfParts><Company></Company><LinksUpToDate>false</LinksUpToDate><SharedDoc>false</SharedDoc><HyperlinksChanged>false</HyperlinksChanged><AppVersion>15.0300</AppVersion></Properties>');

        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator></dc:creator><cp:lastModifiedBy></cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2006-09-16T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2017-08-25T05:47:03Z</dcterms:modified></cp:coreProperties>');
    }
}
