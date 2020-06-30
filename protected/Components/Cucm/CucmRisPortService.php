<?php
namespace App\Components\Cucm;

class CucmRisPortService
{
    private const TIMEOUT_AFTER_EXCEEDED_ALLOWED_RATE = 10; // sec
    private const MAX_NUMBER_OF_ATTEMPTS_TO_MAKE_REQUEST = 18;
    private $cucm;
    private $client;

    /**
     * CucmRisPortService constructor.
     * @param Cucm $cucm
     */
    public function __construct(Cucm $cucm)
    {
        $this->cucm = $cucm;
    }

    /**
     * Registered Phone with $name
     * @param string $name
     * @return \stdClass|false
     * @throws \SoapFault
     */
    public function registeredPhone(string $name)
    {
        $registeredPhones = $this->registeredPhones([$name]);
        return empty($registeredPhones) ? false : reset($registeredPhones);
    }

    /**
     * Registered Phones with $names
     * @param array $names
     * @return array of only registered \stdClass|[]
     * @throws \SoapFault
     */
    public function registeredPhones(array $names): array
    {
        $registeredPhones = [];
        if (count($names) == 0) {
            return $registeredPhones;
        }
        foreach (array_chunk($names, $this->maxDevicesPerQuery()) as $namesPerQuery) {
            $response = $this->selectCmDevice(
                CucmRisPortParameters::DEVICE_CLASS_PHONE,
                CucmRisPortParameters::MODEL_ANY,
                CucmRisPortParameters::STATUS_REGISTERED,
                'Name',
                array_map(
                    function ($name) {
                        return ['Item' => $name];
                    },
                    $namesPerQuery
                )
            );
            if (empty($response)) {
                continue;
            }
            $response = $response['SelectCmDeviceResult'];
            if ($response->TotalDevicesFound > 0) {
                array_walk(
                    $response->CmNodes,
                    function ($cmNode) use (&$registeredPhones) {
                        if (false !== mb_eregi('Ok', $cmNode->ReturnCode)) {
                            $registeredPhones = array_merge(
                                $registeredPhones,
                                $cmNode->CmDevices
                            );
                        }
                    }
                );
            }
        }
        return $registeredPhones;
    }

    /**
     * Original Risport Service command
     * @param string $deviceClass
     * @param int $model
     * @param string $status
     * @param string $selectBy
     * @param array $selectItems
     * @param string $stateInfo
     * @return array - response
     * @throws \SoapFault
     */
    public function selectCmDevice(string $deviceClass, int $model, string $status, string $selectBy, array $selectItems, string $stateInfo = ''): array
    {
        $selectCmDevice = [];
        $numberOfAttemptsToMakeRequest = self::MAX_NUMBER_OF_ATTEMPTS_TO_MAKE_REQUEST;
        do {
            try {
                $isExceededAllowedRateAndThereAreAttempts = false;
                $selectCmDevice = $this->client()->SelectCmDevice($stateInfo, [
                    'MaxReturnedDevices' => $this->maxDevicesPerQuery(),
                    'Class' => $deviceClass,
                    'Model' => $model,
                    'Status' => $status,
                    'SelectBy' => $selectBy,
                    'SelectItems' => $selectItems,
                ]);
            } catch (\SoapFault $e) {
                if (!mb_ereg_match(".*Exceeded allowed rate.*", $e->getMessage())) {
                    throw $e;
                }
                sleep(self::TIMEOUT_AFTER_EXCEEDED_ALLOWED_RATE);
                $numberOfAttemptsToMakeRequest--;
                $isExceededAllowedRateAndThereAreAttempts = $numberOfAttemptsToMakeRequest > 0;
            }
        } while ($isExceededAllowedRateAndThereAreAttempts);
        return $selectCmDevice;
    }

    /**
     * @return \SoapClient
     * @throws \SoapFault
     */
    private function client(): \SoapClient
    {
        if (is_null($this->client)) {
            $wsdl = 'https://' . $this->cucm->ip() . ':8443/realtimeservice/services/RisPort?wsdl';
            $this->client = new \SoapClient(
                $wsdl,
                [
                    'trace' => true,
                    'exception' => true,
                    'login' => $this->cucm->login(),
                    'password' => $this->cucm->password(),
                    'keep_alive' => true,
                    'stream_context' => $this->streamContext(),
                ]
            );
        }
        return $this->client;
    }

    /**
     * @return resource
     */
    private function streamContext()
    {
        return stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'ciphers' => 'AES256-SHA',
            ]
        ]);
    }

    /**
     * @return int
     * @throws \Exception
     */
    private function maxDevicesPerQuery(): int
    {
        return ((float)$this->cucm->schema() < 9.0) ? 200 : 1000;
    }
}
