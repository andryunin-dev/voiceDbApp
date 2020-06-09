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
    private $logger;

    /**
     * PhoneService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->logger = StreamLogger::instanceWith('PHONES_CDP_NEIGHBORS');
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
     * Updating data of phones connected to the polling switches
     */
    public function updateDataOfPhonesConnectedToPollingSwitches(): void
    {
        $switches = (new SwitchService())->switchesAvailableForPollingCdpNeighbors()->toArray();
        $dataOfPhonesConnectedToSwitches = $this->dataOfPhonesConnectedToSwitches($switches);
        array_walk(
            $dataOfPhonesConnectedToSwitches,
            function ($dataOfPhone) {
                try {
                    $phone = $this->phoneWithSEP($dataOfPhone['sep']);
                    if (false === $phone) {
                        throw new \Exception($dataOfPhone['sep'] . ' is unregistered phone');
                    }
                    $phone
                        ->updateCdpNeighborData(
                            $dataOfPhone['sw_name'],
                            $dataOfPhone['sw_ip'],
                            $dataOfPhone['sw_port']
                        )
                        ->updateLocationByCdpNeighbor(Appliance::findByPK($dataOfPhone['sw_id']));
                    if (!$phone->isCorrectConnectionPort($dataOfPhone['ph_port'])) {
                        throw new \Exception($dataOfPhone['sep'] . ' is connected on Port 2');
                    }
                } catch (\Throwable $e) {
                    $this->logger->error(
                        '[message]=' . $e->getMessage() .
                        ' [sep]=' . $dataOfPhone['sep'] .
                        ' [sw_id]=' . $dataOfPhone['sw_id']
                    );
                }
            }
        );
    }

    /**
     * Extended data of unregistered phones connected in the office
     * @param Office $office
     * @param int $age hours
     * @return array
     * @throws \Exception
     */
    public function extendedDataOfUnregisteredPhonesInOffice(Office $office, int $age): array
    {
        return array_map(
            function ($phone) {
                $phone['model'] = '';
                $phone['inventory_number'] = '';
                $phone['last_update'] = '';
                $phone['cdp_last_update'] = '';
                $phone['is_in_db'] = false;
                if (false !== $phoneInfo = PhoneInfo::findByColumn('name', $phone['sep'])) {
                    $phone['model'] = $phoneInfo->model ?? '';
                    $phone['inventory_number'] = $phoneInfo->phone->inventoryNumber();
                    $phone['last_update'] = $phoneInfo->phone->lastUpdate ?? '';
                    $phone['cdp_last_update'] = $phoneInfo->cdpLastUpdate ?? '';
                    $phone['is_in_db'] = true;
                }
                return $phone;
            },
            $this->dataOfUnregisteredPhonesConnectedInOffice($office, $age/24)
        );
    }

    /**
     * Data of phones connected in the office
     * and which are not in the database or the date of the last survey expired by more than $age
     * @param Office $office
     * @param int $age days
     * @return array
     */
    public function dataOfUnregisteredPhonesConnectedInOffice(Office $office, int $age)
    {
        return array_filter(
            $this->dataOfPhonesConnectedInOffice($office),
            function ($dataOfPhone) use ($age) {
                $macLength = 12;
                $mac = mb_substr($dataOfPhone['sep'], -$macLength);
                if (mb_strlen($mac) != $macLength) {
                    return false;
                }
                $phone = PhoneInfo::findByMac($mac);
                return false == $phone
                    || (false !== $phone && $phone->amountOfDaysSinceTheLastTimeThePhoneWasAvailable() > $age);
            }
        );
    }

    /**
     * Data of phones connected in the office
     * @param Office $office
     * @return array
     */
    public function dataOfPhonesConnectedInOffice(Office $office)
    {
        return $this->dataOfPhonesConnectedToSwitches(
            (new SwitchService())->liveSwitchesInOffice($office)->toArray()
        );
    }

    /**
     * Data of phones connected to the switches
     * @param array $switches
     * @return array
     */
    public function dataOfPhonesConnectedToSwitches(array $switches): array
    {
        $dataOfPhonesConnectedToSwitches = [];
        array_walk(
            $switches,
            function ($switch) use (&$dataOfPhonesConnectedToSwitches) {
                try {
                    $dataOfPhonesConnectedToSwitches = array_merge(
                        $dataOfPhonesConnectedToSwitches,
                        $this->dataOfPhonesConnectedToSwitch($switch)
                    );
                } catch (\Throwable $e) {
                    $this->logger->error('[message]=' . $e->getMessage() . ' [sw_id]=' . $switch->getPk());
                }
            }
        );
        return $dataOfPhonesConnectedToSwitches;
    }

    /**
     * Data of phones connected to the switch
     * @param Appliance $switch
     * @return array
     * @throws \Exception
     */
    public function dataOfPhonesConnectedToSwitch(Appliance $switch): array
    {
        $switch = new CiscoSwitch($switch);
        return array_map(
            function ($phone) use ($switch) {
                $phone['sw_name'] = $switch->hostname();
                $phone['sw_ip'] = $switch->managementIp();
                $phone['sw_id'] = $switch->getPk();
                return $phone;
            },
            $switch->cdpPhoneNeighborsData()
        );
    }
}
