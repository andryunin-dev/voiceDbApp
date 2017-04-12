<?php

namespace App\Components;

use App\Models\Appliance;
use App\Models\ApplianceType;
use App\Models\DataPort;
use App\Models\Office;
use App\Models\Platform;
use App\Models\PlatformItem;
use App\Models\Software;
use App\Models\SoftwareItem;
use App\Models\Vendor;
use T4\Core\Exception;
use T4\Core\MultiException;
use T4\Core\Std;

class DataProcessor extends Std
{
//    protected $dir = 'C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_2017041011131081254400.json';
    protected $file = 'C:\\OpenServer\\domains\\voice.loc\\protected\\Test_JSON\\item_2017041011131081254400.json';
    protected $management_ip;
    protected $description;
    protected $show_version;
    protected $lotus_id;
    protected $chassis;
    protected $serial;
    protected $vendor_name;
    protected $type;
    protected $software;
    protected $modules = [];
    public $data = [];

    public function run()
    {
        $rawdata = (new Std())->fill(json_decode(file_get_contents($this->file)));

        $this->data->officeId;
        $this->data->vendorId;
        $this->data->applianceTypeId;
        $this->data->platformId;
        $this->data->softwareId;

        die();
    }

    public function AddAppliance($data)
    {
        try {
            Appliance::getDbConnection()->beginTransaction();

            if (!is_numeric($data->officeId)) {
                throw new Exception('Офис не выбран');
            }
            if (!is_numeric($data->vendorId)) {
                throw new Exception('Производитель не выбран');
            }
            if (!is_numeric($data->applianceTypeId)) {
                throw new Exception('Тип оборудования не выбран');
            }
            if (!is_numeric($data->platformId)) {
                throw new Exception('Платформа не выбрана');
            }
            if (!is_numeric($data->softwareId)) {
                throw new Exception('ПО не выбрано');
            }
            $office = Office::findByPK($data->officeId);
            $vendor = Vendor::findByPK($data->vendorId);
            $applianceType = ApplianceType::findByPK($data->applianceTypeId);

            $platformItem = (new PlatformItem())
                ->fill([
                    'platform' => Platform::findByPK($data->platformId),
                    'serialNumber' => $data->platformSn
                ])
                ->save();

            $softwareItem = (new SoftwareItem())
                ->fill([
                    'software' => Software::findByPK($data->softwareId),
                    'version' => $data->softwareVersion
                ])
                ->save();

            $appliance = (new Appliance())
                ->fill([
                    'location' => $office,
                    'vendor' => $vendor,
                    'platform' => $platformItem,
                    'software' => $softwareItem,
                    'type' => $applianceType,
                    'details' => [
                        'hostname' => $data->hostname
                    ]
                ])
                ->save();

            //если appliance сохранился без ошибок - сохраняем модули к нему
            if (!empty($data->module->id)) {
                foreach ($data->module->id as $key => $value) {
                    //если не выбран модуль - пропускаем
                    if (!is_numeric($value)) {
                        continue;
                    }
                    $module = Module::findByPK($value);
                    $moduleItem = (new ModuleItem())
                        ->fill([
                            'appliance' => $appliance,
                            'module' => $module,
                            'serialNumber' => $data->module->sn->$key,
                            'comment' => $data->module->comment->$key
                        ])
                        ->save();
                }
            }

            Appliance::getDbConnection()->commitTransaction();
        } catch (MultiException $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            $this->data->errors = $e;
        } catch (Exception $e) {
            Appliance::getDbConnection()->rollbackTransaction();
            $this->data->errors = (new MultiException())->add($e);
        }
    }
}