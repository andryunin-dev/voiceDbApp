<?php
namespace App\Components\Log;

interface EraseableLog extends Log
{
    public function erase(): void;
}
