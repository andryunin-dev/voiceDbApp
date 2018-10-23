<?php

namespace App\Components\Connection\ConnectionImpl;

use App\Components\Connection\Connection;
use T4\Orm\Exception;

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
     * @return resource
     * @throws Exception
     */
    public function getConnect($ip)
    {
        $connection = ssh2_connect($ip, self::SSH_PORT);
        if (false === $connection) {
            throw new Exception('UNABLE TO CONNECT to '.$ip.' on port '.self::SSH_PORT);
        }

        $authorization = ssh2_auth_password($connection, $this->login, $this->password);
        if (false === $authorization) {
            throw new Exception('AUTHENTICATION FAILED on connect to '.$ip);
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
