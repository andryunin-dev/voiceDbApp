<?php
namespace App\Components;

class AxlClient
{
    private static $uniqueInstance = [];

    const DEFAULTSCHEMA = '7.1';

    public $client;
    public $schema;


    protected function __construct(string $cucmIp)
    {
        // Create the environment
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


        // Create the default axlClient
        $axlClientWithDefaultSchema = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . self::DEFAULTSCHEMA . '/AXLAPI.wsdl'), [
            'trace' => true,
            'exception' => true,
            'location' => 'https://' . $cucmIp . ':8443/axl',
            'login' => $username,
            'password' => $password,
            'stream_context' => $context,
        ]);


        // Get the cucm's scheme
        $result = preg_match('~^\d+\.\d+~', $axlClientWithDefaultSchema->GetCCMVersion($cucmIp)->return->componentVersion->version, $axlSchema);

        if (1 == $result) {

            // Если cucm's scheme получена, то создать или вернуть axlClient с cucm's scheme
            $this->schema = $axlSchema[0];

            if (self::DEFAULTSCHEMA == $this->schema) {
                $this->client = $axlClientWithDefaultSchema;
            } else {
                $this->client = new \SoapClient(realpath(ROOT_PATH . '/AXLscheme/' . $axlSchema[0] . '/AXLAPI.wsdl'), [
                    'trace' => true,
                    'exception' => true,
                    'location' => 'https://' . $cucmIp . ':8443/axl',
                    'login' => $username,
                    'password' => $password,
                    'stream_context' => $context,
                ]);
            }

        } else {
            // Если не удалось получить cucm's scheme,то вернуть false
            $this->client = false;
            $this->schema = false;
        }
    }


    public static function getInstance(string $cucmIp)
    {
        if (null === self::$uniqueInstance[$cucmIp]) {
            self::$uniqueInstance[$cucmIp] = new self($cucmIp);
        }

        return self::$uniqueInstance[$cucmIp];
    }
}
