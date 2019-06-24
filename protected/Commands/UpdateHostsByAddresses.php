<?php
namespace App\Commands;

use App\Components\StreamLogger;
use App\Models\DataPort;
use T4\Console\Command;

class UpdateHostsByAddresses extends Command
{
    private const LIMIT = 250; // limit on the number of requested ipaddresses
    private const UPDATE_PERIOD = 169; // week in hours
    private const SQL = [
        'host_ip' => '
            SELECT "ipAddress"
            FROM equipment."dataPorts"
            WHERE (((date_part(\'epoch\' :: TEXT, age(now(), "dnsLastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER) > :update_period  OR "dnsLastUpdate" ISNULL
            LIMIT :limit',
    ];
    private $logger;

    public function actionDefault(): void
    {
        $this->actionUpdateDnsNames($this->actionDnsNames($this->ipAddresses()));
        $this->writeLn('Hosts has updated');
    }

    /**
     * @return array
     */
    private function ipAddresses(): array
    {
        $params = [
            'limit' => self::LIMIT,
            'update_period' => self::UPDATE_PERIOD,
        ];
        $dataPorts = DataPort::getDbConnection()->query(self::SQL['host_ip'], $params)->fetchAll(\PDO::FETCH_ASSOC);
        $ipAddresses = [];
        foreach ($dataPorts as $dataPort) {
            $ipAddresses[] = $dataPort['ipAddress'];
        }
        return $ipAddresses;
    }

    /**
     * @param array $ipAddresses
     * @return array
     */
    private function actionDnsNames(array $ipAddresses): array
    {
        $dnsNames = [];
        foreach ($ipAddresses as $ipAddress) {
            try {
                $dnsName = gethostbyaddr($ipAddress);
            } catch (\Throwable $e) {
                $this->logger->error('[message]=' . $e->getMessage());
                continue;
            }
            if ($dnsName == $ipAddress || false === $dnsName) {
                $dnsName = '';
            }
            $dnsNames[$ipAddress] = $dnsName;
        }
        return $dnsNames;
    }

    /**
     * @param array $dnsNames
     */
    private function actionUpdateDnsNames(array $dnsNames): void
    {
        foreach ($dnsNames as $ipAddress => $dnsName) {
            try {
                $dataPort = DataPort::findByColumn('ipAddress', $ipAddress);
                if (false === $dataPort) {
                    throw new \Exception('DataPort is not exists [ip]=' . $ipAddress);
                }
                $dataPort->fill([
                    'dnsName' => $dnsName,
                    'dnsLastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                    'vrf' => $dataPort->vrf,
                ])->save();
                if (count($dataPort->errors) > 0) {
                    $this->logger->error('[message]=' . $dataPort->errors[0]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('[message]=' . $e->getMessage());
            }
        }
    }

    /**
     * UpdateHostsByAddresses constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = StreamLogger::getInstance('DS-DNSNAMES');
    }
}
