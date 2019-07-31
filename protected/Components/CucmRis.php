<?php
namespace App\Components;

class CucmRis
{
    private const ANY_CLASS = 'Any';
    private const ANY_MODEL = 255;
    private const NAME = 'Name';
    private const REGISTERED_STATUS = 'Registered';
    private const SLEEP_SECONDS = 1;
    private const MAX_ATTEMPTS = 150;
    private $risClient;
    private $ip;

    /**
     * CucmRis constructor.
     * @param string $ip
     * @param CucmRisClient $risClient
     */
    public function __construct(string $ip, CucmRisClient $risClient)
    {
        $this->risClient = $risClient;
        $this->ip = (new IpTools($ip))->address;
    }

    /**
     * @param array $names
     * @return array
     * @throws \SoapFault
     */
    public function registeredPhonesWithNames(array $names): array
    {
        return $this->devicesWith(self::NAME, $names, self::REGISTERED_STATUS);
    }

    /**
     * @param string $name
     * @return bool|mixed
     * @throws \SoapFault
     */
    public function registeredPhoneWithName(string $name)
    {
        $result = $this->devicesWith(self::NAME, array($name), self::REGISTERED_STATUS);
        return !empty($result) ? array_shift($result) : false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function schema(): string
    {
        return $this->risClient->schema();
    }

    /**
     * @param string $selectAttribute
     * @param array $items
     * @param string $status
     * @return array
     * @throws \SoapFault
     */
    private function devicesWith(string $selectAttribute, array $items, string $status): array
    {
        $devices = [];
        foreach ($this->itemsGroups($items) as $selectItems) {
            $result = $this->selectCmDevice($selectAttribute, $selectItems, $status);
            if (!empty($result) && !is_null(($result['SelectCmDeviceResult'])->CmNodes)) {
                foreach (($result['SelectCmDeviceResult'])->CmNodes as $node) {
                    if ('ok' == mb_strtolower($node->ReturnCode)) {
                        foreach ($node->CmDevices as $device) {
                            $devices[mb_strtoupper($device->Name)] = [
                                'name' => $device->Name,
                                'ipAddress' => $device->IpAddress,
                                'status' => $device->Status,
                                'class' => $device->Class,
                            ];
                        }
                    }
                }
            }
        }
        return $devices;
    }

    /**
     * @param string $selectAttribute
     * @param array $items
     * @param string $status
     * @return array
     * @throws \SoapFault
     */
    private function selectCmDevice(string $selectAttribute, array $items, string $status): array
    {
        $result = [];
        $numberOfAttempts = 0;
        do {
            try {
                $exceededAllowedNumbersOfRequestPerMinute = false;
                $result = $this->connection()->SelectCmDevice('', [
                    'MaxReturnedDevices' => $this->maxDevicesPerQuery(),
                    'Class' => self::ANY_CLASS,
                    'Model' => self::ANY_MODEL,
                    'Status' => $status,
                    'SelectBy' => $selectAttribute,
                    'SelectItems' => $items,
                ]);
            } catch (\SoapFault $e) {
                if (mb_ereg_match('.*exceededallowedrateforreatimeinformation', mb_strtolower(mb_ereg_replace(' ', '', $e->getMessage())))) {
                    if (++$numberOfAttempts > self::MAX_ATTEMPTS) {
                        throw new \Exception('RisPort service is not available. Exceeded max allowed numbers of attempts');
                    }
                    $exceededAllowedNumbersOfRequestPerMinute = true;
                    sleep(self::SLEEP_SECONDS);
                } else {
                    throw $e;
                }
            }
        } while ($exceededAllowedNumbersOfRequestPerMinute);
        return $result;
    }

    /**
     * @return int
     * @throws \Exception
     */
    private function maxDevicesPerQuery(): int
    {
        return ((float)$this->schema() <= 9.0) ? 200 : 1000;
    }

    /**
     * @param array $items
     * @return array
     * @throws \Exception
     */
    private function itemsGroups(array $items): array
    {
        $itemsGroups = [];
        if (empty($items)) {
            return $itemsGroups;
        }
        if (count($items) > $this->maxDevicesPerQuery()) {
            for ($offset = 0; $offset < count($items); $offset += $this->maxDevicesPerQuery()) {
                $itemsGroups[] = array_map(
                    function ($item) {
                        return array('Item' => $item);
                    },
                    array_slice($items, $offset, $this->maxDevicesPerQuery())
                );
            }
        } else {
            $itemsGroups[] = array_map(
                function ($item) {
                    return array('Item' => $item);
                },
                $items
            );
        }
        return $itemsGroups;
    }

    /**
     * @return \SoapClient
     * @throws \Exception
     */
    private function connection(): \SoapClient
    {
        return $this->risClient->client();
    }
}
