<?php
namespace App\Components;

class RisPortClient
{
    private static $uniqueInstance = [];

    protected $client;

    protected function __construct(string $cucmIp)
    {
        if ('cli' == PHP_SAPI) {
            $app = \T4\Console\Application::instance();
        } else {
            $app = \T4\Mvc\Application::instance();
        }

        $username = $app->config->axl->username;
        $password = $app->config->axl->password;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'ciphers' => 'HIGH',
            ]
        ]);

        $this->client = new \SoapClient('https://' . $cucmIp . ':8443/realtimeservice/services/RisPort?wsdl', [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $cucmIp . ':8443/realtimeservice/services/RisPort',
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
        ]);
    }


    public static function getInstance(string $cucmIp)
    {
        if (null === self::$uniqueInstance[$cucmIp]) {
            self::$uniqueInstance[$cucmIp] = new self($cucmIp);
        }

        return self::$uniqueInstance[$cucmIp]->client;
    }
}
