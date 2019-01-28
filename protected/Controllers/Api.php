<?php

namespace App\Controllers;

use App\ApiHelpers\DevInfo;
use App\Models\Appliance;
use App\Models\Office;
use App\Models\Vendor;
use App\Models\Vrf;
use App\ViewModels\ApiView_Devices;
use App\ViewModels\ApiView_Geo;
use App\ViewModels\Geo_View;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Api extends Controller
{
    public function actionGetRegCenters()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = json_decode(file_get_contents('php://input'));
        
        $query = (new Query())
            ->select('regCenter')
            ->distinct()
            ->from(Geo_View::getTableName())
            ->where('"regCenter" NOTNULL')
            ->order('"regCenter"');
        $res = Geo_View::findAllByQuery($query);
        $output = [];
        /**
         * @var Geo_View $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->regCenter, 'label' => $item->regCenter];
        }
        $this->data->rc = $output;
    }
    
    public function actionGetRegions()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['region_id NOTNULL'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['region_id', 'region'])
            ->from(ApiView_Geo::getTableName())
            ->where(join(' AND ', $condition))
            ->group('region_id, region')
            ->order('region');
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->region_id, 'label' => $item->region];
        }
        $this->data->rc = $output;
    }
    
    public function actionGetCities()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['city_id NOTNULL'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['city_id', 'city'])
            ->from(ApiView_Geo::getTableName())
            ->where(join(' AND ', $condition))
            ->group('city_id, city')
            ->order('city');
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->city_id, 'label' => $item->city];
        }
        $this->data->rc = $output;
    }
    
    public function actionGetOffices()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['location_id NOTNULL'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['location_id', 'office'])
            ->from(ApiView_Geo::getTableName())
            ->where(join(' AND ', $condition))
            ->group('location_id, office')
            ->order('"office"');
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->location_id, 'label' => $item->office];
        }
        $this->data->rc = $output;
    }
    
    public function actionGetDevTypes()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['dev_type_id NOTNULL'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['dev_type_id', 'dev_type'])
            ->from(ApiView_Devices::getTableName())
            ->where(join(' AND ', $condition))
            ->group('dev_type_id, dev_type')
            ->order('"dev_type"');
        $res = ApiView_Devices::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Devices $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->dev_type_id, 'label' => $item->dev_type];
        }
        $this->data->rc = $output;
    }
    
    public function actionGetPlatforms()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['platform_id NOTNULL'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['platform_id', 'platform'])
            ->from(ApiView_Devices::getTableName())
            ->where(join(' AND ', $condition))
            ->group('platform_id, platform')
            ->order('"platform"');
        $res = ApiView_Devices::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Devices $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->platform_id, 'label' => $item->platform];
        }
        $this->data->rc = $output;
    }
    
    public function actionGetSoftwareList()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['software_id NOTNULL'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(['software_id', 'software'])
            ->from(ApiView_Devices::getTableName())
            ->where(join(' AND ', $condition))
            ->group('software_id, software')
            ->order('"software"');
        $res = ApiView_Devices::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Devices $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->software_id, 'label' => $item->software];
        }
        $this->data->rc = $output;
    }
    
    public function actionGetDevData($id)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $fields = [
            'dev_id', 'location_id', 'platform_id', 'platform_item_id', 'software_id', 'software_item_id', 'vendor_id', 'dev_type_id',
            'dev_comment', 'software_comment', 'dev_last_update', 'dev_in_use', 'platform_sn', 'platform_sn_alt', 'is_hw', 'software_ver',
            'dev_details', 'software_details'
        ];
        $condition = 'dev_id = :dev_id';
        try {
            $query = (new Query())
                ->select($fields)
                ->from(ApiView_Devices::getTableName())
                ->where($condition)
                ->group(join(',',$fields))
                ->params([':dev_id' => $id]);
            $res = ApiView_Devices::findByQuery($query);
            
            $this->data->devInfo = $res;
        } catch (Exception $e) {
            http_response_code(417);
        }
        
    }
    public function actionGetDevModulesData($id)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $fields = [
            'module', 'module_id' ,'module_item_id', 'module_item_details',  'module_item_comment', 'module_item_sn', 'module_in_use', 'module_not_found'
        ];
        $condition = 'dev_id = :dev_id';
        try {
            $query = (new Query())
                ->select($fields)
                ->from(ApiView_Devices::getTableName())
                ->where($condition)
                ->group(join(',',$fields))
                ->order('module')
                ->params([':dev_id' => $id]);
            $res = ApiView_Devices::findAllByQuery($query);

            $res = $res->toArrayRecursive();
            $this->data->modules = $res;
        } catch (Exception $e) {
            http_response_code(417);
        }
        
    }
    public function actionGetDevPortsData($id)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $fields = [
            'port_id', 'port_ip', 'port_comment', 'port_details', 'port_is_mng', 'port_mac', 'port_mask_len', 'port_vrf_id', 'port_vrf_name'
        ];
        $condition = 'dev_id = :dev_id';
        try {
            $query = (new Query())
                ->select($fields)
                ->from(ApiView_Devices::getTableName())
                ->where($condition)
                ->group(join(',',$fields))
                ->order('port_details::jsonb->>\'portName\'')
                ->params([':dev_id' => $id]);
            $res = ApiView_Devices::findAllByQuery($query);

            $res = $res->toArrayRecursive();
            $this->data->ports= $res;
        } catch (Exception $e) {
            http_response_code(417);
        }
        
    }
    public function actionGetDevLocation($location_id)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $fields = [
            'location_id', 'city_id', 'region_id', 'office_comment'
        ];
        $condition = 'location_id = :location_id';
        try {
            $query = (new Query())
                ->select($fields)
                ->from(ApiView_Geo::getTableName())
                ->where($condition)
                ->group(join(',',$fields))
                ->params([':location_id' => $location_id]);
            $res = ApiView_Geo::findByQuery($query);

            $this->data->location= $res;
        } catch (Exception $e) {
            http_response_code(417);
        }
        
    }
    public function actionGetVrfList()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $fields = [
            '__id', 'name', 'rd', 'comment'
        ];
        try {
            $query = (new Query())
                ->select($fields)
                ->from(Vrf::getTableName());
            $res = Vrf::findAllByQuery($query);

            $this->data->vrfList= $res->toArrayRecursive();
        } catch (Exception $e) {
            http_response_code(417);
        }
        
    }
    
    public function actionSaveDev()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        try {
            $data = new Std(json_decode(file_get_contents('php://input')));
            $data = new DevInfo($data);
            if (!($data instanceof DevInfo)) {
                $errors[] = 'Invalid input data';
                throw new Exception();
            }
            if ($data->errors->count() === 0) {
                $data->saveDev();
            } else {
                throw new Exception();
            }
        } catch (Exception $e) {
            $this->data->errors = $errors;
        } catch (\Exception $e) {
            $this->data->exception = $e;
        }
    }
    public function actionPostTest()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $errors = [];
        try {
            $data = new Std(json_decode(file_get_contents('php://input')));
            if (($data instanceof Std)) {
                $errors[] = 'Invalid input data';
                throw new Exception();
            }
        } catch (Exception $e) {
            $this->data->errors = $errors;
        }
    }
    public function actionGetApp($id) {
        $app = Appliance::findByPK($id);
        var_dump($app);
        var_dump('============OFFICE============');
        $office = ($app instanceof Appliance) ? $app->location : null;
        var_dump($office);
        var_dump('============MODULES============');
        $modules = ($app instanceof Appliance) ? $app->modules : null;
        var_dump($modules);
        var_dump('============PORTS============');
        $ports = ($app instanceof Appliance) ? $app->dataPorts : null;
        var_dump($ports);
        die;
    }
}