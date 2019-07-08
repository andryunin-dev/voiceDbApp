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
            WHERE masklen NOTNULL AND ((((date_part(\'epoch\' :: TEXT, age(now(), "dnsLastUpdate")) / (3600) :: DOUBLE PRECISION)) :: INTEGER) > :update_period  OR "dnsLastUpdate" ISNULL)
            LIMIT :limit',
    ];
    private $logger;

    public function actionDefault(): void
    {
        $this->update($this->hostsBy($this->ipAddresses()));
        $this->writeLn('Hosts has updated');
    }

    /**
     * @return array
     */
    private function ipAddresses(): array
    {
        return array_map(
            function ($dataPort) {
                return $dataPort['ipAddress'];
            },
            DataPort::getDbConnection()
                ->query(self::SQL['host_ip'], ['limit' => self::LIMIT, 'update_period' => self::UPDATE_PERIOD])
                ->fetchAll(\PDO::FETCH_ASSOC)
        );
    }

    /**
     * @param array $ipAddresses
     * @return array
     */
    private function hostsBy(array $ipAddresses): array
    {
        return array_map(
            function ($ipAddress): array  {
                return [
                    'ipAddress' => $ipAddress,
                    'dnsName' => (in_array($dnsName = gethostbyaddr($ipAddress), [$ipAddress, false])) ? '' : $dnsName,
                ];
            },
            $ipAddresses
        );
    }

    /**
     * @param array $hosts
     */
    private function update(array $hosts): void
    {
        $logger = $this->logger;
        array_walk(
            $hosts,
            function ($host) use ($logger): void {
                if (false !== $dataPort = DataPort::findByColumn('ipAddress', $host['ipAddress'])) {
                    $dataPort
                        ->fill([
                            'dnsName' => $host['dnsName'],
                            'dnsLastUpdate' => (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s P'),
                            'vrf' => $dataPort->vrf
                        ])
                        ->save();
                    if (count($dataPort->errors) > 0) {
                        $logger->error('[message]=' . $dataPort->errors[0] . ' [ip]=' . $dataPort->ipAddress);
                    }
                }
            }
        );
    }

    /**
     * UpdateHostsByAddresses constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->logger = StreamLogger::instanceWith('DS-DNSNAMES');
    }
}
