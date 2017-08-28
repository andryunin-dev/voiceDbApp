<?php
namespace App\Commands;

use App\Components\RLogger;
use App\Models\Appliance;
use App\Models\Phone;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet;
use T4\Console\Command;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\MultiException;
use ZipArchive;

class CucmsPhones extends Command
{
    const PUBLISHER = 'cmp';
    const PHONE = 'phone';
    const ROUTER = 'router';
    const SWITCH = 'switch';
    const CMP = 'cmp';
    const CMS = 'cms';
    const UPS = 'ups';
    const VG = 'vg';

    public function actionDefault()
    {
        $this->actionGetAll();
        $this->actionSave();
    }


    public function actionGetAll()
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));
        file_put_contents($this->getBackupFileName(),'');

        // Получить все зарегистрированные телефоны из всех cucms
        foreach (Appliance::findAllByType(self::PUBLISHER) as $publisher) {
            $cucmIp = $publisher->managementIp;

            $logger->info('START:[cucm]=' . $cucmIp);

            try {

                $registeredPhones = Phone::findAllRegisteredIntoCucm($cucmIp);

                // Backup registered phones
                $fd = fopen($this->getBackupFileName(), 'a');
                foreach ($registeredPhones as $phone) {
                    fwrite($fd, json_encode($phone->getData()) . PHP_EOL);
                }
                fclose($fd);

            } catch (MultiException $errs) {
                foreach ($errs as $e) {
                    $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
                }
            } catch (Exception $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            } catch (\SoapFault $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            }

            $logger->info('----- Found registered phones: ' . $registeredPhones->count());
            $logger->info('END:[cucm]=' . $cucmIp);
        }

        $this->writeLn('Get phones from all cucms - OK');
    }


    public function actionSave()
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        $backupFile = $this->getBackupFileName();
        if (is_readable($backupFile)) {
            $phonesData = explode(PHP_EOL, file_get_contents($this->getBackupFileName()));
            foreach ($phonesData as $phoneData) {
                try {
                    (new Phone())
                        ->fill(json_decode($phoneData))
                        ->save();
                } catch (Exception $e) {
                    $logger->error('UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData));
                    echo 'UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData) . PHP_EOL;
                }
            }

            $this->writeLn('Save phones - OK');
        } else {
            $this->writeLn('Save phones - ERROR');
        }
    }


    protected function getBackupFileName(string $cucmIP = null)
    {
        if (null == $cucmIP) {
            return ROOT_PATH . DS . 'Backups' . DS . 'backup_phones.txt';
        } else {
            return ROOT_PATH . DS . 'Backups' . DS . 'backup_phones_' . preg_replace('~\.~', '_', $cucmIP) . '.txt';
        }
    }


    /**
     * @param string $cucmIp
     *
     * !!! В командной строке $cucmIp передавать без кавычек
     */
    public function actionGetFrom(string $cucmIp)
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));
        file_put_contents($this->getBackupFileName($cucmIp),'');

        $publisher = (Appliance::findAllByType(self::PUBLISHER))->filter(
            function ($publisher) use ($cucmIp) {
                return $cucmIp == $publisher->getManagementIp();
            }
        )->first();

        if (null != $publisher) {
            $logger->info('START:[cucm]=' . $cucmIp);

            try {

                $registeredPhones = Phone::findAllRegisteredIntoCucm($cucmIp);

                // Backup registered phones
                $fd = fopen($this->getBackupFileName($cucmIp), 'a');
                foreach ($registeredPhones as $phone) {
                    fwrite($fd, json_encode($phone->getData()) . PHP_EOL);
                }
                fclose($fd);

            } catch (MultiException $errs) {
                foreach ($errs as $e) {
                    $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
                }
            } catch (Exception $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            } catch (\SoapFault $e) {
                $logger->error('[cucm]=' . $cucmIp . '; [message]=' . ($e->getMessage() ?? '""'));
            }

            $logger->info('----- Found registered phones: ' . $registeredPhones->count());
            $logger->info('END:[cucm]=' . $cucmIp);
            $this->writeLn('Get phones from cucms ' . $cucmIp . ' - OK');

        } else {
            $this->writeLn('Cucm ' . $cucmIp . ' does not found');
        }
    }


    public function actionSaveFrom(string $cucmIp)
    {
        $logger = RLogger::getInstance('Cucm', realpath(ROOT_PATH . '/Logs/phones.log'));

        $backupFile = $this->getBackupFileName($cucmIp);
        if (is_readable($backupFile)) {
            $phonesData = explode(PHP_EOL, file_get_contents($backupFile));
            foreach ($phonesData as $phoneData) {
                try {
                    (new Phone())
                        ->fill(json_decode($phoneData))
                        ->save();
                } catch (Exception $e) {
                    $logger->error('UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData));
                    echo 'UPDATE PHONE: [name]=' . $phoneData->name . '; [ip]=' . $phoneData->ipAddress . '; [cucm]=' . $phoneData->cucmIpAddress . '; [message]=' . ($e->getMessage() ?? '""') . '; [data]=' . json_encode($phoneData) . PHP_EOL;
                }
            }

            $this->writeLn('Save phones from ' . $cucmIp . ' - OK');
        } else {
            $this->writeLn('Save phones from ' . $cucmIp . ' - ERROR');
        }
    }






    public function actionExportToExcel()
    {
        $startTotal = microtime(true);

        $spreadsheet = new Spreadsheet();
        $workSheet = $spreadsheet->getActiveSheet();

        $workSheet->setTitle('Phones');

        // HEADER
        $sells = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1','L1','M1','N1','O1','P1','Q1','R1','S1','T1','U1','V1','W1','X1','Y1','Z1','AA1','AB1','AC1','AD1','AE1','AF1','AG1','AH1','AI1','AJ1','AK1','AL1'];

        $vals = ['№п/п', 'Регион', 'Офис', 'Publisher', 'Device', 'Name', 'IP', 'Partion', 'CSS', 'Prefix', 'DN', 'Status', 'Device ser', 'Software', 'Software ver.', 'Last update', 'Comment', 'Description', 'Device Pool', 'Alerting Name', 'Timezone', 'DHCP enable', 'DHCP server', 'Domain name', 'TFTP server 1', 'TFTP server 2', 'Default Router', 'DNS server 1', 'DNS server 2', 'Call manager 1', 'Call manager 2', 'Call manager 3', 'Call manager 4', 'VLAN ID', 'User locale', 'CDP neighbor device ID', 'CDP neighbor IP', 'CDP neighbor Port'];

        for ($i = 0; $i < count($sells); $i++) {
            $workSheet->setCellValue($sells[$i], $vals[$i]);
        }

        // Format
        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL'];
        foreach ($columns as $column) {
            $workSheet->getColumnDimension($column)->setAutoSize(true);
        }
        $workSheet->getStyle('A:AL')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $workSheet->getStyle('A1:AL1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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

$start = microtime(true);
        $appliances = Appliance::findAllByQuery($query,$params);
echo 'Query: ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Responce: count of the phones: ' . $appliances->count() . PHP_EOL . PHP_EOL;


        $fields = ['','region','office','publisher','device','name','ip','partition','css','prefix','dn','status','serialNumber','softtitle','softversion','lastUpdate','comment','description','devicePool','alertingName','timezone','dhcpEnabled','dhcpServer','domainName','tftpServer1','tftpServer2','defaultRouter','dnsServer1','dnsServer2','callManager1','callManager2','callManager3','callManager4','vlanId','userLocale','cdpNeighborDeviceId','cdpNeighborIP','cdpNeighborPort',
        ];
        $maxColumn = count($fields);
        $n = 2;

$start = microtime(true);
        foreach ($appliances as $appliance) {

// TODO remember - delete this
            if (12 == $n) {
                break;
            }

            for ($column = 0; $column < $maxColumn; $column++) {
                if (0 === $column) {
                    $workSheet->setCellValueByColumnAndRow($column, $n, $n - 1);
                }
                if (!is_null($appliance[$fields[$column]]) && 0 !== $column) {
                    $workSheet->setCellValueByColumnAndRow($column, $n, $appliance[$fields[$column]]);
                }
            }



//            foreach ($columns as $key => $columnName) {
//                if (0 === $key) {
//                    $workSheet->setCellValue($columnName . $n, $n - 1);
//                }
//                if (!is_null($appliance[$fields[$key]]) && 0 !== $key) {
//                    $workSheet->setCellValue($columnName . $n, $appliance[$fields[$key]]);
//                }
//            }


            if (false === $appliance->inUse) {
                $workSheet->getStyle('A' . $n . ':AL' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            $n++;
        }
echo 'Get Value - Time: ' . Worksheet::$getSellTime . ' sec' . PHP_EOL;
echo 'Set Value - Time: ' . Worksheet::$setValueTime . ' sec' . PHP_EOL;
echo 'Set cell value: ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Count of the phones to array: ' . ($n - 2) . PHP_EOL . PHP_EOL;


//$start = microtime(true);
//        $workSheet->getStyle('A2:AL' . $n)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
//echo  'Get Style: ' . (microtime(true) - $start) . ' sec' . PHP_EOL . PHP_EOL;


        // Autofilter
        $workSheet->setAutoFilter('B1:AL' . ($n-1));
        $workSheet->freezePane('A2');


// ------ Export ----------------------
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

$start = microtime(true);
        $writer->save('D:' . DS . 'ExportExcel' . DS . 'Hard inventory__' . gmdate('d M Y') . '.xlsx');
echo 'Export: ' . (microtime(true) - $start) . ' sec' . PHP_EOL . PHP_EOL;


        $this->writeLn('Total: ' . (microtime(true) - $startTotal) . ' sec');
//        $this->writeLn('Excel export - OK');
    }








    public function actionExportAllToExcel()
    {
        $startTotal = microtime(true);
        $spreadsheet = new Spreadsheet();

// ------ Worksheet - 'Appliances' ----------------------
        $workSheet1 = $spreadsheet->getActiveSheet();
        $workSheet1->setTitle('Appliances');

        // HEADER
        $sells = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1'];
        $vals = ['№п/п', 'Регион', 'Офис', 'Hostname', 'Type', 'Device', 'Device ser', 'Software', 'Software ver.', 'Appl. last update', 'Comment'];
        for ($i = 0; $i < count($sells); $i++) {
            $workSheet1->setCellValue($sells[$i], $vals[$i]);
        }

        // Format
        $columns = ['A','B','C','D','E','F','G','H','I','J','K'];
        foreach ($columns as $column) {
            $workSheet1->getColumnDimension($column)->setAutoSize(true);
        }
        $workSheet1->getStyle('A:K')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $workSheet1->getStyle('A1:K1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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


$start = microtime(true);
        $appliances = Appliance::findAllByQuery($query,$params);
echo 'Query (Appliances): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Responce: count of the phones: ' . $appliances->count() . PHP_EOL . PHP_EOL;


        $n = 2;
        $fields = ['','region','office','details','type','platform','platform_serialnumber','soft_title','soft_version','appliance_lastupdate','appliance_comment'];

        $start = microtime(true);
        foreach ($appliances as $appliance) {


// TODO remember - delete this
            if (12 == $n) {
                break;
            }


            foreach ($columns as $key => $columnName) {
                if (0 === $key) {
                    $workSheet1->setCellValue($columnName . $n, $n - 1);
                }
                if (3 === $key) {
                    $workSheet1->setCellValue($columnName . $n, $appliance->details->hostname);
                }
                if (5 === $key) {
                    $workSheet1->setCellValue($columnName . $n, $appliance->vendor . ' ' . $appliance->platform);
                }
                if (!is_null($appliance[$fields[$key]]) && 3 != $key && 5 != $key) {
                    $workSheet1->setCellValue($columnName . $n, $appliance[$fields[$key]]);
                }
            }

            if (false === $appliance->inuse) {
                $workSheet1->getStyle('A' . $n . ':K' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            $n++;
        }
echo 'Set cell value (Appliances): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Count of the Appliances to array: ' . ($n - 2) . PHP_EOL . PHP_EOL;


        // Autofilter
        $workSheet1->setAutoFilter('B1:K' . ($n-1));
        $workSheet1->freezePane('A2');


// ------ Worksheet - 'Appliances with modules' ----------------------
        $workSheet2 = $spreadsheet->createSheet(1);
        $workSheet2->setTitle('Appliances with modules');

        // HEADER
        $sells = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1','L1','M1','N1'];
        $vals = ['№п/п', 'Регион', 'Офис', 'Hostname', 'Type', 'Device', 'Device ser', 'Software', 'Software ver.', 'Appl. last update', 'Module', 'Module ser', 'Module last update', 'Comment'];
        for ($i = 0; $i < count($sells); $i++) {
            $workSheet2->setCellValue($sells[$i], $vals[$i]);
        }

        // Format
        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N'];
        foreach ($columns as $column) {
            $workSheet2->getColumnDimension($column)->setAutoSize(true);
        }
        $workSheet2->getStyle('A:N')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $workSheet2->getStyle('A1:N1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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

$start = microtime(true);
        $appliances = Appliance::findAllByQuery($query,$params);
echo 'Query (Appliances with modules): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Responce: count of the phones: ' . $appliances->count() . PHP_EOL . PHP_EOL;


        $n = 2;
        $fields = ['','region','office','hostname','type','platform','platform_serialnumber','soft_title','soft_version','module','module_serialnumber','module_lastupdate','module_comment'];


$start = microtime(true);
        foreach ($appliances as $appliance) {


// TODO remember - delete this
            if (12 == $n) {
                break;
            }


            foreach ($columns as $key => $columnName) {
                if (0 === $key) {
                    $workSheet2->setCellValue($columnName . $n, $n - 1);
                }
                if (3 === $key) {
                    $workSheet2->setCellValue($columnName . $n, $appliance->details->hostname);
                }
                if (5 === $key) {
                    $workSheet2->setCellValue($columnName . $n, $appliance->vendor . ' ' . $appliance->platform);
                }
                if (!is_null($appliance[$fields[$key]]) && 3 != $key && 5 != $key) {
                    $workSheet2->setCellValue($columnName . $n, $appliance[$fields[$key]]);
                }
            }

            if (false === $appliance->module_inuse) {
                $workSheet2->getStyle('K' . $n . ':N' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            $n++;
        }
echo 'Set cell value (Appliances with modules): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Count of the Appliances to array: ' . ($n - 2) . PHP_EOL . PHP_EOL;


        // Autofilter
        $workSheet2->setAutoFilter('B1:N' . ($n-1));
        $workSheet2->freezePane('A2');


// ------ Worksheet - 'Phones' ----------------------
        $workSheet3 = $spreadsheet->createSheet(2);

        $workSheet3->setTitle('Phones');

        // HEADER
        $sells = ['A1','B1','C1','D1','E1','F1','G1','H1','I1','J1','K1','L1','M1','N1','O1','P1','Q1','R1','S1','T1','U1','V1','W1','X1','Y1','Z1','AA1','AB1','AC1','AD1','AE1','AF1','AG1','AH1','AI1','AJ1','AK1','AL1'];

        $vals = ['№п/п', 'Регион', 'Офис', 'Publisher', 'Device', 'Name', 'IP', 'Partion', 'CSS', 'Prefix', 'DN', 'Status', 'Device ser', 'Software', 'Software ver.', 'Last update', 'Comment', 'Description', 'Device Pool', 'Alerting Name', 'Timezone', 'DHCP enable', 'DHCP server', 'Domain name', 'TFTP server 1', 'TFTP server 2', 'Default Router', 'DNS server 1', 'DNS server 2', 'Call manager 1', 'Call manager 2', 'Call manager 3', 'Call manager 4', 'VLAN ID', 'User locale', 'CDP neighbor device ID', 'CDP neighbor IP', 'CDP neighbor Port'];

        for ($i = 0; $i < count($sells); $i++) {
            $workSheet3->setCellValue($sells[$i], $vals[$i]);
        }

        // Format
        $columns = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL'];
        foreach ($columns as $column) {
            $workSheet3->getColumnDimension($column)->setAutoSize(true);
        }
        $workSheet3->getStyle('A:AL')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $workSheet3->getStyle('A1:AL1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

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

$start = microtime(true);
        $appliances = Appliance::findAllByQuery($query,$params);
echo 'Query (Phones): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Responce: count of the phones: ' . $appliances->count() . PHP_EOL . PHP_EOL;


        $n = 2;
        $fields = ['','region','office','publisher','device','name','ip','partition','css','prefix','dn','status','serialNumber','softtitle','softversion','lastUpdate','comment','description','devicePool','alertingName','timezone','dhcpEnabled','dhcpServer','domainName','tftpServer1','tftpServer2','defaultRouter','dnsServer1','dnsServer2','callManager1','callManager2','callManager3','callManager4','vlanId','userLocale','cdpNeighborDeviceId','cdpNeighborIP','cdpNeighborPort',
        ];


        $start = microtime(true);
        foreach ($appliances as $appliance) {

// TODO remember - delete this
            if (12 == $n) {
                break;
            }

            foreach ($columns as $key => $columnName) {
                if (0 === $key) {
                    $workSheet3->setCellValue($columnName . $n, $n - 1);
                }

                if (!is_null($appliance[$fields[$key]])) {
                    $workSheet3->setCellValue($columnName . $n, $appliance[$fields[$key]]);
                }
            }

            if (false === $appliance->inUse) {
                $workSheet3->getStyle('A' . $n . ':AL' . $n)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(Color::COLOR_YELLOW);
            }
            $n++;
        }
        echo 'Set cell value (Phones): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
        echo 'Count of the phones to array: ' . ($n - 2) . PHP_EOL . PHP_EOL;


        // Autofilter
        $workSheet3->setAutoFilter('B1:AL' . ($n-1));
        $workSheet3->freezePane('A2');


// ------ Export ----------------------
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        $start = microtime(true);
        $writer->save('D:' . DS . 'ExportExcel' . DS . 'Hard inventory__' . gmdate('d M Y') . '.xlsx');
        echo 'Export: ' . (microtime(true) - $start) . ' sec' . PHP_EOL . PHP_EOL;


        $this->writeLn('Total: ' . (microtime(true) - $startTotal) . ' sec');
//        $this->writeLn('Excel export - OK');
    }




    public function actionTest()
    {
        $startTotal = microtime(true);

        $pFilename = ROOT_PATH . DS . 'Logs' . DS . 'tempAppliancesToExcel.xlsx';
        $zip = new ZipArchive();
        if (file_exists($pFilename)) {
            unlink($pFilename);
        }
        // Try opening the ZIP file
        if ($zip->open($pFilename, ZipArchive::OVERWRITE) !== true) {
            if ($zip->open($pFilename, ZipArchive::CREATE) !== true) {
                throw new Exception('Could not open ' . $pFilename . ' for writing.');
            }
        }


// ------ Worksheet - 'Appliances' ----------------------
        // Header sheet 1
        $headerSheet1 = ['№п/п','Регион','Офис','Hostname','Type','Device','Device ser','Software','Software ver.','Appl. last update','Comment'];
        $columnsSheet1 = ['A','B','C','D','E','F','G','H','I','J','K'];
        $rowSpansSheet1 = count($columnsSheet1);

        $currentRowSheet1 = 1;
        $charPosition = 0;
        $sharedStringsSI = '';

        $sheet1_rows = '<row r="' . $currentRowSheet1 . '" spans="1:' . $rowSpansSheet1 . '" x14ac:dyDescent="0.25">';
        for ($i = 0; $i < $rowSpansSheet1; $i++) {
            $sharedStringsSI .= '<si><t>' . $headerSheet1[$i] . '</t></si>';
            $sheet1_rows .= '<c r="' . $columnsSheet1[$i] . $currentRowSheet1 . '" s="2" t="s"><v>' . $charPosition++ . '</v></c>';
        }
        $sheet1_rows .= '</row>';
        $currentRowSheet1++;


        // Body sheet 1
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

$start = microtime(true);
        $appliances = Appliance::findAllByQuery($query,$params);
echo 'Query (Appliances): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Responce: count of the phones: ' . $appliances->count() . PHP_EOL;



$start = microtime(true);
        $fields = ['','region','office','details','type','platform','platform_serialnumber','soft_title','soft_version','appliance_lastupdate','appliance_comment'];
        foreach ($appliances as $appliance) {

// TODO remember - delete this
            if (10002 == $currentRowSheet1) {
                break;
            }

            $styleType = (false === $appliance->appliance_inuse) ? 4 : 3;

            $sheet1_rows .= '<row r="' . $currentRowSheet1 . '" spans="1:' . $rowSpansSheet1 . '" x14ac:dyDescent="0.25">';
            for ($column = 0; $column < $rowSpansSheet1; $column++) {
                if (0 === $column) {
                    $sheet1_rows .= '<c r="' . $columnsSheet1[$column] . $currentRowSheet1 . '" s="' . $styleType . '"><v>' . ($currentRowSheet1 - 1) . '</v></c>';
                    continue;
                }
                switch ($column) {
                    case 3:
                        $sharedStringsSI .= '<si><t>' . $appliance->details->hostname . '</t></si>';
                        break;
                    case 5:
                        $sharedStringsSI .= '<si><t>' . $appliance->vendor . ' ' . $appliance->platform . '</t></si>';
                        break;
                    default:
                        $sharedStringsSI .= '<si><t>' . $appliance[$fields[$column]] . '</t></si>';
                }
                $sheet1_rows .= '<c r="' . $columnsSheet1[$column] . $currentRowSheet1 . '" s="' . $styleType . '" t="s"><v>' . $charPosition++ . '</v></c>';
            }
            $sheet1_rows .= '</row>';
            $currentRowSheet1++;
        }
echo 'Set cell value (Appliances): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
echo 'Count of the Appliances to excel: ' . ($currentRowSheet1 - 2) . PHP_EOL . PHP_EOL;

        $dimensionSheet1 = '<dimension ref="A1:' . end($columnsSheet1) . ($currentRowSheet1 - 1). '"/>';
        $autoFilter1 = '<autoFilter ref="B1:' . end($columnsSheet1) . ($currentRowSheet1 - 1) . '"/>';
        $frozenPane1 = '<sheetView tabSelected="1" workbookViewId="0"><pane state="frozen" activePane="bottomLeft" topLeftCell="A2" ySplit="1"/><selection sqref="A2" activeCell="A2" pane="bottomLeft"/></sheetView>';
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">' . $dimensionSheet1 . '<sheetViews>' . $frozenPane1 . '</sheetViews><sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/><sheetData>' . $sheet1_rows . '</sheetData>' . $autoFilter1 . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
        unset($sheet1_rows);



// ------ Worksheet - 'Appliances with modules' ----------------------
        // Header sheet 1
        $headerSheet2 = ['№п/п','Регион','Офис','Hostname','Type','Device','Device ser','Software','Software ver.','Appl. last update','Module','Module ser','Module last update','Comment'];
        $columnsSheet2 = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N'];
        $rowSpansSheet2 = count($columnsSheet2);

        $currentRowSheet2 = 1;
        $sheet2_rows = '<row r="' . $currentRowSheet2 . '" spans="1:' . $rowSpansSheet2 . '" x14ac:dyDescent="0.25">';
        for ($i = 0; $i < $rowSpansSheet2; $i++) {
            $sharedStringsSI .= '<si><t>' . $headerSheet2[$i] . '</t></si>';
            $sheet2_rows .= '<c r="' . $columnsSheet2[$i] . $currentRowSheet2 . '" s="2" t="s"><v>' . $charPosition++ . '</v></c>';
        }
        $sheet2_rows .= '</row>';
        $currentRowSheet2++;


        // Body sheet 1
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

        $start = microtime(true);
        $appliances = Appliance::findAllByQuery($query,$params);
        echo 'Query (Appliances with modules): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
        echo 'Responce: count of the modules: ' . $appliances->count() . PHP_EOL;



        $start = microtime(true);
        $fields = ['','region','office','hostname','type','platform','platform_serialnumber','soft_title','soft_version','module','module_serialnumber','module_lastupdate','module_comment'];
        foreach ($appliances as $appliance) {

// TODO remember - delete this
            if (10002 == $currentRowSheet2) {
                break;
            }

            $styleType = (false === $appliance->module_inuse) ? 4 : 3;

            $sheet2_rows .= '<row r="' . $currentRowSheet2 . '" spans="1:' . $rowSpansSheet2 . '" x14ac:dyDescent="0.25">';
            for ($column = 0; $column < $rowSpansSheet2; $column++) {
                if (0 === $column) {
                    $sheet2_rows .= '<c r="' . $columnsSheet2[$column] . $currentRowSheet2 . '" s="' . $styleType . '"><v>' . ($currentRowSheet2 - 1) . '</v></c>';
                    continue;
                }
                switch ($column) {
                    case 3:
                        $sharedStringsSI .= '<si><t>' . $appliance->details->hostname . '</t></si>';
                        break;
                    case 5:
                        $sharedStringsSI .= '<si><t>' . $appliance->vendor . ' ' . $appliance->platform . '</t></si>';
                        break;
                    default:
                        $sharedStringsSI .= '<si><t>' . $appliance[$fields[$column]] . '</t></si>';
                }
                $sheet2_rows .= '<c r="' . $columnsSheet2[$column] . $currentRowSheet2 . '" s="' . $styleType . '" t="s"><v>' . $charPosition++ . '</v></c>';
            }
            $sheet2_rows .= '</row>';
            $currentRowSheet2++;
        }
        echo 'Set cell value (Appliances with modules): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
        echo 'Count of the modules to excel: ' . ($currentRowSheet2 - 2) . PHP_EOL . PHP_EOL;

        $dimensionSheet2 = '<dimension ref="A1:' . end($columnsSheet2) . ($currentRowSheet2 - 1). '"/>';
        $autoFilter2 = '<autoFilter ref="B1:' . end($columnsSheet2) . ($currentRowSheet2 - 1) . '"/>';
        $frozenPane2 = '<sheetView tabSelected="2" workbookViewId="0"><pane state="frozen" activePane="bottomLeft" topLeftCell="A2" ySplit="1"/><selection sqref="A2" activeCell="A2" pane="bottomLeft"/></sheetView>';
        $zip->addFromString('xl/worksheets/sheet2.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">' . $dimensionSheet2 . '<sheetViews>' . $frozenPane2 . '</sheetViews><sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/><sheetData>' . $sheet2_rows . '</sheetData>' . $autoFilter2 . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
        unset($sheet2_rows);




// ------ Worksheet - 'Phones' ----------------------
// Header sheet 1
        $headerSheet3 = ['№п/п','Регион','Офис','Publisher','Device','Name','IP','Partion','CSS','Prefix','DN','Status','Device ser','Software','Software ver.','Last update','Comment','Description','Device Pool','Alerting Name','Timezone','DHCP enable','DHCP server','Domain name','TFTP server 1','TFTP server 2','Default Router','DNS server 1','DNS server 2','Call manager 1','Call manager 2','Call manager 3','Call manager 4','VLAN ID','User locale','CDP neighbor device ID','CDP neighbor IP','CDP neighbor Port'];
        $columnsSheet3 = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL'];
        $rowSpansSheet3 = count($columnsSheet3);

        $currentRowSheet3 = 1;
        $sheet3_rows = '<row r="' . $currentRowSheet3 . '" spans="1:' . $rowSpansSheet3 . '" x14ac:dyDescent="0.25">';
        for ($i = 0; $i < $rowSpansSheet3; $i++) {
            $sharedStringsSI .= '<si><t>' . $headerSheet3[$i] . '</t></si>';
            $sheet3_rows .= '<c r="' . $columnsSheet3[$i] . $currentRowSheet3 . '" s="2" t="s"><v>' . $charPosition++ . '</v></c>';
        }
        $sheet3_rows .= '</row>';
        $currentRowSheet3++;


        // Body sheet 1
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

        $start = microtime(true);
        $appliances = Appliance::findAllByQuery($query,$params);
        echo 'Query (Phones): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
        echo 'Responce: count of the phones: ' . $appliances->count() . PHP_EOL;



        $start = microtime(true);
        $fields = ['','region','office','publisher','device','name','ip','partition','css','prefix','dn','status','serialNumber','softtitle','softversion','lastUpdate','comment','description','devicePool','alertingName','timezone','dhcpEnabled','dhcpServer','domainName','tftpServer1','tftpServer2','defaultRouter','dnsServer1','dnsServer2','callManager1','callManager2','callManager3','callManager4','vlanId','userLocale','cdpNeighborDeviceId','cdpNeighborIP','cdpNeighborPort'];

        foreach ($appliances as $appliance) {

// TODO remember - delete this
            if (10002 == $currentRowSheet3) {
                break;
            }

            $styleType = (false === $appliance->inUse) ? 4 : 3;

            $sheet3_rows .= '<row r="' . $currentRowSheet3 . '" spans="1:' . $rowSpansSheet3 . '" x14ac:dyDescent="0.25">';
            for ($column = 0; $column < $rowSpansSheet3; $column++) {
                if (0 === $column) {
                    $sheet3_rows .= '<c r="' . $columnsSheet3[$column] . $currentRowSheet3 . '" s="' . $styleType . '"><v>' . ($currentRowSheet3 - 1) . '</v></c>';
                    continue;
                }
                $sharedStringsSI .= '<si><t>' . $appliance[$fields[$column]] . '</t></si>';
                $sheet3_rows .= '<c r="' . $columnsSheet3[$column] . $currentRowSheet3 . '" s="' . $styleType . '" t="s"><v>' . $charPosition++ . '</v></c>';
            }
            $sheet3_rows .= '</row>';
            $currentRowSheet3++;
        }
        echo 'Set cell value (Phones): ' . (microtime(true) - $start) . ' sec' . PHP_EOL;
        echo 'Count of the phones to excel: ' . ($currentRowSheet3 - 2) . PHP_EOL . PHP_EOL;



        $dimensionSheet3 = '<dimension ref="A1:' . end($columnsSheet3) . ($currentRowSheet3 - 1). '"/>';
        $autoFilter3 = '<autoFilter ref="B1:' . end($columnsSheet3) . ($currentRowSheet3 - 1) . '"/>';
        $frozenPane3 = '<sheetView tabSelected="3" workbookViewId="0"><pane state="frozen" activePane="bottomLeft" topLeftCell="A2" ySplit="1"/><selection sqref="A2" activeCell="A2" pane="bottomLeft"/></sheetView>';
        $zip->addFromString('xl/worksheets/sheet3.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac">' . $dimensionSheet3 . '<sheetViews>' . $frozenPane3 . '</sheetViews><sheetFormatPr defaultRowHeight="15" x14ac:dyDescent="0.25"/><sheetData>' . $sheet3_rows . '</sheetData>' . $autoFilter3 . '<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/></worksheet>');
        unset($sheet3_rows);




//--------- Export ---------------------------------
        $start = microtime(true);
        $sharedStringsCount = 'count="' . $charPosition . '" uniqueCount="' . $charPosition . '">';
        $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' . $sharedStringsCount . $sharedStringsSI . '</sst>');
        unset($sharedStringsSI);

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>');

        $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');

        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId6" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/><Relationship Id="rId5" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId4" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/></Relationships>');

        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x15" xmlns:x15="http://schemas.microsoft.com/office/spreadsheetml/2010/11/main"><fileVersion appName="xl" lastEdited="6" lowestEdited="4" rupBuild="14420"/><workbookPr filterPrivacy="1" defaultThemeVersion="124226"/><bookViews><workbookView xWindow="240" yWindow="105" windowWidth="14805" windowHeight="8010"/></bookViews><sheets><sheet name="Appliances" sheetId="1" r:id="rId1"/><sheet name="Appliances with modules" sheetId="2" r:id="rId2"/><sheet name="Phones" sheetId="3" r:id="rId3"/></sheets><calcPr calcId="122211"/></workbook>');

        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" mc:Ignorable="x14ac" xmlns:x14ac="http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac"><fonts count="1" x14ac:knownFonts="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/><scheme val="minor"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFFFFF00"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="4"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>  <xf borderId="0" fillId="2" fontId="0" numFmtId="0" xfId="0" applyAlignment="1" applyFill="1"><alignment vertical="center" horizontal="left"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Обычный" xfId="0" builtinId="0"/></cellStyles><dxfs count="0"/><tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleMedium9"/><extLst><ext uri="{EB79DEF2-80B8-43e5-95BD-54CBDDF9020C}" xmlns:x14="http://schemas.microsoft.com/office/spreadsheetml/2009/9/main"><x14:slicerStyles defaultSlicerStyle="SlicerStyleLight1"/></ext><ext uri="{9260A510-F301-46a8-8635-F512D64BE5F5}" xmlns:x15="http://schemas.microsoft.com/office/spreadsheetml/2010/11/main"><x15:timelineStyles defaultTimelineStyle="TimeSlicerStyleLight1"/></ext></extLst></styleSheet>');

        $zip->addFromString('xl/theme/theme1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="Тема Office"><a:themeElements><a:clrScheme name="Стандартная"><a:dk1><a:sysClr val="windowText" lastClr="000000"/></a:dk1><a:lt1><a:sysClr val="window" lastClr="FFFFFF"/></a:lt1><a:dk2><a:srgbClr val="1F497D"/></a:dk2><a:lt2><a:srgbClr val="EEECE1"/></a:lt2><a:accent1><a:srgbClr val="4F81BD"/></a:accent1><a:accent2><a:srgbClr val="C0504D"/></a:accent2><a:accent3><a:srgbClr val="9BBB59"/></a:accent3><a:accent4><a:srgbClr val="8064A2"/></a:accent4><a:accent5><a:srgbClr val="4BACC6"/></a:accent5><a:accent6><a:srgbClr val="F79646"/></a:accent6><a:hlink><a:srgbClr val="0000FF"/></a:hlink><a:folHlink><a:srgbClr val="800080"/></a:folHlink></a:clrScheme><a:fontScheme name="Стандартная"><a:majorFont><a:latin typeface="Cambria" panose="020F0302020204030204"/><a:ea typeface=""/><a:cs typeface=""/><a:font script="Jpan" typeface="ＭＳ Ｐゴシック"/><a:font script="Hang" typeface="맑은 고딕"/><a:font script="Hans" typeface="宋体"/><a:font script="Hant" typeface="新細明體"/><a:font script="Arab" typeface="Times New Roman"/><a:font script="Hebr" typeface="Times New Roman"/><a:font script="Thai" typeface="Tahoma"/><a:font script="Ethi" typeface="Nyala"/><a:font script="Beng" typeface="Vrinda"/><a:font script="Gujr" typeface="Shruti"/><a:font script="Khmr" typeface="MoolBoran"/><a:font script="Knda" typeface="Tunga"/><a:font script="Guru" typeface="Raavi"/><a:font script="Cans" typeface="Euphemia"/><a:font script="Cher" typeface="Plantagenet Cherokee"/><a:font script="Yiii" typeface="Microsoft Yi Baiti"/><a:font script="Tibt" typeface="Microsoft Himalaya"/><a:font script="Thaa" typeface="MV Boli"/><a:font script="Deva" typeface="Mangal"/><a:font script="Telu" typeface="Gautami"/><a:font script="Taml" typeface="Latha"/><a:font script="Syrc" typeface="Estrangelo Edessa"/><a:font script="Orya" typeface="Kalinga"/><a:font script="Mlym" typeface="Kartika"/><a:font script="Laoo" typeface="DokChampa"/><a:font script="Sinh" typeface="Iskoola Pota"/><a:font script="Mong" typeface="Mongolian Baiti"/><a:font script="Viet" typeface="Times New Roman"/><a:font script="Uigh" typeface="Microsoft Uighur"/><a:font script="Geor" typeface="Sylfaen"/></a:majorFont><a:minorFont><a:latin typeface="Calibri" panose="020F0502020204030204"/><a:ea typeface=""/><a:cs typeface=""/><a:font script="Jpan" typeface="ＭＳ Ｐゴシック"/><a:font script="Hang" typeface="맑은 고딕"/><a:font script="Hans" typeface="宋体"/><a:font script="Hant" typeface="新細明體"/><a:font script="Arab" typeface="Arial"/><a:font script="Hebr" typeface="Arial"/><a:font script="Thai" typeface="Tahoma"/><a:font script="Ethi" typeface="Nyala"/><a:font script="Beng" typeface="Vrinda"/><a:font script="Gujr" typeface="Shruti"/><a:font script="Khmr" typeface="DaunPenh"/><a:font script="Knda" typeface="Tunga"/><a:font script="Guru" typeface="Raavi"/><a:font script="Cans" typeface="Euphemia"/><a:font script="Cher" typeface="Plantagenet Cherokee"/><a:font script="Yiii" typeface="Microsoft Yi Baiti"/><a:font script="Tibt" typeface="Microsoft Himalaya"/><a:font script="Thaa" typeface="MV Boli"/><a:font script="Deva" typeface="Mangal"/><a:font script="Telu" typeface="Gautami"/><a:font script="Taml" typeface="Latha"/><a:font script="Syrc" typeface="Estrangelo Edessa"/><a:font script="Orya" typeface="Kalinga"/><a:font script="Mlym" typeface="Kartika"/><a:font script="Laoo" typeface="DokChampa"/><a:font script="Sinh" typeface="Iskoola Pota"/><a:font script="Mong" typeface="Mongolian Baiti"/><a:font script="Viet" typeface="Arial"/><a:font script="Uigh" typeface="Microsoft Uighur"/><a:font script="Geor" typeface="Sylfaen"/></a:minorFont></a:fontScheme><a:fmtScheme name="Стандартная"><a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="50000"/><a:satMod val="300000"/></a:schemeClr></a:gs><a:gs pos="35000"><a:schemeClr val="phClr"><a:tint val="37000"/><a:satMod val="300000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:tint val="15000"/><a:satMod val="350000"/></a:schemeClr></a:gs></a:gsLst><a:lin ang="16200000" scaled="1"/></a:gradFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:shade val="51000"/><a:satMod val="130000"/></a:schemeClr></a:gs><a:gs pos="80000"><a:schemeClr val="phClr"><a:shade val="93000"/><a:satMod val="130000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="94000"/><a:satMod val="135000"/></a:schemeClr></a:gs></a:gsLst><a:lin ang="16200000" scaled="0"/></a:gradFill></a:fillStyleLst><a:lnStyleLst><a:ln w="9525" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"><a:shade val="95000"/><a:satMod val="105000"/></a:schemeClr></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="25400" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln><a:ln w="38100" cap="flat" cmpd="sng" algn="ctr"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:prstDash val="solid"/></a:ln></a:lnStyleLst><a:effectStyleLst><a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="20000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="38000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle><a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="23000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="35000"/></a:srgbClr></a:outerShdw></a:effectLst></a:effectStyle><a:effectStyle><a:effectLst><a:outerShdw blurRad="40000" dist="23000" dir="5400000" rotWithShape="0"><a:srgbClr val="000000"><a:alpha val="35000"/></a:srgbClr></a:outerShdw></a:effectLst><a:scene3d><a:camera prst="orthographicFront"><a:rot lat="0" lon="0" rev="0"/></a:camera><a:lightRig rig="threePt" dir="t"><a:rot lat="0" lon="0" rev="1200000"/></a:lightRig></a:scene3d><a:sp3d><a:bevelT w="63500" h="25400"/></a:sp3d></a:effectStyle></a:effectStyleLst><a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="40000"/><a:satMod val="350000"/></a:schemeClr></a:gs><a:gs pos="40000"><a:schemeClr val="phClr"><a:tint val="45000"/><a:shade val="99000"/><a:satMod val="350000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="20000"/><a:satMod val="255000"/></a:schemeClr></a:gs></a:gsLst><a:path path="circle"><a:fillToRect l="50000" t="-80000" r="50000" b="180000"/></a:path></a:gradFill><a:gradFill rotWithShape="1"><a:gsLst><a:gs pos="0"><a:schemeClr val="phClr"><a:tint val="80000"/><a:satMod val="300000"/></a:schemeClr></a:gs><a:gs pos="100000"><a:schemeClr val="phClr"><a:shade val="30000"/><a:satMod val="200000"/></a:schemeClr></a:gs></a:gsLst><a:path path="circle"><a:fillToRect l="50000" t="50000" r="50000" b="50000"/></a:path></a:gradFill></a:bgFillStyleLst></a:fmtScheme></a:themeElements><a:objectDefaults/><a:extraClrSchemeLst/></a:theme>');

        $zip->addFromString('docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Microsoft Excel</Application><DocSecurity>0</DocSecurity><ScaleCrop>false</ScaleCrop><HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Листы</vt:lpstr></vt:variant><vt:variant><vt:i4>3</vt:i4></vt:variant></vt:vector></HeadingPairs><TitlesOfParts><vt:vector size="3" baseType="lpstr"><vt:lpstr>Appliances</vt:lpstr><vt:lpstr>Appliances with modules</vt:lpstr><vt:lpstr>Phones</vt:lpstr></vt:vector></TitlesOfParts><Company></Company><LinksUpToDate>false</LinksUpToDate><SharedDoc>false</SharedDoc><HyperlinksChanged>false</HyperlinksChanged><AppVersion>15.0300</AppVersion></Properties>');

        $zip->addFromString('docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator></dc:creator><cp:lastModifiedBy></cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">2006-09-16T00:00:00Z</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">2017-08-25T05:47:03Z</dcterms:modified></cp:coreProperties>');

        // Close file
        if ($zip->close() === false) {
            throw new Exception("Could not close zip file $pFilename.");
        }
echo 'Export to excel: ' . (microtime(true) - $start) . PHP_EOL . PHP_EOL;


        $this->writeLn('Total: ' . (microtime(true) - $startTotal) . ' sec');
        $this->writeLn('Excel export - OK');
    }
}
