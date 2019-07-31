<?php
namespace App\Components;

class CucmRisClient
{
    private $client;
    private $schema;
    private $ip;
    private $login;
    private $password;

    /**
     * CucmRisClient constructor.
     * @param string $ip
     * @param string $login
     * @param string $password
     */
    public function __construct(string $ip, string $login, string $password)
    {
        $this->ip = (new IpTools($ip))->address;
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function schema(): string
    {
        if (is_null($this->schema)) {
            if (false === $response = snmpget($this->ip, 'RegionRS2005', '.1.3.6.1.4.1.9.9.156.1.5.29.0')) {
                throw new \Exception('Wrong cucm schema');
            }
            $schema = [];
            mb_ereg('\d{1,2}\.\d', $response, $schema);
            $this->schema = $schema[0];
        }
        return $this->schema;
    }

    /**
     * @return \SoapClient
     * @throws \Exception
     */
    public function client(): \SoapClient
    {
        if (is_null($this->client)) {
            try {
                $this->client = new \SoapClient('https://' . $this->ip . ':8443/realtimeservice/services/RisPort?wsdl', [
                    'trace' => true,
                    'exception' => true,
                    'location' => 'https://' . $this->ip . ':8443/realtimeservice/services/RisPort',
                    'login' => $this->login,
                    'password' => $this->password,
                    'stream_context' => $this->context(),
                ]);
            } catch (\Throwable $e) {
                throw new \Exception('RIS connection error');
            }
        }
        return $this->client;
    }

    /**
     * @return resource
     */
    private function context()
    {
        return stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'ciphers' => 'AES256-SHA',
            ]
        ]);
    }
}
