<?php
namespace App\Components\Cucm;

use App\Components\Phones\Cisco\CiscoDeviceFactory;
use App\Components\StreamLogger;
use App\Models\Appliance;
use App\Models\ApplianceType;
use function T4\app;

class Cucm
{
    private $appliance;
    private $schema;
    private $axlService;
    private $risPortService;
    private $logger;

    /**
     * Cucm constructor.
     * @param Appliance $appliance
     * @throws \Exception
     */
    public function __construct(Appliance $appliance)
    {
        if (ApplianceType::CUCM_PUBLISHER !== $appliance->type->type) {
            throw new \Exception('Appliance (id=' . $appliance->getPk() . ') is not CUCM');
        }
        $this->appliance = $appliance;
        $this->axlService = new CucmAxlService($this);
        $this->risPortService = new CucmRisPortService($this);
        $this->logger = StreamLogger::instanceWith(
            'CUCM',
            ROOT_PATH.DS.'Logs'.DS.'cucm_'.$this->ip().'.log'
        );
    }

    /**
     * Phone registered on the CUCM
     * @param string $name
     * @return CucmPhone|false
     * @throws \SoapFault
     */
    public function registeredPhone(string $name)
    {
        $dataOfAxlService = $this->axlService->phone($name);
        if (false === $dataOfAxlService) {
            return false;
        }
        $dataOfRisPortService = $this->risPortService->registeredPhone($name);
        if (false === $dataOfRisPortService) {
            return false;
        }
        return $this->cucmPhone(
            $dataOfAxlService,
            $dataOfRisPortService,
            $this->phoneRealtimeData(
                $name,
                $dataOfAxlService->model,
                $dataOfRisPortService->IpAddress
            )
        );
    }

    /**
     * Phones registered on the CUCM
     * @return array of CucmPhone|[]
     * @throws \SoapFault
     */
    public function registeredPhones()
    {
        $registeredPhones = [];
        $dataOfAxlService = $this->axlService->phones();
        if (empty($dataOfAxlService)) {
            return $registeredPhones;
        }
        $dataOfRisPortService = $this->risPortService->registeredPhones(array_keys($dataOfAxlService));
        if (empty($dataOfRisPortService)) {
            return $registeredPhones;
        }
        array_walk(
            $dataOfRisPortService,
            function ($phoneDataOfRisService) use ($dataOfAxlService, &$registeredPhones) {
                $phoneDataOfAxlService = $dataOfAxlService[mb_strtoupper($phoneDataOfRisService->Name)];
                if (is_null($phoneDataOfAxlService)) {
                    $this->logger->error(
                        '[message]=Axl Service does not have data about the phone ' . $phoneDataOfRisService->Name . ', but Risport Service has.' .
                        ' [sep]=' . $phoneDataOfRisService->Name .
                        ' [cucm]=' . $this->ip()
                    );
                    return;
                }
                $registeredPhones[] = $this->cucmPhone(
                    $phoneDataOfAxlService,
                    $phoneDataOfRisService,
                    $this->phoneRealtimeData(
                        $phoneDataOfRisService->Name,
                        $phoneDataOfAxlService->model,
                        $phoneDataOfRisService->IpAddress
                    )
                );
            }
        );
        return $registeredPhones;
    }

    /**
     * Redirected Phones
     * @return array of RedirectedPhone|[]
     * @throws \SoapFault|\T4\Core\MultiException
     */
    public function redirectedPhones(): array
    {
        return $this->axlService->redirectedPhones();
    }

    /**
     * Redirected Phones with callForwardingNumber
     * @param string $callForwardingNumber
     * @return array of RedirectedPhone|[]
     * @throws \SoapFault|\T4\Core\MultiException
     */
    public function redirectedPhonesWithCallForwardingNumber(string $callForwardingNumber): array
    {
        return $this->axlService->redirectedPhonesWithCallForwardingNumber($callForwardingNumber);
    }

    /**
     * Redirected Phones containing callForwardingNumber as substring
     * @param string $callForwardingNumber
     * @return array of RedirectedPhone|[]
     * @throws \SoapFault|\T4\Core\MultiException
     */
    public function redirectedPhonesContainingCallForwardingNumberAsSubstring(string $callForwardingNumber): array
    {
        return $this->axlService->redirectedPhonesContainingCallForwardingNumberAsSubstring($callForwardingNumber);
    }

