<?php

namespace App\Controllers;

use App\ApiHelpers\DevInfo;
use App\ApiHelpers\NetData;
use App\Models\Appliance;
use App\Models\Network;
use App\Models\Office;
use App\Models\Vendor;
use App\Models\Vrf;
use App\ViewModels\ApiView_Devices;
use App\ViewModels\ApiView_DPorts;
use App\ViewModels\ApiView_Geo;
use App\ViewModels\ApiView_Modules;
use App\ViewModels\ApiView_Networks;
use App\ViewModels\ApiView_Vrfs;
use App\ViewModels\Geo_View;
use phpDocumentor\Reflection\Types\This;
use T4\Core\Exception;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Mvc\Controller;

class Api extends Controller
{
    protected $devData;
    protected $netData;
    protected $errors = [];
    public function actionGetRegCenters()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = Geo_View::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['"regCenter" NOTNULL'];
        $fields = ['"regCenter"'];
        $orderBy = ['"regCenter"'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(join(',',$fields))
            ->from($table)
            ->where(join(' AND ', $condition))
            ->group(join(',',$fields))
            ->order(join(',',$orderBy));
        $res = Geo_View::findAllByQuery($query);
        $output = [];
        /**
         * @var Geo_View $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->regCenter, 'label' => $item->regCenter];
        }
        $this->data->regCenters = $output;
    }
    
    public function actionGetRegions()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = ApiView_Geo::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['region_id NOTNULL'];
        $fields = ['region_id', 'region'];
        $orderBy = ['region'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(join(',',$fields))
            ->from($table)
            ->where(join(' AND ', $condition))
            ->group(join(',',$fields))
            ->order(join(',',$orderBy));
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->region_id, 'label' => $item->region];
        }
        $this->data->regions = $output;
    }
    
    public function actionGetCities()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = ApiView_Geo::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['city_id NOTNULL'];
        $fields = ['city_id', 'city'];
        $orderBy = ['city'];
        if ($filters->count() > 0) {
            foreach ($filters as $filter) {
                if (!empty($filter->value)) {
                    $condition[] = $filter->accessor . $filter->statement . $filter->value;
                }
            }
        }
        
        $query = (new Query())
            ->select(join(',',$fields))
            ->from($table)
            ->where(join(' AND ', $condition))
            ->group(join(',',$fields))
            ->order(join(',',$orderBy));
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->city_id, 'label' => $item->city];
        }
        $this->data->cities = $output;
    }
    
    public function actionGetOffices()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = ApiView_Geo::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['office_id NOTNULL'];
        $fields = ['office_id', 'office'];
        $orderBy = ['"office"'];
        if ($filters->count() > 0) {
            foreach ($filters as $filter) {
                if (!empty($filter->value)) {
                    $condition[] = $filter->accessor . $filter->statement . $filter->value;
                }
            }
        }
        $query = (new Query())
            ->select(join(',',$fields))
            ->from($table)
            ->where(join(' AND ', $condition))
            ->group(join(',',$fields))
            ->order(join(',',$orderBy));
        $res = ApiView_Geo::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Geo $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->office_id, 'label' => $item->office];
        }
        $this->data->offices = $output;
    }
    
    public function actionGetDevTypes()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = ApiView_Devices::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['dev_type_id NOTNULL'];
        $fields = ['dev_type_id', 'dev_type'];
        $orderBy = ['"dev_type"'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(join(',',$fields))
            ->from($table)
            ->where(join(' AND ', $condition))
            ->group(join(',',$fields))
            ->order(join(',',$orderBy));
        $res = ApiView_Devices::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Devices $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->dev_type_id, 'label' => $item->dev_type];
        }
        $this->data->devTypes = $output;
    }
    
    public function actionGetPlatforms()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = ApiView_Devices::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['platform_id NOTNULL'];
        $fields = ['platform_id', 'platform'];
        $orderBy = ['"platform"'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(join(',',$fields))
            ->from($table)
            ->where(join(' AND ', $condition))
            ->group(join(',',$fields))
            ->order(join(',',$orderBy));

        $res = ApiView_Devices::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Devices $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->platform_id, 'label' => $item->platform];
        }
        $this->data->platforms = $output;
    }
    
    public function actionGetSoftwareList()
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = ApiView_Devices::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['software_id NOTNULL'];
        $fields = ['software_id', 'software'];
        $orderBy = ['"software"'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        $query = (new Query())
            ->select(join(',',$fields))
            ->from($table)
            ->where(join(' AND ', $condition))
            ->group(join(',',$fields))
            ->order(join(',',$orderBy));

        $res = ApiView_Devices::findAllByQuery($query);
        $output = [];
        /**
         * @var ApiView_Devices $item
         */
        foreach ($res as $item) {
            $output[] = ['value' => $item->software_id, 'label' => $item->software];
        }
        $this->data->softwareList = $output;
    }
    
