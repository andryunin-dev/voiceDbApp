<?php
namespace App\Models;

use App\Components\DSPappliance;
use App\Components\RLogger;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class Phone extends Std
{
    const PHONE = 'phone';
    const PHONESOFT = 'Phone Soft';
    const VENDOR = 'CISCO'; // Todo - пока так

    protected $debugLogger;


    public function __construct()
    {
        $this->debugLogger = RLogger::getInstance('Phone', realpath(ROOT_PATH . '/Logs/debug.log'));
    }


    /**
     * @return bool
     * @throws Exception
     */
    public function update()
    {
        $this->debugLogger->info('START: ' . '[name]=' . $this->ipAddress);
        $this->verifyData();

        // Find the location by the cucm's IP address
        $cucmLocation = (Appliance::findByManagementIP($this->cmIpAddress))->location;
        if (!($cucmLocation instanceof Office)) {
            throw new Exception('Location not found. CucmIP = ' . $this->cmIpAddress);
        }

        $this->debugLogger->info('process: ' . '[name]=' . $this->ipAddress . '; [office]=' . $cucmLocation->title);

        // Create a DataSet for a phone(appliance)
        $softwareVersion = (1 == preg_match('~6921~', $this->type)) ? $this->appLoadID : $this->versionID;
        $macAddress = ($this->macAddress) ?? substr($this->name,-12);
        $macAddress = implode('.', [
            substr($macAddress,0,4),
            substr($macAddress,4,4),
            substr($macAddress,8,4),
        ]);
        $phoneDataSet = (new Std())->fill([
            'applianceType' => self::PHONE,
            'platformVendor' => self::VENDOR,
            'platformTitle' => ($this->modelNumber) ?? $this->type,
            'platformSerial' => ($this->serialNumber) ?? $this->name,
            'applianceSoft' => self::PHONESOFT,
            'softwareVersion' => $softwareVersion,
            'ip' => $this->ipAddress,
            'macAddress' => $macAddress,
            'LotusId' => $cucmLocation->lotusId,
            'hostname' => '',
            'chassis' => ($this->modelNumber) ?? $this->type,
            'applianceModules' => [],
        ]);


        // IF найден PhoneInfo по его имени (значит нашли и Phone)
        // THEN Update Phone
        if (($phoneInfo = PhoneInfo::findByColumn('name', $this->name)) instanceof PhoneInfo) {
            $phone = (new DSPappliance($phoneDataSet, $phoneInfo->phone))->returnAppliance();
        } else {
            $phone = (new DSPappliance($phoneDataSet))->returnAppliance();
            $phoneInfo = new PhoneInfo();
        }

        $this->debugLogger->info('process: ' . '[name]=' . $this->ipAddress . '; [phone]= OK');

        // Update PhoneInfo
        $phoneInfo->fill([
            'phone' => $phone,
            'type' => $this->type,
            'name' => $this->name,
            'prefix' => preg_replace('~\..+~','',$this->prefix),
            'phoneDN' => $this->phoneDN,
            'status' => $this->status,
            'description' => $this->description,
            'css' => $this->css,
            'devicePool' => $this->devicePool,
            'alertingName' => $this->alertingName,
            'partition' => $this->partition,
        ])->save();

        $this->debugLogger->info('process: ' . '[name]=' . $this->ipAddress . '; [phoneInfo]= OK');
        $this->debugLogger->info('END: ' . '[name]=' . $this->ipAddress);

        return true;
    }


    /**
     * @throws Exception
     * @throws MultiException
     */
    protected function verifyData()
    {
        $errors = new MultiException();

        if (!isset($this->cmName)) {
            $errors->add(new Exception('DATASET: No field cmName'));
        }
        if (!isset($this->cmIpAddress)) {
            $errors->add(new Exception('DATASET: No field cmIpAddress'));
        }
        if (empty($this->name)) {
            $errors->add(new Exception('DATASET: Empty or No field name'));
        }
        if (!isset($this->ipAddress)) {
            $errors->add(new Exception('DATASET: No field ipAddress'));
        }
        if (!isset($this->description)) {
            $errors->add(new Exception('DATASET: No field description'));
        }
        if (!isset($this->css)) {
            $errors->add(new Exception('DATASET: No field css'));
        }
        if (!isset($this->devicePool)) {
            $errors->add(new Exception('DATASET: No field devicePool'));
        }
        if (!isset($this->prefix)) {
            $errors->add(new Exception('DATASET: No field prefix'));
        }
        if (!isset($this->phoneDN)) {
            $errors->add(new Exception('DATASET: No field phoneDN'));
        }
        if (!isset($this->alertingName)) {
            $errors->add(new Exception('DATASET: No field alertingName'));
        }
        if (!isset($this->partition)) {
            $errors->add(new Exception('DATASET: No field partition'));
        }
        if (!isset($this->type)) {
            $errors->add(new Exception('DATASET: No field type'));
        }

        // Если DataSet не валидный, то заканчиваем работу
        if (0 < $errors->count()) {
            throw $errors;
        }
    }
}
