<?php

namespace App\Models;

use App\Components\Ip;
use App\Components\IpTools;
use phpDocumentor\Reflection\Types\This;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\IArrayable;
use T4\Core\MultiException;
use T4\Core\Std;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class DataPort
 * @package App\Models
 * имя порта писать в details->portName
 *
 * @property array $errors
 * @property string $ipAddress
 * @property string $cidrIpAddress
 * @property int $masklen
 * @property Network $network
 * @property string $macAddress
 * @property string $details
 * @property string $comment
 * @property bool $isManagement
 * @property string $portName
 *
 * @property Appliance $appliance
 * @property DPortType $portType
 * @property Vrf $vrf
 */
class DataPort extends Model
{
    const DEFAULT_PORTNAME = '';
    const DEFAULT_MACADDRESS = '00:00:00:00:00:00';
    const SQL = [
        'findPortBy_Ip_VrfId' => '
        SELECT dp.* FROM equipment."dataPorts" dp
          JOIN network.networks net ON dp.__network_id = net.__id
          JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id
          WHERE host(dp."ipAddress") = host(:address) AND vrf.__id = :vrf_id',
        'findPortBy_Ip_VrfName' => '
        SELECT dataport.*
        FROM equipment."dataPorts" dataport
          JOIN network.networks network ON network.__id = dataport.__network_id
          JOIN network.vrfs vrf ON vrf.__id = network.__vrf_id
        WHERE host(dataport."ipAddress") = host(:ip) AND vrf.name = :vrf_name',
        'countPortsBy_Ip_VrfId' => '
        SELECT count(1) FROM equipment."dataPorts" dp
          JOIN network.networks net ON dp.__network_id = net.__id
          JOIN network.vrfs vrf ON net.__vrf_id = vrf.__id
          WHERE host(dp."ipAddress") = host(:address) AND vrf.__id = :vrf_id',
        'findByAppliance_Interface' => '
        SELECT dataport.*
        FROM equipment."dataPorts" dataport
          JOIN equipment.appliances appliance ON appliance.__id = dataport.__appliance_id
        WHERE appliance.__id = :appliance_pk AND dataport.details->>\'portName\' = :port_name',
    ];


    public function __construct($data = null)
    {
        $this->errors = [];
        $this->details = new Std(['portName' => self::DEFAULT_PORTNAME]);
        parent::__construct($data);
    }


