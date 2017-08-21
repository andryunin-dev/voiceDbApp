<?php

namespace App\Controllers;

use App\Models\Appliance;
use App\Models\DataPort;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use T4\Mvc\Controller;

class Export extends Controller
{
    const PHONE = 'phone';
    const ROUTER = 'router';
    const SWITCH = 'switch';
    const CMP = 'cmp';
    const CMS = 'cms';
    const UPS = 'ups';
    const VG = 'vg';

    public function actionHardInvExcel()
    {
        $spreadsheet = new Spreadsheet();

// ------ Worksheet - 'Appliances' ----------------------
        $spreadsheet->getActiveSheet()->setTitle('Appliances');

        // HEADER
        $sells = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1'];
        $vals = ['№п/п', 'Регион', 'Офис', 'Hostname', 'Type', 'Device', 'Device ser', 'Software', 'Software ver.', 'Appl. last update', 'Comment'];
        for ($i = 0; $i < count($sells); $i++) {
            $spreadsheet->getActiveSheet()->setCellValue($sells[$i], $vals[$i]);
        }

        // Format
        $columns = ['A','B','C','D','E','F','G','H','I','J','K'];
        foreach ($columns as $column) {
            $spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);
        }
        $spreadsheet->getActiveSheet()->getStyle('A:K')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $spreadsheet->getActiveSheet()->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Body
        $query = 'SELECT
  region.title AS region,
  location.title AS office,
  appliance.details,
  applianceType.type,
  vendor.title AS vendor,
  platform.title AS platform,
  platformItem."serialNumber" AS platform_serialnumber,
  software.title AS soft_title,
  softwareItem.version AS soft_version,
  appliance."lastUpdate" AS appliance_lastupdate,
  appliance.comment AS appliance_comment,
  appliance."inUse" AS appliance_inuse
FROM equipment.appliances AS appliance
  INNER JOIN company.offices AS location ON location.__id = appliance.__location_id
  INNER JOIN geolocation.addresses AS address ON address.__id = location.__address_id
  INNER JOIN geolocation.cities AS city ON city.__id = address.__city_id
  INNER JOIN geolocation.regions AS region ON region.__id = city.__region_id
  INNER JOIN equipment."platformItems" AS platformItem ON platformItem.__id = appliance.__platform_item_id
  INNER JOIN equipment.platforms AS platform ON platform.__id = platformItem.__platform_id
  INNER JOIN equipment.vendors AS vendor ON vendor.__id = platform.__vendor_id
  INNER JOIN equipment."softwareItems" AS softwareItem ON softwareItem.__id = appliance.__software_item_id
  INNER JOIN equipment.software AS software ON software.__id = softwareItem.__software_id
  INNER JOIN equipment."applianceTypes" AS applianceType ON applianceType.__id = appliance.__type_id WHERE applianceType.type IN (:appType1, :appType2, :appType3, :appType4, :appType5, :appType6)';

        $params = [
            ':appType1' => self::SWITCH,
            ':appType2' => self::CMP,
            ':appType3' => self::CMS,
            ':appType4' => self::UPS,
            ':appType5' => self::VG,
            ':appType6' => self::ROUTER,
        ];

        $appliances = Appliance::findAllByQuery($query,$params);

