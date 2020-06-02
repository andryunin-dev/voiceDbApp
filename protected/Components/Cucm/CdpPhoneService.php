<?php
namespace App\Components\Cucm;

use App\Components\StreamLogger;
use App\Components\Swiitch\CiscoSwitch;
use App\Components\Swiitch\SwitchService;
use App\Models\Appliance;
use App\Models\Office;
use App\Models\PhoneInfo;

class CdpPhoneService
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
     * Updating data on phone CDP neighbors connected in the office
     * @param Office $office
     */
    public function updateDataOnPhoneCdpNeighborsConnectedInOffice(Office $office): void
    {
        $this->updateDataOnPhoneCdpNeighborsConnectedToSwitches(
            (new SwitchService())->liveSwitchesInOffice($office)->toArray()
        );
    }

    /**
     * Updating data on phone CDP neighbors connected to the polling switches
     */
    public function updateDataOnPhoneCdpNeighborsConnectedToPollingSwitches(): void
    {
        $this->updateDataOnPhoneCdpNeighborsConnectedToSwitches(
            (new SwitchService())->switchesAvailableForPollingCdpNeighbors()->toArray()
        );
    }

    /**
     * Updating data on phone CDP neighbors connected to the switches
     * @param array $switches - array of Appliances
     */
    public function updateDataOnPhoneCdpNeighborsConnectedToSwitches(array $switches): void
    {
        array_walk(
            $switches,
            function ($switch) {
                if ($switch->isPartOfCluster() && false === $switch->managementIp) {
                    return;
                }
                try {
                    $this->updateDataOnPhoneCdpNeighborsConnectedToSwitch($switch);
                } catch (\Throwable $e) {
                    $this->phonesCdpNeighborsLogger->error(
                        '[message]=' . $e->getMessage() .
                        ' [sw_ip]=' . $switch->managementIp
                    );
                }
            }
        );
    }

    /**
     * Updating data on phone CDP neighbors connected to the switch
     * @param Appliance $switch
     * @throws \Exception
     */
    public function updateDataOnPhoneCdpNeighborsConnectedToSwitch(Appliance $switch): void
    {
        $switch = new CiscoSwitch($switch);
        $cdpPhoneNeighborsData = $switch->cdpPhoneNeighborsData();
        array_walk(
            $cdpPhoneNeighborsData,
            function ($cdpPhoneNeighborData) use ($switch) {
                try {
                   $this->updateDataOnPhoneCdpNeighborConnectedToSwitch(
                       $cdpPhoneNeighborData,
                       $switch
                   );
                } catch (\Throwable $e) {
                    $this->phonesCdpNeighborsLogger->error(
                        '[message]=' . $e->getMessage() .
                        ' [sw_ip]=' . $switch->managementIp() .
                        ' [sep]=' . $cdpPhoneNeighborData['sep']
                    );
                }
            }
        );
    }

    /**
     * Updating data on phone CDP neighbor connected to the switch
     * @param array $cdpPhoneNeighborData
     * @param CiscoSwitch $switch
     * @throws \T4\Core\MultiException
     */
    public function updateDataOnPhoneCdpNeighborConnectedToSwitch(array $cdpPhoneNeighborData, CiscoSwitch $switch): void
    {
        $phone = $this->phoneWithSEP($cdpPhoneNeighborData['sep']);
        if (false === $phone) {
            throw new \Exception($cdpPhoneNeighborData['sep'] . ' is unregistered phone');
        }
        $phone
            ->updateCdpNeighborData(
                $switch->hostname(),
                $switch->managementIp(),
                $cdpPhoneNeighborData['sw_port']
            )
            ->updateLocationByCdpNeighbor($switch->appliance())
        ;
        if ($phone->amountOfDaysSinceTheLastTimeThePhoneWasAvailable() > PhoneInfo::LIFETIME) {
            throw new \Exception($phone->name . ' is unregistered phone');
        }
        if (!$phone->isCorrectConnectionPort($cdpPhoneNeighborData['ph_port'])) {
            throw new \Exception($phone->name . ' is connected on Port 2');
        }
    }
}