    protected static $schema = [
        'table' => 'equipment.dataPorts',
        'columns' => [
            'ipAddress' => ['type' => 'string'],
            'masklen' => ['type' => 'int'],
            'macAddress' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'text'],
            'lastUpdate' => ['type' => 'datetime'],
            'isManagement' => ['type' => 'boolean'],
            'dnsName' => ['type' => 'text'],
            'dnsLastUpdate' => ['type' => 'datetime'],
        ],
        'relations' => [
            'appliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
            'portType' => ['type' => self::BELONGS_TO, 'model' => DPortType::class, 'by' => '__type_port_id'],
            'network' => ['type' => self::BELONGS_TO, 'model' => Network::class, 'by' => '__network_id']
        ]
    ];

    protected $vrf;
    protected $errors;

    protected function getErrors()
    {
        return $this->errors;
    }

    protected function getCidrIpAddress()
    {
        $ip = new IpTools($this->ipAddress, $this->masklen);
        if ($ip->is_valid) {
            return $ip->cidrAddress;
        } else {
            return null;
        }
    }
    public function checkIpAddress()
    {
        $localErrors = [];
        $ip = new IpTools($this->ipAddress, $this->masklen);
        if (false === $ip->is_valid) {
            $localErrors[] = 'Invalid IP address: ' . $this->ipAddress;
        }
        if (true === $ip->is_maskNull) {
            $localErrors[] = 'mask is not set for IP: ' . $this->ipAddress;
        } elseif (false === $ip->is_hostIp) {
            $localErrors[] = $ip->cidrAddress . ' is not host IP';
        }
        if (count($localErrors) > 0) {
            $this->errors = array_merge($this->errors, $localErrors);
            return false;
        }
        $this->ipAddress = $ip->address;
        $this->masklen = $ip->masklen;
        return true;
    }

    public function checkMacAddress()
    {
        $this->macAddress = trim($this->macAddress);
        if (empty($this->macAddress)) {
            $this->macAddress = self::DEFAULT_MACADDRESS;
            return true;
        }
        if (!empty($this->macAddress) && false === filter_var(trim($this->macAddress), FILTER_VALIDATE_MAC)) {
            $this->errors[] = 'Invalid MAC address: ' . $this->macAddress;
            return false;
        }
        return true;
    }
    public function checkVrf()
    {
        if (!($this->getVrf() instanceof Vrf)) {
            $this->errors[] = 'Invalid VRF';
            return false;
        }
        return true;
    }
    public function checkAppliance()
    {
        if (!($this->appliance instanceof Appliance)) {
            $this->errors[] = 'Invalid appliance';
            return false;
        }
        return true;
    }
    public function checkDPortType()
    {
        if (!($this->portType instanceof DPortType)) {
            $this->errors[] = 'Invalid port type';
            return false;
        }
        return true;
    }
    protected function getVrf()
    {
        return empty($this->vrf) ? $this->vrf = $this->network->vrf : $this->vrf;
    }

    protected function setVrf(Vrf $vrf)
    {
       $this->vrf = $vrf;
       return $this;
    }

    protected function getPortName()
    {
        return $this->details->portName;
    }

    public function formatMacAddress()
    {
        $key = 'macAddress';
        if (isset($this->__data[$key]) && !empty($this->__data[$key])) {
            $data = preg_replace('~:~', '', $this->__data[$key]);

            return implode('.', [
                substr($data,0,4),
                substr($data,4,4),
                substr($data,8,4),
            ]);
        }

        return null;
    }

    /**
     * @param string $ipCidr
     * @param Vrf $vrf
     * @return Network|bool
     */
    protected function createNetworkIfNotExists($ipCidr, $vrf)
    {
        $network = Network::findByAddressVrf($ipCidr, $vrf);
        if ($network === false) {
            try {
                $network = new Network();
                $network->fill([
                    'address' => $ipCidr,
                    'vrf' => $this->vrf,
                ])
                    ->save();
            } catch (\Exception $e) {
            }
        }
        return $network;
    }

    protected function checkAbilityCreatePort()
    {

    }

    protected function checkDataBeforeUpdateCreate()
    {
        try {
            $localErrors = [];
            $checkIp = $this->checkIpAddress();
            $checkMac = $this->checkMacAddress();
            $checkVrf = $this->checkVrf();
            if(!$checkIp || !$checkMac || !$checkVrf) {
                return false;
            }
            $ip = new IpTools($this->ipAddress, $this->macAddress);

            if (!($this->appliance instanceof Appliance)) {
                $localErrors[] = 'Appliance for data port is not found';
            }
            if (!($this->portType instanceof  DPortType)) {
                $localErrors[] = 'Invalid port type';
            }
            //find existed by ip vrf
            $dPortFromDb = self::findByIpVrf($this->ipAddress, $this->vrf);
            if ($dPortFromDb instanceof DataPort && $dPortFromDb->getPk() !== $this->getPk()) {
                $localErrors[] = 'IP address ' . $this->cidrIpAddress . ' already in use';
            }

            if (count($localErrors) > 0) {
                $this->errors = array_merge($this->errors, $localErrors);
                return false;
            }
            // if there are not errors, try to create network for this host IP if it not exists
            //try to create network if not exists
            $network = $this->createNetworkIfNotExists($ip->cidrNetwork, $this->vrf);
            if (count($network->errors) == 0) {
                $this->network = $network;
            } else {
                $localErrors = array_merge($localErrors, $network->errors);
            }

            if (count($localErrors) > 0) {
                $this->errors = array_merge($this->errors, $localErrors);
                return false;
            }
            return true;
        } catch (\Throwable $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param string $ip IP address cidr or only host part
     * @param Vrf $vrf
     * @return Collection
     */
    public static function findAllByIpVrf($ip, $vrf)
    {
        $query = new Query(self::SQL['findPortBy_Ip_VrfId']);
        $res = self::findAllByQuery($query, [':address' => $ip, ':vrf_id' => $vrf->getPk()]);
        return $res;
    }

    /**
     * @param string $ip IP address cidr or only host part
     * @param Vrf $vrf
     * @return DataPort
     */
    public static function findByIpVrf($ip, $vrf)
    {
        $query = new Query(self::SQL['findPortBy_Ip_VrfId']);
        $res = self::findByQuery($query, [':address' => $ip, ':vrf_id' => $vrf->getPk()]);
        return $res;
    }
    /**
     * Find DataPorts by IpAddress and Vrf_Name
     *
     * @param string $ip IP address cidr or only host part
     * @param string $vrf_name
     * @return mixed
     */
    public static function findAllByIp_VrfName(string $ip, string $vrf_name)
    {
        $query = new Query(self::SQL['findPortBy_Ip_VrfName']);
        $res = self::findAllByQuery($query, [':ip' => $ip, 'vrf_name' => $vrf_name]);
        return $res;
    }

    /**
     * Find DataPort by IpAddress and Vrf_Name
     *
     * @param string $ip IP address cidr or only host part
     * @param string $vrf_name
     * @return mixed
     */
    public static function findByIp_VrfName(string $ip, string $vrf_name)
    {
        $query = new Query(self::SQL['findPortBy_Ip_VrfName']);
        $res = self::findByQuery($query, [':ip' => $ip, 'vrf_name' => $vrf_name]);
        return $res;
    }

    public static function countByIpVrf($ip, $vrf)
    {
        $query = new Query(self::SQL['countPortsBy_Ip_VrfId']);
        $res = self::countAllByQuery($query, [':address' => $ip, ':vrf_id' => $vrf->getPk()]);
        return $res;
    }

    public function isManagement()
    {
        $this->isManagement = true;
    }

    public function isNotManagement()
    {
        $this->isManagement = false;
    }

    protected function validate()
    {
        try {
            $localErrors = [];
            $checkApp = $this->checkAppliance();
            $checkIp = $this->checkIpAddress();
            $checkMac = $this->checkMacAddress();
            $checkVrf = $this->checkVrf();
            $checkPortType = $this->checkDPortType();
            if(!$checkApp || !$checkIp ||  !$checkMac || !$checkVrf || !$checkPortType) {
                return false;
            }

            if (!($this->appliance instanceof Appliance)) {
                $localErrors[] = 'Appliance for data port is not found';
            }
            if (!($this->portType instanceof  DPortType)) {
                $localErrors[] = 'Invalid port type';
            }
            //find existed by ip vrf
            $dPortFromDb = self::findByIpVrf($this->ipAddress, $this->vrf);
            if ($dPortFromDb instanceof DataPort && $dPortFromDb->getPk() !== $this->getPk()) {
                $localErrors[] = 'IP address ' . $this->cidrIpAddress . ' already in use';
            }

            if (count($localErrors) > 0) {
                $this->errors = array_merge($this->errors, $localErrors);
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $localErrors[] = $e->getMessage();
            $this->errors = array_merge($this->errors, $localErrors);
            return false;
        }
    }

    protected function beforeSave()
    {
        $localErrors = [];
        try {
            if (count($this->errors) > 0) {
                return false;
            }
            // if there are not errors, try to create network for this host IP if it not exists
            //try to create network if not exists
            $ip = new IpTools($this->ipAddress, $this->masklen);
            $network = $this->createNetworkIfNotExists($ip->cidrNetwork, $this->vrf);
            if (count($network->errors) == 0) {
                $this->network = $network;
            } else {
                $localErrors = array_merge($localErrors, $network->errors);
            }

            if (count($localErrors) > 0) {
                $this->errors = array_merge($this->errors, $localErrors);
                return false;
            }
            return parent::beforeSave();
        } catch (\Exception $e) {
            $localErrors[] = $e->getMessage();
            $this->errors = array_merge($this->errors, $localErrors);
            return false;
        }

    }
    protected function afterDelete()
    {
        if (null !== $this->network && 32 == (new IpTools($this->network->address))->masklen) {
            $this->network->delete();
        }
        return parent::afterDelete();
    }

    public static function findByApplianceInterface(Appliance $appliance, string $interface)
    {
        $query = new Query(self::SQL['findByAppliance_Interface']);
        return self::findByQuery($query, [':appliance_pk' => $appliance->getPk(), ':port_name' => $interface]);
    }

//    ==============LEGACY CODE==================
//    public function _set($key, $val)
//    {
//        if ('ipAddress' == $key) {
//            //перенес из фреймворка
//            $validateMethod = 'validate' . ucfirst($key);
//            if (method_exists($this, $validateMethod)) {
//
//                $validateResult = $this->$validateMethod($val);
//                if (false === $validateResult) {
//                    return;
//                }
//
//                if ($validateResult instanceof \Generator) {
//                    $errors = new MultiException();
//                    foreach ($validateResult as $error) {
//                        if ($error instanceof \Exception) {
//                            $errors[] = $error;
//                        }
//                    }
//                    if (!$errors->isEmpty()) {
//                        throw $errors;
//                    }
//                }
//                if (isset($this->isNew) && false === $this->isNew) {
//                    $this->isUpdated = true;
//                }
//            }
//            //================
//            $ip = new IpTools($val);
//            if ($ip->is_valid) {
//                parent::__set('masklen', $ip->masklen);
//                parent::__set('ipAddress', $ip->address);
//            }
//        } else {
//            parent::__set($key, $val);
//        }
//    }
//
//    public function _fill($data)
//    {
//        if ($data instanceof IArrayable) {
//            $data = $data->toArray();
//        } else {
//            $data = (array)$data;
//        }
//
//        if (array_key_exists('vrf', $data) && ($data['vrf'] instanceof Vrf) || null === ($data['vrf'])) {
//            $this->vrf = $data['vrf'];
//            unset($data['vrf']);
//        } else {
//            throw new Exception('Неверно задан VRF');
//        }
//        return parent::fill($data);
//    }
//
//
//    /**
//     * не может быть пустым
//     * адрес должен быть валидным
//     * должна быть явно задана маска
//     * адрес не должен быть адресом сети
//     *
//     * @param $val
//     * @return bool
//     * @throws Exception
//     *
//     */
//    protected function _validateIpAddress($val)
//    {
//        $ip = new IpTools($val);
//        if (false === $ip->is_valid) {
//            throw new Exception(implode('<br>', $ip->errors));
//        }
//        if ($ip->is_valid && false === $ip->is_maskNull && false === $ip->is_hostIp) {
//            throw new Exception($ip->cidrAddress . ' - не является адресом хоста' );
//        }
//        return true;
//    }
//
//    /**
//     * не должен быть пустым
//     * должен быть валидным
//     *
//     * @param $val
//     * @return bool
//     * @throws Exception
//     */
//    protected function _validateMacAddress($val)
//    {
//        if (empty(trim($val))) {
//            return true;
//        }
//        if (!empty(trim($val)) && false === filter_var(trim($val), FILTER_VALIDATE_MAC)) {
//            throw new Exception('DataPort: Неверный формат MAC адреса');
//        }
//        return true;
//    }
//
//
//    protected function _sanitizeMacAddress($val)
//    {
//        if (empty($val)) {
//            $val = self::DEFAULT_MACADDRESS;
//        }
//
//        return filter_var(trim($val), FILTER_VALIDATE_MAC);
//    }
//
//    protected function _sanitizeComment($val)
//    {
//        return trim($val);
//    }
//
//    protected function _sanitizeDetails($details)
//    {
//        if (is_array($details)) {
//            foreach ($details as $key => $item) {
//                $details[$key] = trim($item);
//            }
//        }
//
//        if (empty($details)) {
//            $details = ['portName' => self::DEFAULT_PORTNAME];
//        }
//
//        return $details;
//    }
//
//    protected function _validate()
//    {
//        $ip = new IpTools($this->ipAddress, $this->masklen);
//
//        if (false === $this->appliance) {
//            throw new Exception('Устройство не найдено');
//        }
//        if (false === $this->portType) {
//            throw new Exception('Данный тип порта не найден');
//        }
//
//        //ищем записи с таким ip для новой записи
//        if (true === $this->isNew && self::countAllByIpVrf($this->ipAddress, $this->vrf) > 0) {
//            throw new Exception('IP адрес ' . $ip->address . ' уже используется.');
//        }
//        //валидация при изменении существующей записи
//        if (true === $this->isUpdated) {
//            $fromDb = self::findByIpVrf($this->ipAddress, $this->vrf);
//            if (false !== $fromDb && $fromDb->getPk() != $this->getPk()) {
//                throw new Exception('IP адрес ' . $ip->address . ' уже используется.');
//            }
//        }
//
//        return true;
//    }
//
//    /**
//     * назначаем подсеть для ip адреса данного порта и сохраняем ее.
//     * если именяемый порт имел сетку с 32 маской - удаляем ее
//     * @return bool
//     * @throws Exception
//     */
//    protected function _beforeSave()
//    {
//        $ip = (new IpTools($this->ipAddress, $this->masklen));
//        //если маска не нулевая, то ищем сетку для него
//        if ($this->isNew && !$ip->is_maskNull) {
//            if (false === $network = Network::findByAddressVrf($ip->cidrNetwork, $this->vrf)) {
//                $network = (new Network())
//                    ->fill([
//                        'address' => ($ip->cidrNetwork),
//                        'vrf' => $this->vrf
//                    ])
//                    ->save();
//                $network->refresh();
//                //new network must not contain any subnets
//                if ($network->children->count() > 0) {
//                    $network->delete();
//                    throw new Exception('Ошибка при создании сети ' . $network->address . ' для хоста ' . $this->cidrIpAddress . '. Данная сеть разбита на подсети. Использование для хостовых IP невозможно.');
//                }
//            } elseif ($network->children->count() > 0) {
//                throw new Exception('Сеть ' . $network->address . ' разбита на подсети. Использование для хостовых IP невозможно. Хост IP - ' . $this->cidrIpAddress);
//            }
//            $this->network = $network;
//        } elseif ($this->isUpdated && !$ip->is_maskNull) {
//            if (null !== $this->vrf && false !== $network = Network::findByAddressVrf($ip->cidrNetwork, $this->vrf)) {
//                //if net for new IP exists, it must not contain subnets.
//                if ($network->children->count() > 0) {
//                    throw new Exception('Сеть ' . $network->address . ' разбита на подсети. Использование для хостовых IP невозможно. Хост IP - ' . $this->cidrIpAddress);
//                }
//                $this->network = $network;
//            } else {
//                //network for new IP not found. Create it
//                $network = (new Network())
//                    ->fill([
//                        'address' => ($ip->cidrNetwork),
//                        'vrf' => $this->vrf
//                    ]);
//                $network->validate();
//                //delete old DataPort
//                DataPort::findByPK($this->getPk())->delete();
//                //change current updated Data Port like new object
//                $this->isUpdated = false;
//                $this->isNew = true;
//                //try save new network
//                $network->save();
//                $network->refresh();
//                //new network must not contain any subnets
//                if ($network->children->count() > 0) {
//                    $network->delete();
//                    throw new Exception('Ошибка при создании сети ' . $network->address . ' для хоста ' . $this->cidrIpAddress . '. Данная сеть разбита на подсети. Использование для хостовых IP невозможно.');
//                }
//                $this->network = $network;
//            }
//        }
//
//        if (true === $this->isNew && null === $this->isManagement) {
//            $this->isNotManagement();
//        }
//
//        return parent::beforeSave();
//    }
//
//    public function _save()
//    {
//        if ($this->isUpdated) {
//            $oldNetwork = (DataPort::findByPK($this->getPk()))->network;
//            $saveResult = parent::save();
//            if (false !== $saveResult &&
//                null !== $oldNetwork &&
//                32 == (new Ip($oldNetwork->address))->masklen &&
//                $this->network->address != $oldNetwork->address) {
//                $oldNetwork->delete();
//            }
//            return $saveResult;
//        } else {
//            return parent::save();
//        }
//    }
//
//
//    /**
//     * @param $ip
//     * @param Vrf $vrf
//     * @return int
//     */
//    public static function _countAllByIpVrf($ip, Vrf $vrf)
//    {
//        return self::findAllByIpVrf($ip, $vrf)->count();
//    }
//
//    /**
//     * @param $ip
//     * @param Vrf $vrf
//     * @return bool|Collection|DataPort[]
//     */
//    public static function _findAllByIpVrf($ip, $vrf)
//    {
//        $query = (new Query())
//            ->select()
//            ->from(DataPort::getTableName())
//            ->where('host("ipAddress") = host(:ip)')
//            ->params([':ip' => $ip]);
//
//        /**
//         * @var Collection|bool $result
//         */
//        $result = DataPort::findAllByQuery($query);
//        $result = $result->filter(function ($dPort) use ($vrf) {
//            /**
//             * @var DataPort $dPort
//             * @var Vrf $vrf
//             */
//            if (null === $dPort->network) {
//                return true;
//            } else {
//                return ($dPort->network->vrf->name == $vrf->name);
//            }
//        });
//        return (null === $result) ? false : $result;
//    }
//
//    /**
//     * @param $ip
//     * @param Vrf $vrf
//     * @return DataPort|bool
//     */
//    public static function _findByIpVrf($ip, $vrf)
//    {
//        $result = self::findAllByIpVrf($ip, $vrf)->first();
//        return (null === $result) ? false : $result;
//    }
//
//
//    public function _trimIpAddress()
//    {
//        return preg_replace('~/.+~', '', $this->ipAddress);
//    }

}