    public function actionGetDevData($id)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        $table = ApiView_Devices::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['dev_id = :dev_id'];
        $fields = [
            'dev_id', 'location_id', 'platform_id', 'platform_item_id', 'software_id', 'software_item_id', 'vendor_id', 'dev_type_id',
            'dev_comment', 'software_item_comment', 'dev_last_update', 'dev_in_use', 'platform_item_sn', 'platform_item_sn_alt', 'is_hw', 'software_item_ver',
            'dev_details', 'software_item_details'
        ];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        
        try {
            $query = (new Query())
                ->select(join(',',$fields))
                ->from($table)
                ->where(join(' AND ', $condition))
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
        $table = ApiView_Modules::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['dev_id = :dev_id'];
        $fields = [
            'module', 'module_id' ,'module_item_id', 'module_item_details',  'module_item_comment', 'module_item_sn', 'module_item_in_use', 'module_item_not_found', 'module_item_last_update'
        ];
        $order = ['module'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        try {
            $query = (new Query())
                ->select(join(',',$fields))
                ->from($table)
                ->where(join(' AND ', $condition))
                ->group(join(',',$fields))
                ->order(join(',',$order))
                ->params([':dev_id' => $id]);
            $res = ApiView_Modules::findAllByQuery($query);

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
        $table = ApiView_DPorts::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['dev_id = :dev_id'];
        $fields = [
            'port_id', 'port_type_id', 'port_ip', 'port_comment', 'port_details', 'port_is_mng', 'port_mac', 'port_mask_len', 'port_type', 'port_last_update', 'port_net_id', 'port_vrf_id'
        ];
        $order = ['port_details::jsonb->>\'portName\''];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        
        try {
            $query = (new Query())
                ->select(join(',',$fields))
                ->from($table)
                ->where(join(' AND ', $condition))
                ->group(join(',',$fields))
                ->order(join(',',$order))
                ->params([':dev_id' => $id]);
            $res = ApiView_DPorts::findAllByQuery($query);

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
        $table = ApiView_Geo::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = ['office_id = :office_id'];
        $fields = ['office_id', 'city_id', 'region_id', 'office_comment'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
        
        try {
            $query = (new Query())
                ->select(join(',',$fields))
                ->from($table)
                ->where(join(' AND ', $condition))
                ->group(join(',',$fields))
                ->params([':office_id' => $location_id]);
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
        $table = ApiView_Vrfs::getTableName();
        $filters = new Std(json_decode(file_get_contents('php://input')));
        $condition = [];
        $fields = ['vrf_id', 'vrf_name', 'vrf_rd', 'vrf_comment'];
        $order = ['vrf_name'];
        if (!empty($filters->value)) {
            $condition[] = $filters->accessor . $filters->statement . $filters->value;
        }
    
        try {
            $query = (new Query())
                ->select(join(',',$fields))
                ->from($table)
                ->order(join(',',$order));
            $res = ApiView_Vrfs::findAllByQuery($query);
        
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
            $this->devData = new Std(json_decode(file_get_contents('php://input')));
            $this->devData = new DevInfo($this->devData);
            if (!($this->devData instanceof DevInfo)) {
                $this->errors[] = 'Invalid input data';
                throw new Exception();
            }
            if ($this->devData->errors->count() === 0) {
                $this->devData->saveDev();
                $this->data->result = 'OK';
            } else {
                $this->errors = array_merge($this->errors, $this->devData->errors->toArray());
                throw new Exception();
            }
        } catch (Exception $e) {
            $this->data->errors = $this->errors;
        } catch (\Exception $e) {
            $this->data->exception = $e->getMessage();
        }
    }
    
    public function actionGetNetData($netId)
    {
        // respond to preflights
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        try {
            if (! is_numeric($netId)) {
                throw new Exception('Error data request');
            }
            $netId = intval($netId);
            $network = ApiView_Networks::findByPK($netId);
            $this->data->netData = $network;
        } catch (\Exception $e) {
            $this->data->exception = $e->getMessage();
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
    public function actionSaveNetData()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit;
        }
        try {
            $this->netData = new Std(json_decode(file_get_contents('php://input')));
            if (!($this->netData instanceof Std)) {
                $this->errors[] = 'Invalid input data';
                throw new \Exception();
            }
            if (is_numeric($this->netData->vrfId)) {
                $this->netData->vrf = Vrf::findByPK($this->netData->vrfId);
            } else {
                $this->errors[] ='Invalid VRF data';
                throw new \Exception();
            }
            if ($this->netData->delNet === true) {
                $network = Network::findByPK($this->netData->netId);
                if (! $network instanceof Network) {
                    $this->errors[] = 'Network not found';
                    throw new \Exception();
                }
                $network->delete();
                $this->data->parentNetId = $network->parentNetwork === false ? false :  $network->parentNetwork->getPk();
            } elseif ($this->netData->newNet === true) {
                //new Network
                $network = (new Network())
                    ->fill([
                        'address' => $this->netData->netIp,
                        'comment' => $this->netData->netComment,
                        'vrf' => $this->netData->vrf
                    ])
                    ->save();
                $this->data->netId = $network->getPk();
                $this->data->parentNetId = $network->parentNetwork === false ? false :  $network->parentNetwork->getPk();
            } elseif ($this->netData->newNet === false) {
                //edit existed network
                $network = Network::findByPK($this->netData->netId);
                if (! $network instanceof Network) {
                    $this->errors[] = 'Network not found';
                    throw new \Exception();
                }
                $network
                    ->fill([
                        'address' => $this->netData->netIp,
                        'comment' => $this->netData->netComment,
                        'vrf' => $this->netData->vrf
                    ])
                    ->save();
                $this->data->netId = $network->getPk();
                $this->data->parentNetId = $network->parentNetwork === false ? false :  $network->parentNetwork->getPk();
            }
            $this->data->result = 'OK';
        } catch (\Exception $e) {
            if (isset($network) && $network instanceof Network) {
                $this->data->errors =  array_merge($this->errors, $network->errors);
            } else {
                $this->data->errors = [$e->getMessage()];
            }
        }
    }
    
    /**
     * Test only
     */
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
                throw new \Exception();
            }
        } catch (\Exception $e) {
            $this->data->errors = $errors;
        }
    }
    public function actionGetNetParent($netId)
    {
        try {
            $network = Network::findByPK($netId);
            if (!($network instanceof Network)) {
                throw new \Exception('Update after submit: Invalid network ID: ' . $id);
            }
            $this->data->parentNetId = $network->parentNetwork === false ? false : $network->parentNetwork->getPk();
        } catch (\Exception $e) {
            $this->data->errors = [$e->getMessage()];
        }
        
    }
    
}