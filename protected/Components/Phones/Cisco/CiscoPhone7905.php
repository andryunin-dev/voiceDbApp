<?php
namespace App\Components\Phones\Cisco;

class CiscoPhone7905 extends CiscoPhone
{
    protected const CONNECT_TIME_OUT = 10; // seconds

    /**
     * @return array
     * @throws \Exception
     */
    public function realtimeData(): array
    {
        return array_merge($this->xmlBasicInfo(), $this->htmlNetInfo(), $this->xmlPortInfo());
    }

    protected function htmlNetInfo(): array
    {
        $content = $this->urlContent('http://' . $this->ip . '/NetworkConfiguration');
        if (false === $content) {
            throw new \Exception('NetInfo is not available');
        }
        libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $document->loadHTML($content);
        $fieldsMap = [
            'subnetmask' => 'subNetMask',
            'dhcpenabled' => 'dhcpEnabled',
            'dhcpserver' => 'dhcpServer',
            'domainname' => 'domainName',
            'tftpserver1' => 'tftpServer1',
            'tftpserver2' => 'tftpServer2',
            'defaultrouter' => 'defaultRouter',
            'defaultrouter1' => 'defaultRouter',
            'dnsserver1' => 'dnsServer1',
            'dnsserver2' => 'dnsServer2',
            'callmanager1' => 'callManager1',
            'callmanager2' => 'callManager2',
            'callmanager3' => 'callManager3',
            'callmanager4' => 'callManager4',
            'vlanid' => 'vlanId',
            'userlocale' => 'userLocale',
        ];
        $netCInfo = [
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
        foreach (explode("\n", $document->textContent) as $field) {
            $field = mb_strtolower(mb_ereg_replace(' +', '', $field));
            foreach ($fieldsMap as $key => $value) {
                if (mb_ereg_match($key, $field)) {
                    $netCInfo[$value] = mb_ereg_replace($key, '', $field);
                    break;
                }
            }
        }
        return $netCInfo;
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
