<?php

namespace App\Models;

use App\Components\Ip;
use App\Components\IpTools;
use T4\Core\Collection;
use T4\Core\Exception;
use T4\Core\IArrayable;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class DataPort
 * @package App\Models
 * имя порта писать в details->portName
 *
 * @property string $ipAddress
 * @property string $cidrIpAddress
 * @property int $masklen
 * @property Network $network
 * @property string $macAddress
 * @property string $details
 * @property string $comment
 * @property bool $isManagement
 *
 * @property Appliance $appliance
 * @property DPortType $portType
 * @property Vrf $vrf
 */
class DataPort extends Model
{
    protected static $schema = [
        'table' => 'equipment.dataPorts',
        'columns' => [
            'ipAddress' => ['type' => 'string'],
            'masklen' => ['type' => 'int'],
            'macAddress' => ['type' => 'string'],
            'details' => ['type' => 'json'],
            'comment' => ['type' => 'text'],
            'isManagement' => ['type' => 'boolean']
        ],
        'relations' => [
            'appliance' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
            'portType' => ['type' => self::BELONGS_TO, 'model' => DPortType::class, 'by' => '__type_port_id'],
            'network' => ['type' => self::BELONGS_TO, 'model' => Network::class, 'by' => '__network_id']
        ]
    ];

    public $vrf;

    protected function getCidrIpAddress()
    {
        $ip = new IpTools($this->ipAddress, $this->masklen);
        if ($ip->is_valid) {
            return $ip->cidrAddress;
        } else {
            return null;
        }
//        $key = 'ipAddress';
//        $masklen = $this->masklen;
//        $address = isset($this->__data[$key]) ? $this->__data[$key] : null;
//        $ip = new IpTools($address, $masklen);
//        if ($ipObj->is_valid) {
//            return $ipObj->cidrAddress;
//        } else {
//            return null;
//        }
//        if (empty($masklen)) {
//            return isset($this->__data[$key]) ? $this->__data[$key] : null;
//        } else {
//            return isset($this->__data[$key]) ? ($this->__data[$key] . '/' . $masklen) : null;
//        }
    }
    public function __set($key, $val)
    {
        if ('ipAddress' == $key) {
            //перенес из фреймворка
            $validateMethod = 'validate' . ucfirst($key);
            if (method_exists($this, $validateMethod)) {

                $validateResult = $this->$validateMethod($val);
                if (false === $validateResult) {
                    return;
                }

                if ($validateResult instanceof \Generator) {
                    $errors = new MultiException();
                    foreach ($validateResult as $error) {
                        if ($error instanceof \Exception) {
                            $errors[] = $error;
                        }
                    }
                    if (!$errors->isEmpty()) {
                        throw $errors;
                    }
                }
                if (isset($this->isNew) && false === $this->isNew) {
                    $this->isUpdated = true;
                }
            }
            //================
            $ip = new IpTools($val);
            if ($ip->is_valid) {
                parent::__set('masklen', $ip->masklen);
                parent::__set('ipAddress', $ip->address);
            }
        } else {
            parent::__set($key, $val);
        }
    }