    /**
     * Version of cucm's API
     * @return mixed
     * @throws \Exception
     */
    public function schema()
    {
        if (is_null($this->schema)) {
            $object_id = '.1.3.6.1.4.1.9.9.156.1.5.29.0';
            $snmpObjectValue = snmpget($this->ip(), $this->snmpCommunity(), $object_id);
            if (false === $snmpObjectValue) {
                throw new \Exception(
                    '[message]=Ris port schema not received. SNMP runtime error'
                    . '[ip]=' . $this->ip()
                );
            }
            mb_ereg("\d+.\d", $snmpObjectValue, $regs);
            $this->schema = reset($regs);
        }
        return $this->schema;
    }

    /**
     * @return string
     */
    public function ip(): string
    {
        return $this->appliance->managementIp;
    }

    /**
     * @return string
     */
    public function login(): string
    {
        return app()->config->axl->username;
    }

    /**
     * @return string
     */
    public function password(): string
    {
        return app()->config->axl->password;
    }

    /**
     * @return string
     */
    private function snmpCommunity(): string
    {
        return app()->config->snmp->community;
    }

    /**
     * @param \stdClass $dataOfAxlService
     * @param \stdClass $dataOfRisPortService
     * @param array $dataOfPhone
     * @return CucmPhone
     */
    private function cucmPhone(\stdClass $dataOfAxlService, \stdClass $dataOfRisPortService, array $dataOfPhone): CucmPhone
    {
        return (new CucmPhone())->fill([
            'name' => $dataOfAxlService->name,
            'model' => $dataOfAxlService->model,
            'status' => $dataOfRisPortService->Status,
            'class' => $dataOfRisPortService->Class,
            'prefix' => $dataOfAxlService->prefix,
            'phonedn' => $dataOfAxlService->phonedn,
            'e164mask' => $dataOfAxlService->e164mask,
            'css' => $dataOfAxlService->css,
            'devicepool' => $dataOfAxlService->devicepool,
            'alertingname' => $dataOfAxlService->alertingname,
            'partition' => $dataOfAxlService->partition,
            'description' => $dataOfAxlService->description,
            'serialNumber' => $dataOfPhone['serialNumber'] ?? '',
            'modelNumber' => $dataOfPhone['modelNumber'] ?? '',
            'versionID' => $dataOfPhone['versionID'] ?? '',
            'appLoadID' => $dataOfPhone['appLoadID'] ?? '',
            'timezone' => $dataOfPhone['timezone'] ?? '',
            'macAddress' => $dataOfPhone['macAddress'] ?? '',
            'ipAddress' => $dataOfRisPortService->IpAddress,
            'subNetMask' => $dataOfPhone['subNetMask'] ?? '',
            'vlanId' => $dataOfPhone['vlanId'] ?? '',
            'dhcpEnabled' => $dataOfPhone['dhcpEnabled'] ?? '',
            'dhcpServer' => $dataOfPhone['dhcpServer'] ?? '',
            'domainName' => $dataOfPhone['domainName'] ?? '',
            'tftpServer1' => $dataOfPhone['tftpServer1'] ?? '',
            'tftpServer2' => $dataOfPhone['tftpServer2'] ?? '',
            'defaultRouter' => $dataOfPhone['defaultRouter'] ?? '',
            'dnsServer1' => $dataOfPhone['dnsServer1'] ?? '',
            'dnsServer2' => $dataOfPhone['dnsServer2'] ?? '',
            'callManager1' => $dataOfPhone['callManager1'] ?? '',
            'callManager2' => $dataOfPhone['callManager2'] ?? '',
            'callManager3' => $dataOfPhone['callManager3'] ?? '',
            'callManager4' => $dataOfPhone['callManager4'] ?? '',
            'userLocale' => $dataOfPhone['userLocale'] ?? '',
            'cdpNeighborDeviceId' => $dataOfPhone['cdpNeighborDeviceId'] ?? '',
            'cdpNeighborIP' => $dataOfPhone['cdpNeighborIP'] ?? '',
            'cdpNeighborPort' => $dataOfPhone['cdpNeighborPort'] ?? '',
            'publisherIp' => $this->ip(),
        ]);
    }

    /**
     * Data received from the phone
     * @param string $name
     * @param string $model
     * @param string $ip
     * @return array
     */
    private function phoneRealtimeData(string $name,string $model, string $ip): array
    {
        $dataOfPhone = [];
        try {
            $ciscoPhone = CiscoDeviceFactory::model($model, $ip);
            if (false !== $ciscoPhone) {
                $dataOfPhone = $ciscoPhone->realtimeData();
            }
        } catch (\Throwable $e) {
            $this->logger->error(
                '[message]=' . $e->getMessage() .
                ' [sep]=' . $name .
                ' [model]=' . $model .
                ' [phoneIp]=' . $ip .
                ' [cucmIp]=' . $this->ip()
            );
        }
        return $dataOfPhone;
    }
}