        $data = [];
        $n = 2;
        foreach ($appliances as $appliance) {
            $data[] = [
                $n-1,
                $appliance->region,
                $appliance->office,
                $appliance->details->hostname,
                $appliance->type,
                $appliance->vendor . ' ' . $appliance->platform,
                $appliance->platform_serialnumber,
                $appliance->soft_title,
                $appliance->soft_version,
                $appliance->appliance_lastupdate,
                $appliance->appliance_comment,
            ];
            if (false === $appliance->appliance_inuse) {
                $spreadsheet->getActiveSheet()->getStyle('A' . $n . ':K' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            $n++;
        }

        $spreadsheet->getActiveSheet()->fromArray($data, NULL, 'A2');
        $spreadsheet->getActiveSheet()->getStyle('A2:K' . $n)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Autofilter
        $spreadsheet->getActiveSheet()->setAutoFilter('B1:K' . ($n-1));
        $spreadsheet->getActiveSheet()->freezePane('A2');


// ------ Worksheet - 'Appliances with modules' ----------------------
        $objWorkSheet1 = $spreadsheet->createSheet(1);
        $objWorkSheet1->setTitle('Appliances with modules');

        // HEADER
        $sells = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1','L1','M1','N1'];
        $vals = ['№п/п', 'Регион', 'Офис', 'Hostname', 'Type', 'Device', 'Device ser', 'Software', 'Software ver.', 'Appl. last update', 'Module', 'Module ser', 'Module last update', 'Comment'];
        for ($i = 0; $i < count($sells); $i++) {
            $objWorkSheet1->setCellValue($sells[$i], $vals[$i]);
        }

        // Format
        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N'];
        foreach ($columns as $column) {
            $objWorkSheet1->getColumnDimension($column)->setAutoSize(true);
        }
        $objWorkSheet1->getStyle('A:N')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $objWorkSheet1->getStyle('A1:N1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Body
        $query = 'SELECT
  region.title AS region,
  location.title AS office,
  appliance.details,
  applianceType.type,
  vendor.title AS vendor,
  platform.title AS platform,
  platformItem."serialNumber" AS platform_serialnumber,
  software.title AS soft_title,
  softwareItem.version AS soft_version,
  appliance."lastUpdate" AS appliance_lastupdate,
  appliance.comment AS appliance_comment,
  module.title AS module,
  moduleItem."serialNumber" AS module_serialnumber,
  moduleItem."lastUpdate" AS module_lastupdate,
  moduleItem.comment AS module_comment,
  moduleItem."inUse" AS module_inuse
FROM equipment.appliances AS appliance
  INNER JOIN company.offices AS location ON location.__id = appliance.__location_id
  INNER JOIN geolocation.addresses AS address ON address.__id = location.__address_id
  INNER JOIN geolocation.cities AS city ON city.__id = address.__city_id
  INNER JOIN geolocation.regions AS region ON region.__id = city.__region_id
  INNER JOIN equipment."platformItems" AS platformItem ON platformItem.__id = appliance.__platform_item_id
  INNER JOIN equipment.platforms AS platform ON platform.__id = platformItem.__platform_id
  INNER JOIN equipment.vendors AS vendor ON vendor.__id = platform.__vendor_id
  INNER JOIN equipment."softwareItems" AS softwareItem ON softwareItem.__id = appliance.__software_item_id
  INNER JOIN equipment.software AS software ON software.__id = softwareItem.__software_id
  LEFT JOIN equipment."moduleItems" AS moduleItem ON moduleItem.__appliance_id = appliance.__id
  LEFT JOIN equipment.modules AS module ON module.__id = moduleItem.__module_id
  INNER JOIN equipment."applianceTypes" AS applianceType ON applianceType.__id = appliance.__type_id WHERE applianceType.type IN (:appType1, :appType2, :appType3, :appType4, :appType5, :appType6)';

        $params = [
            ':appType1' => self::SWITCH,
            ':appType2' => self::CMP,
            ':appType3' => self::CMS,
            ':appType4' => self::UPS,
            ':appType5' => self::VG,
            ':appType6' => self::ROUTER,
        ];

        $appliances = Appliance::findAllByQuery($query,$params);

        $data = [];
        $n = 2;
        foreach ($appliances as $appliance) {
            $data[] = [
                $n-1,
                $appliance->region,
                $appliance->office,
                $appliance->details->hostname,
                $appliance->type,
                $appliance->vendor . ' ' . $appliance->platform,
                $appliance->platform_serialnumber,
                $appliance->soft_title,
                $appliance->soft_version,
                $appliance->module,
                $appliance->module_serialnumber,
                $appliance->module_lastupdate,
                $appliance->module_comment
            ];
            if (false === $appliance->module_inuse) {
                $objWorkSheet1->getStyle('K' . $n . ':N' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            $n++;
        }

        $objWorkSheet1->fromArray($data, NULL, 'A2');
        $objWorkSheet1->getStyle('A2:N' . $n)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Autofilter
        $objWorkSheet1->setAutoFilter('B1:N' . ($n-1));
        $objWorkSheet1->freezePane('A2');


// ------ Worksheet - 'Phones' ----------------------
        $objWorkSheet2 = $spreadsheet->createSheet(2);
        $objWorkSheet2->setTitle('Phones');

        // HEADER
        $sells = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1','L1','M1','N1','O1','P1','Q1','R1','S1','T1','U1','V1','W1','X1','Y1','Z1','AA1','AB1','AC1','AD1','AE1','AF1','AG1','AH1','AI1','AJ1','AK1','AL1'];

        $vals = ['№п/п', 'Регион', 'Офис', 'Publisher', 'Device', 'Name', 'IP', 'Partion', 'CSS', 'Prefix', 'DN', 'Status', 'Device ser', 'Software', 'Software ver.', 'Last update', 'Comment', 'Description', 'Device Pool', 'Alerting Name', 'Timezone', 'DHCP enable', 'DHCP server', 'Domain name', 'TFTP server 1', 'TFTP server 2', 'Default Router', 'DNS server 1', 'DNS server 2', 'Call manager 1', 'Call manager 2', 'Call manager 3', 'Call manager 4', 'VLAN ID', 'User locale', 'CDP neighbor device ID', 'CDP neighbor IP', 'CDP neighbor Port'];

        for ($i = 0; $i < count($sells); $i++) {
            $objWorkSheet2->setCellValue($sells[$i], $vals[$i]);
        }

        // Format
        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL'];
        foreach ($columns as $column) {
            $objWorkSheet2->getColumnDimension($column)->setAutoSize(true);
        }
        $objWorkSheet2->getStyle('A:AL')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $objWorkSheet2->getStyle('A1:AL1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Body
        $query = 'SELECT
  region.title            AS region,
  location.title          AS office,
  phoneInfo."publisherIp" AS publisher,
  phoneInfo.model         AS device,
  phoneInfo.name,
  dataPort."ipAddress"    AS ip,
  phoneInfo.partition,
  phoneInfo.css,
  phoneInfo.prefix,
  phoneInfo."phoneDN" as dn,
  phoneInfo.status,
  platform."serialNumber",
  software.title          AS softTitle,
  softwareItem.version    AS softVersion,
  appliance."lastUpdate",
  appliance."comment",
  phoneInfo.description,
  phoneInfo."devicePool",
  phoneInfo."alertingName",
  phoneInfo.timezone,
  phoneInfo."dhcpEnabled",
  phoneInfo."dhcpServer",
  phoneInfo."domainName",
  phoneInfo."tftpServer1",
  phoneInfo."tftpServer2",
  phoneInfo."defaultRouter",
  phoneInfo."dnsServer1",
  phoneInfo."dnsServer2",
  phoneInfo."callManager1",
  phoneInfo."callManager2",
  phoneInfo."callManager3",
  phoneInfo."callManager4",
  phoneInfo."vlanId",
  phoneInfo."userLocale",
  phoneInfo."cdpNeighborDeviceId",
  phoneInfo."cdpNeighborIP",
  phoneInfo."cdpNeighborPort",
  appliance."inUse"
FROM equipment.appliances AS appliance
  INNER JOIN equipment."applianceTypes" AS applType ON applType.__id = appliance.__type_id AND applType.type = :appType
  INNER JOIN equipment."phoneInfo" AS phoneInfo ON phoneInfo.__appliance_id = appliance.__id AND phoneInfo.status = :appStatus
  LEFT JOIN equipment."dataPorts" AS dataPort ON dataPort.__appliance_id = appliance.__id
  INNER JOIN company.offices AS location ON location.__id = appliance.__location_id
  INNER JOIN geolocation.addresses AS address ON address.__id = location.__address_id
  INNER JOIN geolocation.cities AS city ON city.__id = address.__city_id
  INNER JOIN geolocation.regions AS region ON region.__id = city.__region_id
  INNER JOIN equipment."platformItems" AS platform ON platform.__id = appliance.__platform_item_id
  INNER JOIN equipment."softwareItems" AS softwareItem ON softwareItem.__id = appliance.__software_item_id
  INNER JOIN equipment.software AS software ON software.__id = softwareItem.__software_id';

        $params = [
            ':appType' => self::PHONE,
            ':appStatus' => 'Registered'
        ];

        $appliances = Appliance::findAllByQuery($query,$params);

        $data = [];
        $n = 2;
        foreach ($appliances as $appliance) {
            $data[] = [
                $n-1,
                $appliance->region,
                $appliance->office,
                $appliance->publisher,
                $appliance->device,
                $appliance->name,
                $appliance->ip,
                $appliance->partition,
                $appliance->css,
                $appliance->prefix,
                $appliance->dn,
                $appliance->status,
                $appliance->serialNumber,
                $appliance->softtitle,
                $appliance->softversion,
                $appliance->lastUpdat,
                $appliance->comment,
                $appliance->description,
                $appliance->devicePool,
                $appliance->alertingName,
                $appliance->timezone,
                $appliance->dhcpEnabled,
                $appliance->dhcpServer,
                $appliance->domainName,
                $appliance->tftpServer1,
                $appliance->tftpServer2,
                $appliance->defaultRouter,
                $appliance->dnsServer1,
                $appliance->dnsServer2,
                $appliance->callManager1,
                $appliance->callManager2,
                $appliance->callManager3,
                $appliance->callManager4,
                $appliance->vlanId,
                $appliance->userLocale,
                $appliance->cdpNeighborDeviceId,
                $appliance->cdpNeighborIP,
                $appliance->cdpNeighborPort,
            ];
            if (false === $appliance->inUse) {
                $objWorkSheet2->getStyle('A' . $n . ':AL' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            $n++;
        }

        $objWorkSheet2->fromArray($data, NULL, 'A2');
        $objWorkSheet2->getStyle('A2:AL' . $n)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);


            // Autofilter
        $objWorkSheet2->setAutoFilter('B1:AL' . ($n-1));
        $objWorkSheet2->freezePane('A2');


// ------ Export ----------------------
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Hard inventory__'. gmdate('d M Y') . '.xlsx"');
        header('Cache-Control: max-age=0');

        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');

        exit;
    }


    public function actionIpAppliances()
    {
        $switch = 'switch';
        $router = 'router';
        $vg = 'vg';
        $dataports = (DataPort::findAllByColumn('isManagement', true))->filter(
            function ($dataport) use ($router, $switch, $vg) {
                return $router == $dataport->appliance->type->type || $switch == $dataport->appliance->type->type || $vg == $dataport->appliance->type->type;
            }
        );

        // Semicolon format
        $outputData = '';
        foreach ($dataports as $dataport) {
            $outputData .= $dataport->appliance->details->hostname . ',' . preg_replace('~/.+~', '', $dataport->ipAddress) . ',' . $dataport->appliance->location->lotusId . ';';
        }
        echo $outputData;

        die;
    }

    public function actionIpCucm()
    {
        $cucm = 'cucm';

        $dataports = (DataPort::findAllByColumn('isManagement', true))->filter(
            function ($dataport) use ($cucm) {
                return $cucm == $dataport->appliance->type->type;
            }
        );

        // Semicolon format
        $outputData = '';
        foreach ($dataports as $dataport) {
            $outputData .= $dataport->appliance->details->hostname . ',' . preg_replace('~/.+~', '', $dataport->ipAddress) . ',' . $dataport->appliance->location->lotusId . ';';
        }
        echo $outputData;

        die;
    }
}
