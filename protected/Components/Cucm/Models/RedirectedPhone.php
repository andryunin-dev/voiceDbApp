<?php
namespace App\Components\Cucm\Models;

use App\ViewModels\DbaTrait;
use App\ViewModels\ViewHelperTrait;
use T4\Orm\Model;

/**
 * Class RedirectedPhone
 * @package App\Components\Cucm\Models
 *
 * @property string $device
 * @property string $depiction
 * @property string $css
 * @property string $devicepool
 * @property string $phprefix
 * @property string $phonedn
 * @property string $alertingname
 * @property string $forwardall
 * @property string $forward_all_mail
 * @property string $forwardbusyinternal
 * @property string $forwardbusyexternal
 * @property string $forward_no_answer_internal
 * @property string $forward_no_answer_external
 * @property string $forward_unregistred_internal
 * @property string $forward_unregistred_external
 * @property string $cfnaduration
 * @property string $partition
 * @property string $model
 * @property string $cucmIp
 * @property \DateTime $lastUpdate
 */
class RedirectedPhone extends Model
{
    use ViewHelperTrait;
    use DbaTrait;

    private const SQL = [
        'findByDeviceAndCucm' => 'SELECT * FROM cucm."redirectedPhones" WHERE device = :device AND cucm = :cucm',
        'findAllByCallForwardingNumber' => 'SELECT * FROM cucm."redirectedPhones" WHERE forwardall = :call_forwarding_number OR forwardbusyinternal = :call_forwarding_number OR forwardbusyexternal = :call_forwarding_number OR forward_no_answer_internal = :call_forwarding_number OR forward_no_answer_external = :call_forwarding_number OR forward_unregistred_internal = :call_forwarding_number OR forward_unregistred_external = :call_forwarding_number',
    ];

    protected static $schema = [
        'table' => 'cucm.redirectedPhones',
        'columns' => [
            'device' => ['type' => 'string'],
            'depiction' => ['type' => 'string'],
            'css' => ['type' => 'string'],
            'devicepool' => ['type' => 'string'],
            'phprefix' => ['type' => 'string'],
            'phonedn' => ['type' => 'string'],
            'alertingname' => ['type' => 'string'],
            'forwardall' => ['type' => 'string'],
            'forward_all_mail' => ['type' => 'string'],
            'forwardbusyinternal' => ['type' => 'string'],
            'forwardbusyexternal' => ['type' => 'string'],
            'forward_no_answer_internal' => ['type' => 'string'],
            'forward_no_answer_external' => ['type' => 'string'],
            'forward_unregistred_internal' => ['type' => 'string'],
            'forward_unregistred_external' => ['type' => 'string'],
            'cfnaduration' => ['type' => 'string'],
            'partition' => ['type' => 'string'],
            'model' => ['type' => 'string'],
            'cucm' => ['type' => 'string'],
            'lastUpdate' => ['type' => 'datetime'],
        ]
    ];

    public static $columnMap = [];

    protected static $sortOrders = [
        'default' => 'cucm, css, phprefix asc',
    ];

    protected function validate()
    {
        $objFromDb = self::findByDeviceAndCucm($this->device, $this->cucmIp);
        if (true === $this->isNew && false !== $objFromDb) {
            throwException('Такой Redirected Phone уже существует');
        }
        if (true === $this->isUpdated && false !== $objFromDb && $this->getPk() != $objFromDb->getPk()) {
            throwException('Такой Redirected Phone уже существует');
        }
        return true;
    }

    /**
     * @throws \T4\Core\MultiException
     */
    public function persist(): void
    {
        $objFromDb = self::findByDeviceAndCucm($this->device, $this->cucmIp);
        if (false === $objFromDb) {
            $this->save();
        } else {
            $objFromDb
                ->fill($this)
                ->save();
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isOverdue(): bool
    {
        $currentDate = (new \DateTime())->format('Y-m-d');
        $lastUpdate = (new \DateTime($this->lastUpdate))->format('Y-m-d');
        return strtotime($lastUpdate) < strtotime($currentDate);
    }

    /**
     * @param string $callForwardingNumber
     * @return RedirectedPhone
     */
    public static function findAllByCallForwardingNumber(string $callForwardingNumber)
    {
        return self::findAllByQuery(
            self::SQL['findAllByCallForwardingNumber'],
            [
                'call_forwarding_number' => $callForwardingNumber,
            ]
        );
    }

    /**
     * @param string $device
     * @param string $cucmIp
     * @return RedirectedPhone|false
     */
    public static function findByDeviceAndCucm(string $device, string $cucmIp)
    {
        return self::findByQuery(
            self::SQL['findByDeviceAndCucm'],
            [
                'device' => $device,
                'cucm' => $cucmIp,
            ]
        );
    }
}
