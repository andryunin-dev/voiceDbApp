<?php
namespace App\Components\Inventory;

use App\Components\StreamLogger;
use Monolog\Logger;

class ErrorLoggingService
{
    /**
     * @param array $data
     * @throws \Exception
     */
    public function log(array $data): void
    {
        $this->logger()->error('[message]=' . $data['message'] . ' [host]=' . $data['hostname'] . ' [ip]=' . $data['ip']);
    }

    /**
     * @return \Monolog\Logger
     * @throws \Exception
     */
    private function logger(): Logger
    {
        return StreamLogger::instanceWith('DS-ERRORS');
    }
}
