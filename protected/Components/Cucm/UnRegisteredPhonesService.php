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
        return [
            'unregistered_phones' => array_merge(
                array_filter(
                    (new CiscoSwitch($id))->cdpPhoneNeighborsData(),
                    function ($phone) {
                        return false == PhoneInfo::findByColumn('name', $phone['phone_name']);
                    }
                )
            )
        ];
    }

    /**
     * Data on the phones connected in the office but not existing in the database
     * @param int $id
     * @return array
     */
    public function dataOnUnregisteredPhonesInOffice(int $id): array
    {
        return array_map(
            function ($switch) {
                return [
                    'switch_id' => $switch->getPk(),
                    'unregistered_phones' => $this->dataOnUnregisteredPhonesConnectedToSwitch($switch->getPk())['unregistered_phones'],
                ];
            },
            (new SwitchService())->liveSwitchesInOffice(
                    Office::findByPK($id)->lotusId
            )->toArray()
        );
    }
}
