<?php
namespace App\Components;

class AxlClient
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
        // Todo - попробовать получить $schema из CUCM
        $schema = 'sch7_1';

        self::$instance->$cucmIp = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . $schema . '/AXLAPI.wsdl'), [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $ip . ':8443/axl',
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
