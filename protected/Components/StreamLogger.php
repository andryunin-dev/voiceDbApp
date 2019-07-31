<?php
namespace App\Components;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class StreamLogger
{
    private const DIR = ROOT_PATH.'/Logs/';
    private const LOG_FILE = [
        'DEFAULT' => self::DIR.'errors.log',
        'DS-PREFIXES' => self::DIR.'DSprefixes.log',
        'DS-ERRORS' => self::DIR.'DSerrors.log',
        'DS-CLUSTER' => self::DIR.'DScluster.log',
        'DS-APPLIANCE' => self::DIR.'DSappliance.log',
        'DS-DNSNAMES' => self::DIR.'DSdnsnames.log',
        'CDP_NEIGHBORS' => self::DIR.'phones_cdp_neighbors.log',
        'PHONES_UPDATE' => self::DIR.'phones_update.log',
        'CUCM' => self::DIR.'cucms.log',
    ];

    /**
     * @param string $name
     * @param string|null $logfile
     * @return Logger
     * @throws \Exception
     */
    public static function instanceWith(string $name, string $logfile = null): Logger
    {
        if (is_null($logfile)) {
            $logfile = self::LOG_FILE[$name] ?? self::LOG_FILE['DEFAULT'];
        }
        Logger::setTimezone(new \DateTimeZone('Europe/Moscow'));
        return (new Logger($name))->pushHandler(new StreamHandler($logfile, Logger::DEBUG));
    }
}
