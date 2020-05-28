<?php
namespace App\Components\Phones;

use App\Components\StreamLogger;
use App\Components\Swiitch\CiscoSwitch;
use App\Components\Swiitch\SwitchService;
use App\Models\PhoneInfo;

class PhoneService
{
    private $phonesCdpNeighborsLogger;

    /**
     * PhoneService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->phonesCdpNeighborsLogger = StreamLogger::instanceWith('PHONES_CDP_NEIGHBORS');
    }

    /**
     * @param string $sep
     * @return PhoneInfo|false
     */
    public function phoneWithSEP(string $sep)
    {
        return PhoneInfo::findByColumn('name', $sep);
    }

    /**
     * Updating data on phone neighbors under the CDP protocol
     * @throws \Exception
     */
    public function updateDataOnPhoneCdpNeighborsConnectedToSwitches()
    {
        $switches = (new SwitchService())->switchesAvailableForPollingCdpNeighbors()->toArray();
        array_walk(
            $switches,
            function ($switch) {
                if ($switch->isPartOfCluster() && false === $switch->managementIp) {
                    return;
                }
                try {
                    $cdpPhoneNeighborsData = (new CiscoSwitch($switch->getPk()))->cdpPhoneNeighborsData();
                    array_walk(
                        $cdpPhoneNeighborsData,
                        function ($cdpPhoneNeighborData) use ($switch) {
                            try {
                                $phone = $this->phoneWithSEP($cdpPhoneNeighborData['sep']);
                                if (false === $phone) {
                                    throw new \Exception($phone->name . ' is unregistered phone');
                                }
                                $phone
                                    ->updateCdpNeighborData(
                                        $switch->hostname(),
                                        $switch->managementIp,
                                        $cdpPhoneNeighborData['sw_port']
                                    )
                                    ->updateLocationByCdpNeighbor($switch)
                                ;
                                if ($phone->amountOfDaysSinceTheLastTimeThePhoneWasAvailable() > PhoneInfo::LIFETIME) {
                                    throw new \Exception($phone->name . ' is unregistered phone');
                                }
                                if (!$phone->isCorrectConnectionPort($cdpPhoneNeighborData['ph_port'])) {
                                    throw new \Exception($phone->name . ' is connected on Port 2');
                                }
                            } catch (\Throwable $e) {
                                $this->phonesCdpNeighborsLogger->error(
                                    '[message]=' . $e->getMessage() .
                                    ' [sw_ip]=' . $switch->managementIp .
                                    ' [sep]=' . $cdpPhoneNeighborData['sep']
                                );
                            }
                        }
                    );
                } catch (\Throwable $e) {
                    $this->phonesCdpNeighborsLogger->error(
                        '[message]=' . $e->getMessage() .
                        ' [sw_ip]=' . $switch->managementIp
                    );
                }
            }
        );
    }
}