    protected function getVrf()
    {
        return empty($this->vrf) ? $this->vrf = $this->network->vrf : $this->vrf;
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

    public function fill($data)
    {
        if ($data instanceof IArrayable) {
            $data = $data->toArray();
        } else {
            $data = (array)$data;
        }
        if (array_key_exists('vrf', $data) && ($data['vrf'] instanceof Vrf) || null === ($data['vrf'])) {
            $this->vrf = $data['vrf'];
            unset($data['vrf']);
        } else {
            throw new Exception('Неверно задан VRF');
        }
        return parent::fill($data);
    }


    /**
     * не может быть пустым
     * адрес должен быть валидным
     * должна быть явно задана маска
     * адрес не должен быть адресом сети
     *
     * @param $val
     * @return bool
     * @throws Exception
     *
     */
    protected function validateIpAddress($val)
    {
        $ip = new IpTools($val);
        if (false === $ip->is_valid) {
            throw new Exception(implode('<br>', $ip->errors));
        }
        if ($ip->is_valid && false === $ip->is_maskNull && false === $ip->is_hostIp) {
            throw new Exception($ip->cidrAddress . ' - не является адресом хоста' );
        }
        return true;
    }

    /**
     * не должен быть пустым
     * должен быть валидным
     *
     * @param $val
     * @return bool
     * @throws Exception
     */
    protected function validateMacAddress($val)
    {
        if (empty(trim($val))) {
            return false;
        }
        if (!empty(trim($val)) && false === filter_var(trim($val), FILTER_VALIDATE_MAC)) {
            throw new Exception($val . ' - Неверный формат MAC адреса');
        }
        return true;
    }


    protected function sanitizeMacAddress($val)
    {
        return filter_var(trim($val), FILTER_VALIDATE_MAC);
    }

    protected function sanitizeComment($val)
    {
        return trim($val);
    }

    protected function sanitizeDetails($details)
    {
        if (is_array($details)) {
            foreach ($details as $key => $item) {
                $details[$key] = trim($item);
            }
        }
        return $details;
    }

    protected function validate()
    {
        $ip = new IpTools($this->ipAddress, $this->masklen);
        if (false === $this->appliance) {
            throw new Exception('Устройство не найдено');
        }
        if (false === $this->portType) {
            throw new Exception('Данный тип порта не найден');
        }

        //ищем записи с таким ip для новой записи
        if (true === $this->isNew && self::countAllByIpVrf($this->ipAddress, $this->vrf) > 0) {
            throw new Exception('IP адрес ' . $ip->address . ' уже используется.');
        }
        //валидация при изменении существующей записи
        if (true === $this->isUpdated) {
            $fromDb = self::findByIpVrf($this->ipAddress, $this->vrf);
            if (false !== $fromDb && $fromDb->getPk() != $this->getPk()) {
                throw new Exception('IP адрес ' . $ip->address . ' уже используется.');
            }
        }

        return true;
    }

    /**
     * назначаем подсеть для ip адреса данного порта и сохраняем ее.
     * если именяемый порт имел сетку с 32 маской - удаляем ее
     * @return bool
     * @throws Exception
     */
    protected function beforeSave()
    {
        $ip = (new IpTools($this->ipAddress, $this->masklen));
        //если маска не нулевая, то ищем сетку для него
        if ($this->isNew && !$ip->is_maskNull) {
            if (false === $network = Network::findByAddressVrf($ip->cidrNetwork, $this->vrf)) {
                $network = (new Network())
                    ->fill([
                        'address' => ($ip->cidrNetwork),
                        'vrf' => $this->vrf
                    ])
                    ->save();
                $network->refresh();
                //new network must not contain any subnets
                if ($network->children->count() > 0) {
                    $network->delete();
                    throw new Exception('Ошибка при создании сети ' . $network->address . ' для хоста ' . $this->cidrIpAddress . '. Данная сеть разбита на подсети. Использование для хостовых IP невозможно.');
                }
            } elseif ($network->children->count() > 0) {
                throw new Exception('Сеть ' . $network->address . ' разбита на подсети. Использование для хостовых IP невозможно. Хост IP - ' . $this->cidrIpAddress);
            }
            $this->network = $network;
        } elseif ($this->isUpdated && !$ip->is_maskNull) {
            if (null !== $this->vrf && false !== $network = Network::findByAddressVrf($ip->cidrNetwork, $this->vrf)) {
                //if net for new IP exists, it must not contain subnets.
                if ($network->children->count() > 0) {
                    throw new Exception('Сеть ' . $network->address . ' разбита на подсети. Использование для хостовых IP невозможно. Хост IP - ' . $this->cidrIpAddress);
                }
                $this->network = $network;
            } else {
                //network for new IP not found. Create it
                $network = (new Network())
                    ->fill([
                        'address' => ($ip->cidrNetwork),
                        'vrf' => $this->vrf
                    ]);
                $network->validate();
                //delete old DataPort
                DataPort::findByPK($this->getPk())->delete();
                //change current updated Data Port like new object
                $this->isUpdated = false;
                $this->isNew = true;
                //try save new network
                $network->save();
                $network->refresh();
                //new network must not contain any subnets
                if ($network->children->count() > 0) {
                    $network->delete();
                    throw new Exception('Ошибка при создании сети ' . $network->address . ' для хоста ' . $this->cidrIpAddress . '. Данная сеть разбита на подсети. Использование для хостовых IP невозможно.');
                }
                $this->network = $network;
            }
        }

        if (true === $this->isNew && null === $this->isManagement) {
            $this->isNotManagement();
        }

        return parent::beforeSave();
    }

    public function save()
    {
        if ($this->isUpdated) {
            $oldNetwork = (DataPort::findByPK($this->getPk()))->network;
            $saveResult = parent::save();
            if (false !== $saveResult &&
                null !== $oldNetwork &&
                32 == (new Ip($oldNetwork->address))->masklen &&
                $this->network->address != $oldNetwork->address) {
                $oldNetwork->delete();
            }
            return $saveResult;
        } else {
            return parent::save();
        }
    }

    protected function afterDelete()
    {
        if (null !== $this->network && 32 == (new IpTools($this->network->address))->masklen) {
            $this->network->delete();
        }
        return parent::afterDelete();
    }

    /**
     * @param $ip
     * @param Vrf $vrf
     * @return int
     */
    public static function countAllByIpVrf($ip, Vrf $vrf)
    {
        return self::findAllByIpVrf($ip, $vrf)->count();
    }

    /**
     * @param $ip
     * @param Vrf $vrf
     * @return bool|Collection|DataPort[]
     */
    public static function findAllByIpVrf($ip, $vrf)
    {
        $query = (new Query())
            ->select()
            ->from(DataPort::getTableName())
            ->where('host("ipAddress") = host(:ip)')
            ->params([':ip' => $ip]);

        /**
         * @var Collection|bool $result
         */
        $result = DataPort::findAllByQuery($query);
        $result = $result->filter(function ($dPort) use ($vrf) {
            /**
             * @var DataPort $dPort
             * @var Vrf $vrf
             */
            if (null === $dPort->network) {
                return true;
            } else {
                return ($dPort->network->vrf->rd == $vrf->rd);
            }
        });
        return (null === $result) ? false : $result;
    }

    /**
     * @param $ip
     * @param Vrf $vrf
     * @return DataPort|bool
     */
    public static function findByIpVrf($ip, $vrf)
    {
        $result = self::findAllByIpVrf($ip, $vrf)->first();
        return (null === $result) ? false : $result;
    }


    public function isManagement()
    {
        $this->isManagement = true;
    }

    public function isNotManagement()
    {
        $this->isManagement = false;
    }

    public function trimIpAddress()
    {
        return preg_replace('~/.+~', '', $this->ipAddress);
    }

}
