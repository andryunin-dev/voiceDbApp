<?php

namespace App\Components\Connection\ConnectionImpl;

use App\Components\Connection\Connection;

class SshConnectionHandler implements Connection
{

    private const SSH_PORT = 22;

    private $login;
    private $password;


    public function __construct(string $login, string $password)
    {
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * Establish a connection
     *
     * @param $ip
     * @return bool|resource
     */
    public function getConnect($ip)
    {
        $connection = ssh2_connect($ip, self::SSH_PORT);
        $authorization = ssh2_auth_password($connection, $this->login, $this->password);
        if (false === $connection || !$authorization) {
            return false;
        }
        return $connection;
    }

    /**
     * @return string
     */
    public function getLogin(): string
    {
        return $this->login;
    }

    /**
     * @param string $login
     */
    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }
}
