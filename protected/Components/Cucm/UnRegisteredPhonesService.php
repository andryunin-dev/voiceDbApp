<?php
namespace App\Components\Cucm;

use App\Components\StreamLogger;
use App\Components\Swiitch\CiscoSwitch;
use App\Components\Swiitch\SwitchService;
use App\Models\PhoneInfo;

class UnRegisteredPhonesService
{
    /**
     * Data on the phones connected to the switch but not existing in the database
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function dataOnUnregisteredPhonesConnectedToSwitch(int $id): array
    {
        $switch = new CiscoSwitch($id);
        return array_values(
            array_map(
                function ($phone) use ($switch) {
                    $phone['sw_name'] = $switch->hostname() ?? '';
                    $phone['sw_ip'] = $switch->managementIp();
                    return $phone;
                },
                array_filter(
                    $switch->cdpPhoneNeighborsData(),
                    function ($phone) {
                        return false == PhoneInfo::findByColumn('name', $phone['sep']);
                    }
                )
            )
        );
    }

    /**
     * Data on the phones connected in the office but not existing in the database
     * @param int $lotusId
     * @return array
     * @throws \Exception
     */
    public function dataOnUnregisteredPhonesInOffice(int $lotusId): array
    {
        $logger = StreamLogger::instanceWith('PHONES_CDP_NEIGHBORS');
        $dataOnUnregisteredPhones = [];
        array_map(
            function ($switch) use (&$dataOnUnregisteredPhones, $logger) {
                if ($switch->isPartOfCluster() && false === $switch->managementIp) {
                    return;
                }
                try {
                    $dataOnUnregisteredPhones = array_merge(
                        $dataOnUnregisteredPhones,
                        $this->dataOnUnregisteredPhonesConnectedToSwitch($switch->getPk())
                    );
                } catch (\Throwable $e) {
                    $logger->error('[message]=' . $e->getMessage() . ' [sw_id]=' . $switch->getPk());
                }
            },
            (new SwitchService())->liveSwitchesInOffice($lotusId)->toArray()
        );
        return $dataOnUnregisteredPhones;
    }
}
