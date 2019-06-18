<?php
namespace App\Components\Connection;

class SshConnection implements Connection
{
    private const SSH_PORT = 22;
    private $resource;
    private $ip;
    private $login;
    private $password;

    public function __construct(string $ip, string $login, string $password)
    {
        $this->ip = $ip;
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * @return bool|resource
     */
    public function connect()
    {
        $this->resource = @ssh2_connect($this->ip, self::SSH_PORT); // @ - silence operator (no warning)
        if (false === $this->resource) {
            return false;
        }
        $authorization = @ssh2_auth_password($this->resource, $this->login, $this->password); // @ - silence operator (no warning)
        return (false !== $authorization) ? $this->resource : false;
    }

    /**
     * @return bool
     */
    public function close(): bool
    {
        return ssh2_disconnect($this->resource);
    }
}
