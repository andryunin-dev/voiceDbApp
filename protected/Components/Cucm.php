<?php
namespace App\Components;

use App\Components\Phones\Cisco\CiscoDeviceFactory;

class Cucm
{
    private $ip;
    private $phones;
    private $cucmAxl;
    private $cucmRis;
    private $logger;

    /**
     * Cucm constructor.
     * @param string $ip
     * @throws \Exception
     */
    public function __construct(string $ip)
    {
        $this->ip = (new IpTools($ip))->address;
        $this->phones = [];
        $axlConf = ('cli' == PHP_SAPI) ? (\T4\Console\Application::instance())->config->axl : (\T4\Mvc\Application::instance())->config->axl;
        $this->cucmAxl = new CucmAxl($this->ip, new CucmAxlClient($this->ip, $axlConf->username, $axlConf->password));
        $this->cucmRis = new CucmRis($this->ip, new CucmRisClient($this->ip, $axlConf->username, $axlConf->password));
        $this->logger = StreamLogger::instanceWith('CUCM', ROOT_PATH.DS.'Logs'.DS.'cucm_'.$this->ip.'.log');
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function phones(): array
    {
        try {
            $risPhonesData = $this->cucmRis->registeredPhonesWithNames($this->cucmAxl->phonesNames()); //todo - uncomment
            $axlPhonesData = $this->cucmAxl->phones();
        } catch (\Throwable $e) {
            $this->logger->error('[message]=' . $e->getMessage() . ' [publisher]=' . $this->ip);
            throw new \Exception('Runtime error');
        }
        foreach ($risPhonesData as $phoneName => $risData) {
            try {
                $axlData = $axlPhonesData[$phoneName];
                if (is_null($axlData)) {
                    throw new \Exception('RisPort has the phone info but Axl does not [name]=' . $phoneName);
                }
                $this->phones[] = $this->cucmPhoneWith($risData, $axlData);
            } catch (\Throwable $e) {
                $this->logger->error('[message]=' . $e->getMessage() . ' [name]=' . $axlData['name'] . ' [publisher]=' . $this->ip);
            }
        }
        return $this->phones;
    }

    /**
     * @param string $name
     * @return CucmDevice|bool
     * @throws \Exception
     */
    public function phoneWithName(string $name)
    {
        if (empty($name)) {
            return false;
        }
        try {
            $risData = $this->cucmRis->registeredPhoneWithName($name);
            if (false === $risData) {
                return false;
            }
            $axlData = $this->cucmAxl->phoneWithName($name);
            if (false === $axlData) {
                throw new \Exception('RisPort has the phone info but Axl does not');
            }
            return $this->cucmPhoneWith($risData, $axlData);
        } catch (\Throwable $e) {
            $this->logger->error('[message]=' . $e->getMessage() . ' [name]=' . $name . ' [publisher]=' . $this->ip);
            throw new \Exception('Runtime error');
        }
    }

    /**
     * @param array $risData
     * @param array $axlData
     * @return CucmDevice|bool
     * @throws \Exception
     */
    private function cucmPhoneWith(array $risData, array $axlData)
    {
        $realtimeData = [];
        try {
            if (false !== $ciscoDevice = CiscoDeviceFactory::model($axlData['model'], $risData['ipAddress'])) {
                $realtimeData = $ciscoDevice->realtimeData();
                if (empty($realtimeData['name'])) {
                    $realtimeData['name'] = $axlData['name'];
                }
            } else {
                throw new \Exception('Phone model does not known');
            }
        } catch (\Throwable $e) {
            $this->logger->error('[message]=' . $e->getMessage() . ' [name]=' . $axlData['name'] . ' [publisher]=' . $this->ip);
        }
        $publisherIp = ['publisherIp' => $this->ip];
        return (new CucmDevice())->fill(array_merge($risData, $axlData, $realtimeData, $publisherIp));
    }
}
