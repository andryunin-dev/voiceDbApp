<?php
namespace App\Models;

use App\Components\DateTimeService;
use T4\Core\Exception;
use T4\Dbal\Query;
use T4\Orm\Model;

/**
 * Class PhoneInfo
 * @package App\Models
 *
 * @property string $model
 * @property string $name
 * @property int $prefix
 * @property int $phoneDN
 * @property string $status
 * @property string $description
 * @property string $css
 * @property string $devicePool
 * @property string $alertingName
 * @property string $partition
 * @property string $timezone
 * @property boolean $dhcpEnabled
 * @property string $dhcpServer
 * @property string $domainName
 * @property string $tftpServer1
 * @property string $tftpServer2
 * @property string $defaultRouter
 * @property string $dnsServer1
 * @property string $dnsServer2
 * @property string $callManager1
 * @property string $callManager2
 * @property string $callManager3
 * @property string $callManager4
 * @property int $vlanId
 * @property string $userLocale
 * @property string $cdpNeighborDeviceId
 * @property string $cdpNeighborIP
 * @property string $cdpNeighborPort
 * @property string $cdpLastUpdate
 * @property string $publisherIp
 * @property boolean $unknownLocation
 * @property Appliance $phone
 */
class PhoneInfo extends Model
{
    private const SQL = [
        'findByMac' => 'SELECT * FROM equipment."phoneInfo" WHERE name LIKE (:mac)',
    ];
    private const CORRECT_CONNECTION_PORT = 1;

    protected static $schema = [
        'table' => 'equipment.phoneInfo',
        'columns' => [
            'model' => ['type' => 'string'],
            'name' => ['type' => 'string'],
            'prefix' => ['type' => 'int'],
            'phoneDN' => ['type' => 'int'],
            'status' => ['type' => 'string'],
            'description' => ['type' => 'string'],
            'css' => ['type' => 'string'],
            'devicePool' => ['type' => 'string'],
            'alertingName' => ['type' => 'string'],
            'partition' => ['type' => 'string'],
            'timezone' => ['type' => 'string'],
            'dhcpEnabled' => ['type' => 'boolean'],
            'dhcpServer' => ['type' => 'string'],
            'domainName' => ['type' => 'string'],
            'tftpServer1' => ['type' => 'string'],
            'tftpServer2' => ['type' => 'string'],
            'defaultRouter' => ['type' => 'string'],
            'dnsServer1' => ['type' => 'string'],
            'dnsServer2' => ['type' => 'string'],
            'callManager1' => ['type' => 'string'],
            'callManager2' => ['type' => 'string'],
            'callManager3' => ['type' => 'string'],
            'callManager4' => ['type' => 'string'],
            'vlanId' => ['type' => 'int'],
            'userLocale' => ['type' => 'string'],
            'cdpNeighborDeviceId' => ['type' => 'string'],
            'cdpNeighborIP' => ['type' => 'string'],
            'cdpNeighborPort' => ['type' => 'string'],
            'cdpLastUpdate' => ['type' => 'datetime'],
            'publisherIp' => ['type' => 'string'],
            'unknownLocation' => ['type' => 'boolean'],
        ],
        'relations' => [
            'phone' => ['type' => self::BELONGS_TO, 'model' => Appliance::class],
        ],
    ];


    protected function validate()
    {
        if (empty(trim($this->name))) {
            throw new Exception('PhoneInfo: Пустое значение Name');
        }

        if (!($this->phone instanceof Appliance)) {
            throw new Exception('PhoneInfo: Неверный тип Appliance');
        }

        if (!is_bool($this->unknownLocation)) {
            throw new Exception('PhoneInfo: Неверный тип UnknownLocation');
        }

        $phoneInfo = PhoneInfo::findByColumn('name', $this->name);

        if (true === $this->isNew && ($phoneInfo instanceof PhoneInfo)) {
            throw new Exception('Такой PhoneInfo уже существует');
        }

        if (true === $this->isUpdated && ($phoneInfo instanceof PhoneInfo) && ($phoneInfo->getPk() != $this->getPk())) {
            throw new Exception('Такой PlatformItem уже существует');
        }

        return true;
    }


    /**
     * Updating data on neighbors under the CDP protocol
     * @param string $deviceId
     * @param string $ip
     * @param string $port
     * @return $this
     * @throws \T4\Core\MultiException
     */
    public function updateCdpNeighborData(string $deviceId, string $ip, string $port)
    {
        $this
            ->fill([
                'cdpNeighborDeviceId' => $deviceId,
                'cdpNeighborIP' => $ip,
                'cdpNeighborPort' => $port,
                'cdpLastUpdate' => (new DateTimeService())->now(),
            ])
            ->save()
        ;
        return $this;
    }

    /**
     * Сhange the telephone office to the office of the switch to which it is connected
     * @param Appliance $cdpNeighbor
     * @return $this
     * @throws \T4\Core\MultiException
     */
    public function updateLocationByCdpNeighbor(Appliance $cdpNeighbor)
    {
        $this->phone->fill(['location' => $cdpNeighbor->location])->save();
        return $this;
    }

    /**
     * Сhecking the phone’s network connection port
     * @param int $connectionPort
     * @return bool
     */
    public function isCorrectConnectionPort(int $connectionPort): bool
    {
        return $connectionPort == self::CORRECT_CONNECTION_PORT;
    }

    /**
     * The amount of days since the last time the phone was available
     * @return int|null the amount of days
     * @throws \Exception
     */
    public function daysSinceLastUpdate()
    {
        return (new DateTimeService())->timeDifference($this->phone->lastUpdate)->days;
    }

    /**
     * The amount of hours since the last time the phone was available
     * @return int|null
     */
    public function hoursSinceLastUpdate()
    {
        return $this->phone->hoursSinceLastUpdate();
    }

    /**
     * @param string $mac
     * @return PhoneInfo|false
     */
    public static function findByMac(string $mac)
    {
        return PhoneInfo::findByQuery(
            new Query(self::SQL['findByMac']),
            [':mac' => '%' . $mac]
        );
    }

    /**
     * @return string
     */
    public function inventoryNumber(): string
    {
        return $this->phone->inventoryNumber();
    }
}
