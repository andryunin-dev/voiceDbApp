<?php
namespace App\Components\Ssh;

class SshClient
{
    private const PORT = 22;
    private $ip;
    private $login;
    private $passphrase;
    private $port;
    private $resource;

    /**
     * SshClient constructor.
     * @param string $ip
     * @param string $login
     * @param string $passphrase
     * @param int $port
     */
    public function __construct(string $ip, string $login, string $passphrase, int $port = self::PORT)
    {
        $this->ip = $ip;
        $this->login = $login;
        $this->passphrase = $passphrase;
        $this->port = $port;
    }

    /**
     * @param string $command
     * @return string
     * @throws \Exception
     */
    public function exec(string $command): string
    {
        $this->connect();
        $this->authorization();
        $result = $this->run($command);
        $this->disconnect();
        return $result;
    }

    /**
     * @param string $command
     * @return string
     * @throws \Exception
     */
    private function run(string $command): string
    {
        $outputStream = @ssh2_exec($this->resource, $command);
        $errorStream = @ssh2_fetch_stream($outputStream, SSH2_STREAM_STDERR);
        stream_set_blocking($errorStream, true);
        stream_set_blocking($outputStream, true);
        $result = @stream_get_contents($outputStream, -1);
        $errors = @stream_get_contents($errorStream, -1);
        fclose($errorStream);
        fclose($outputStream);
        if (false === $result) {
            throw new \Exception('ssh://' . $this->ip . ' ssh2_exec errors [errors]=' . $errors);
        }
        return $result;
    }

    /**
     * @throws \Exception
     */
    private function connect(): void
    {
        $this->resource = @ssh2_connect(
            $this->ip,
            $this->port
        );
        if (false === $this->resource) {
            throw new \Exception('ssh://' . $this->ip . ' Connection Failed...');
        }
    }

    /**
     * @throws \Exception
     */
    private function authorization(): void
    {
        $authorization = @ssh2_auth_password(
            $this->resource,
            $this->login,
            $this->passphrase
        );
        if (false === $authorization) {
            throw new \Exception('ssh://' . $this->ip . ' Authentication Failed...');
        }
    }

    /**
     * @return bool
     */
    private function disconnect(): bool
    {
        return ssh2_disconnect($this->resource);
    }
}
