<?php
namespace App\Components\Cucm;

use App\Components\Swiitch\CiscoSwitch;
use App\Components\Swiitch\SwitchService;
use App\Models\Office;
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
     * @param int $id
     * @return array
     */
    public function dataOnUnregisteredPhonesInOffice(int $id): array
    {
        $dataOnUnregisteredPhones = [];
        array_map(
            function ($switch) use (&$dataOnUnregisteredPhones) {
                $dataOnUnregisteredPhones = array_merge(
                    $dataOnUnregisteredPhones,
                    $this->dataOnUnregisteredPhonesConnectedToSwitch($switch->getPk())
                );
            },
            (new SwitchService())->liveSwitchesInOffice(
                Office::findByPK($id)->lotusId
            )->toArray()
        );
        return $dataOnUnregisteredPhones;
    }
}
