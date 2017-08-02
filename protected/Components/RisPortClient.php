<?php
namespace App\Components;

class RisPortClient
{
    static $instance;

    protected function __construct(string $cucmIp)
    {
        $ip = (new IpTools($cucmIp))->address;

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

        self::$instance->$cucmIp = new \SoapClient('https://' . $ip . ':8443/realtimeservice/services/RisPort?wsdl', [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $ip . ':8443/realtimeservice/services/RisPort',
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
        ]);
    }

    public static function instance(string $cucmIp)
    {
        if (!isset(self::$instance->$cucmIp)) {
            new self($cucmIp);
        }
        return self::$instance->$cucmIp;
    }
}
