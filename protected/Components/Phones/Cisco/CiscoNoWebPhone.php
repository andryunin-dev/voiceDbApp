<?php
namespace App\Components\Phones\Cisco;

class CiscoNoWebPhone extends CiscoPhone
{
    /**
     * @return array
     * @throws \Exception
     */
    protected function xmlBasicInfo(): array
    {
        return [
            'name' => '',
            'serialNumber' => '',
            'modelNumber' => '',
            'versionID' => '',
            'appLoadID' => '',
            'timezone' => '',
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function xmlNetInfo(): array
    {
        return [
            'name' => '',
            'subNetMask' => '',
            'dhcpEnabled' => '',
            'dhcpServer' => '',
            'domainName' => '',
            'tftpServer1' => '',
            'tftpServer2' => '',
            'defaultRouter' => '',
            'dnsServer1' => '',
            'dnsServer2' => '',
            'callManager1' => '',
            'callManager2' => '',
            'callManager3' => '',
            'callManager4' => '',
            'vlanId' => '',
            'userLocale' => '',
        ];
    }

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
