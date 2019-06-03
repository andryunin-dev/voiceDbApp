<?php
namespace App\Components\Log;

class ApplianceLog implements EraseableLog
{
    private const FILE_PATTERN = 'DS';
    private $logFiles = [];

    public function __construct()
    {
        foreach (scandir(self::LOG_DIR) as $item) {
            if (mb_ereg_match('^'.self::FILE_PATTERN, $item) && is_file($logFile = self::LOG_DIR.$item)) {
                $this->logFiles[] = $logFile;
            }
        }
    }

    public function list(): array
    {
        $log = [];
        foreach ($this->logFiles as $logFile) {
            foreach (file($logFile, FILE_IGNORE_NEW_LINES) as $logRecord) {
                if (!empty($logRecord)) {
                    $log[] = $logRecord;
                }
            }
        }
        return $log;
    }

    public function erase(): void
    {
        foreach ($this->logFiles as $file) {
            $fh = fopen($file, 'w');
            ftruncate($fh,0);
            fclose($fh);
        }
    }
}
