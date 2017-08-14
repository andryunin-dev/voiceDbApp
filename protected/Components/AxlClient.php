<?php
namespace App\Components;

use T4\Core\Exception;

class AxlClient
{
    const DEFAULTSCHEMA = '7.1';
    static $instance;
    static $schema;

    protected function __construct(string $cucmIp, string $axlSchema = null)
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


        if (is_null($axlSchema)) {
            $axlSchema = self::DEFAULTSCHEMA;

            if (false === realpath(ROOT_PATH . '/AXLscheme/' . $axlSchema . '/AXLAPI.wsdl')) {
                throw new Exception('AxlClient: Default AXL scheme is not found');
            }

            self::$instance->default = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . $axlSchema . '/AXLAPI.wsdl'), [
                'trace' => true,
                'exception' => true,
                'location' => 'https://' . $ip . ':8443/axl',
                'login' => $username,
                'password' => $password,
                'stream_context' => $context,
            ]);

        } else {
            if (false === realpath(ROOT_PATH . '/AXLscheme/' . $axlSchema . '/AXLAPI.wsdl')) {
                throw new Exception('AxlClient: AXL scheme ' . $axlSchema . ' is not found');
            }

            self::$instance->$cucmIp = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . $axlSchema . '/AXLAPI.wsdl'), [
                'trace' => true,
                'exception' => true,
                'location' => 'https://' . $ip . ':8443/axl',
                'login' => $username,
                'password' => $password,
                'stream_context' => $context,
            ]);

            self::$schema->$cucmIp = $axlSchema;
        }
    }

    public static function instance(string $cucmIp)
    {
        if (!isset(self::$instance->$cucmIp)) {
            if (!isset(self::$instance->default)) {
                new self($cucmIp);
            }

            preg_match('~^\d+\.\d+~', (self::$instance->default)->GetCCMVersion($cucmIp)->return->componentVersion->version, $axlSchema);
            new self($cucmIp, $axlSchema[0]);
        }

        return self::$instance->$cucmIp;
    }
}
