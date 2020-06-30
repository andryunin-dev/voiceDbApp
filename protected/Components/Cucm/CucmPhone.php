<?php
namespace App\Components\Cucm;

class CucmPhone
{
    private $name = '';
    private $model = '';
    private $status = '';
    private $class = '';
    private $prefix = '';
    private $phonedn = '';
    private $css = '';
    private $devicepool = '';
    private $alertingname = '';
    private $partition = '';
    private $description = '';
    private $serialNumber = '';
    private $modelNumber = '';
    private $versionID = '';
    private $appLoadID = '';
    private $timezone = '';
    private $macAddress = '';
    private $ipAddress = '';
    private $subNetMask = '';
    private $vlanId = '';
    private $dhcpEnabled = '';
    private $dhcpServer = '';
    private $domainName = '';
    private $tftpServer1 = '';
    private $tftpServer2 = '';
    private $defaultRouter = '';
    private $dnsServer1 = '';
    private $dnsServer2 = '';
    private $callManager1 = '';
    private $callManager2 = '';
    private $callManager3 = '';
    private $callManager4 = '';
    private $userLocale = '';
    private $cdpNeighborDeviceId = '';
    private $cdpNeighborIP = '';
    private $cdpNeighborPort = '';
    private $publisherIp = '';

    /**
     * @param array $data
     * @return $this
     */
    public function fill(array $data)
    {
        foreach ($data as $field => $value) {
            if (!is_null($this->$field)) {
                $this->$field = $value;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'model' => $this->model,
            'status' => $this->status,
            'class' => $this->class,
            'prefix' => $this->prefix,
            'phonedn' => $this->phonedn,
            'css' => $this->css,
            'devicepool' => $this->devicepool,
            'alertingname' => $this->alertingname,
            'partition' => $this->partition,
            'description' => $this->description,
            'serialNumber' => $this->serialNumber,
            'modelNumber' => $this->modelNumber,
            'versionID' => $this->versionID,
            'appLoadID' => $this->appLoadID,
            'timezone' => $this->timezone,
            'macAddress' => $this->macAddress,
            'ipAddress' => $this->ipAddress,
            'subNetMask' => $this->subNetMask,
            'vlanId' => $this->vlanId,
            'dhcpEnabled' => $this->dhcpEnabled,
            'dhcpServer' => $this->dhcpServer,
            'domainName' => $this->domainName,
            'tftpServer1' => $this->tftpServer1,
            'tftpServer2' => $this->tftpServer2,
            'defaultRouter' => $this->defaultRouter,
            'dnsServer1' => $this->dnsServer1,
            'dnsServer2' => $this->dnsServer2,
            'callManager1' => $this->callManager1,
            'callManager2' => $this->callManager2,
            'callManager3' => $this->callManager3,
            'callManager4' => $this->callManager4,
            'userLocale' => $this->userLocale,
            'cdpNeighborDeviceId' => $this->cdpNeighborDeviceId,
            'cdpNeighborIP' => $this->cdpNeighborIP,
            'cdpNeighborPort' => $this->cdpNeighborPort,
            'publisherIp' => $this->publisherIp,
        ];
    }

    /**
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
