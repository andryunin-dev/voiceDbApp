<?php
namespace App\Components\Phones\Cisco;

class CiscoPhone7937 extends CiscoPhone
{
    /**
     * @return array
     * @throws \Exception
     */
    protected function xmlPortInfo(): array
    {
        return [
            'cdpNeighborDeviceId' => '',
            'cdpNeighborIP' => '',
            'cdpNeighborPort' => '',
        ];
    }
}
