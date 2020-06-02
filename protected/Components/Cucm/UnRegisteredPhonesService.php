<?php
namespace App\Components\Cucm;

use App\Components\StreamLogger;
use App\Components\Swiitch\CiscoSwitch;
use App\Components\Swiitch\SwitchService;
use App\Models\Appliance;
use App\Models\Office;
use App\Models\PhoneInfo;

class UnRegisteredPhonesService
{
    private $logger;

    /**
     * UnRegisteredPhonesService constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->logger = StreamLogger::instanceWith('PHONES_CDP_NEIGHBORS');
    }


    /**
     * Data on phones connected to the switch but not in the database,
     * or having the amount of time elapsed since the last time the phone was available
     * more than the LIFETIME
     * @param Appliance $switch
     * @return array
     * @throws \Exception
     */
    public function dataOnUnregisteredPhonesConnectedToSwitch(Appliance $switch): array
    {
        $switch = new CiscoSwitch($switch);
        return array_values(
            array_map(
                function ($phone) use ($switch) {
                    try {
                        (new CdpPhoneService())->updateDataOnPhoneCdpNeighborConnectedToSwitch($phone, $switch);
                    } catch (\Throwable $e) {
                    }
                    $phone['sw_name'] = $switch->hostname();
                    $phone['sw_ip'] = $switch->managementIp();
                    return $phone;
                },
                array_filter(
                    $switch->cdpPhoneNeighborsData(),
                    function ($cdpPhoneNeighbor) {
                        $phone = PhoneInfo::findByColumn('name', $cdpPhoneNeighbor['sep']);
                        return false == $phone
                            || (false !== $phone && $phone->amountOfDaysSinceTheLastTimeThePhoneWasAvailable() > PhoneInfo::LIFETIME);
                    }
                )
            )
        );
    }

    /**
     * Data on unregistered phones connected in the office
     * @param Office $office
     * @return array
     * @throws \Exception
     */
    public function dataOnUnregisteredPhonesInOffice(Office $office): array
    {
        $dataOnUnregisteredPhones = [];
        $switches = (new SwitchService())->liveSwitchesInOffice($office)->toArray();
        array_walk(
            $switches,
            function ($switch) use (&$dataOnUnregisteredPhones) {
                if ($switch->isPartOfCluster() && false === $switch->managementIp) {
                    return;
                }
                try {
                    $dataOnUnregisteredPhones = array_merge(
                        $dataOnUnregisteredPhones,
                        $this->dataOnUnregisteredPhonesConnectedToSwitch($switch)
                    );
                } catch (\Throwable $e) {
                    $this->logger->error('[message]=' . $e->getMessage() . ' [sw_id]=' . $switch->getPk());
                }
            }
        );
        return $dataOnUnregisteredPhones;
    }

    /**
     * Extended data on unregistered phones connected in the office
     * @param Office $office
     * @return array
     * @throws \Exception
     */
    public function extendedDataOnUnregisteredPhonesInOffice(Office $office): array
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
            $this->dataOnUnregisteredPhonesInOffice($office)
        );
    }
}
