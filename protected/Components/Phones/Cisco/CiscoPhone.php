<?php
namespace App\Components\Phones\Cisco;

use App\Components\IpTools;
use App\Components\Phones\Connectable;

class CiscoPhone implements Connectable
{
    protected const CONNECT_TIME_OUT = 2; // seconds
    protected const HTTP_CODE_OK = 200;
    protected $ip;

    /**
     * CiscoPhone constructor.
     * @param string $ip
     */
    public function __construct(string $ip)
    {
        $this->ip = (new IpTools($ip))->address;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function realtimeData(): array
    {
        return array_merge($this->xmlBasicInfo(), $this->xmlNetInfo(), $this->xmlPortInfo());
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function xmlBasicInfo(): array
    {
        $content = $this->urlContent('http://' . $this->ip . '/DeviceInformationX');
        if (false === $content) {
            throw new \Exception('PhoneInfo is not available');
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        if (false == $xml) {
            $content = iconv('WINDOWS-1251', 'UTF-8', '<?xml version="1.0" encoding="utf-8"?>' . explode('?>', $content)[1]);
            $xml = simplexml_load_string($content);
        }
        if (false == $xml) {
            throw new \Exception('PhoneInfo xml parse error');
        }
        $fieldsMap = [
            'hostname' => 'name',
            'serialnumber' => 'serialNumber',
            'modelnumber' => 'modelNumber',
            'versionid' => 'versionID',
            'apploadid' => 'appLoadID',
            'timezone' => 'timezone',
        ];
        $basicInfo = [
            'name' => '',
            'serialNumber' => '',
            'modelNumber' => '',
            'versionID' => '',
            'appLoadID' => '',
            'timezone' => '',
        ];
        foreach (get_object_vars($xml) as $field => $value) {
            if (array_key_exists(mb_strtolower($field), $fieldsMap)) {
                $field = $fieldsMap[mb_strtolower($field)];
                $basicInfo[$field] = trim((string)$value);
            }
        }
        return $basicInfo;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function xmlNetInfo(): array
    {
        $content = $this->urlContent('http://' . $this->ip . '/NetworkConfigurationX');
        if (false === $content) {
            throw new \Exception('PhoneNetConf is not available');
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        if (false == $xml) {
            $content = iconv('WINDOWS-1251', 'UTF-8', '<?xml version="1.0" encoding="utf-8"?>' . explode('?>', $content)[1]);
            $xml = simplexml_load_string($content);
        }
        if (false == $xml) {
            $content = explode('<NetworkLocale>', $content)[0] . '</NetworkConfiguration>';
            $xml = simplexml_load_string($content);
        }
        if (false == $xml) {
            throw new \Exception('PhoneNetConf xml parse error');
        }
        $fieldsMap = [
            'hostname' => 'name',
            'subnetmask' => 'subNetMask',
            'macaddress' => 'macAddress',
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
        $netConf = [
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
        foreach (get_object_vars($xml) as $field => $value) {
            if (array_key_exists(mb_strtolower($field), $fieldsMap)) {
                $field = $fieldsMap[mb_strtolower($field)];
                $netConf[$field] = trim((string)$value);
            }
        }
        return $netConf;
    }

    /**
     * @return array
     * @throws \Exception
     */
    protected function xmlPortInfo(): array
    {
        $content = $this->urlContent('http://' . $this->ip . '/PortInformationX?1');
        if (false === $content) {
            throw new \Exception('PhonePortInfo is not available');
        }
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        if (false == $xml) {
            $content = iconv('WINDOWS-1251', 'UTF-8', '<?xml version="1.0" encoding="utf-8"?>' . explode('?>', $content)[1]);
            $xml = simplexml_load_string($content);
        }
        if (false == $xml) {
            throw new \Exception('PhonePortInfo xml parse error');
        }
        $fieldsMap = [
            'deviceid' => 'cdpNeighborDeviceId',
            'cdpneighbordeviceid' => 'cdpNeighborDeviceId',
            'ipaddress' => 'cdpNeighborIP',
            'cdpneighborip' => 'cdpNeighborIP',
            'port' => 'cdpNeighborPort',
            'cdpneighborport' => 'cdpNeighborPort',
        ];
        $portInfo = [
            'cdpNeighborDeviceId' => '',
            'cdpNeighborIP' => '',
            'cdpNeighborPort' => '',
        ];
        foreach (get_object_vars($xml) as $field => $value) {
            if (array_key_exists(mb_strtolower($field), $fieldsMap)) {
                $field = $fieldsMap[mb_strtolower($field)];
                $portInfo[$field] = trim((string)$value);
            }
        }
        return $portInfo;
    }

    /**
     * @param string $url
     * @return bool|string
     */
    protected function urlContent(string $url)
    {
        if (false === $url = filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIME_OUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::CONNECT_TIME_OUT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        if (curl_getinfo($ch)['http_code'] != self::HTTP_CODE_OK) {
            return false;
        }
        curl_close($ch);
        return $response;
    }
}
