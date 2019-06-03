<?php
namespace App\Components\Log;

class LevelLog implements Log
{
    private $level;
    private $log;

    public function __construct(string $level, Log $log)
    {
        $this->level = mb_strtolower($level);
        $this->log = $log;
    }

    public function list(): array
    {
        $log = [];
        foreach ($this->log->list() as $item) {
            if (mb_ereg_match('.+'.$this->level, mb_strtolower($item))) {
                $log[] = $item;
            }
        }
        return $log;
    }
}
