<?php
namespace App\Components\Log;

interface Log
{
    public const LOG_DIR = ROOT_PATH . DS . 'Logs' . DS;
    public function list(): array;
}
