<?php
namespace App\Components\Connection\ConnectionImpl;

class SshConnection
{
    private const SSH_PORT = 22;

    private $connection;
    private $ip;
    private $login;
    private $password;

    public function __construct(string $ip, string $login, string $password)
    {
        $this->ip = $ip;
        $this->login = $login;
        $this->password = $password;
    }

    public function connect()
    {
        $this->connection = ssh2_connect($this->ip, self::SSH_PORT);
        if (false === $this->connection) {
            return false;
        }
        $authorization = ssh2_auth_password($this->connection, $this->login, $this->password);
        return false !== $authorization ? $this->connection : false;
    }

    public function close(): void
    {
        ssh2_disconnect($this->connection);
    }
}
