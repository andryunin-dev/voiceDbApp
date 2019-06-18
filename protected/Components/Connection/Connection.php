<?php
namespace App\Components\Connection;

interface Connection
{
    public function connect();
    public function close(): bool;
}
