<?php
namespace App\Components;

class DSPerror
{
    protected $data;
    private $logger;

    /**
     * DSPerror constructor.
     * @param $data
     * @throws \Exception
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->logger = StreamLogger::instanceWith('DS-ERRORS');
    }

    public function log()
    {
        try {
            if (!$this->validateDataStructure()) {
                throw new \Exception("Not valid input data structure");
            }
            $this->logger->error('[host]='.$this->data->hostname.' [ip]='.$this->data->ip.' [message]='.$this->data->message);
        } catch (\Throwable $e) {
            $this->logger->error('[ip]='.$this->data->ip.'; [message]='.$e->getMessage().' [dataset]='.json_encode($this->data));
            throw new \Exception("Error: [ip]=".$this->data->ip);
        }
    }

    /**
     * Validate data structure
     * {
     *   "dataSetType",
     *   "ip",
     *   "hostname",
     *   "message"
     * }
     * @return boolean
     */
    private function validateDataStructure(): bool
    {
        return (isset($this->data->hostname) || isset($this->data->ip) || isset($this->data->message));
    }
}
