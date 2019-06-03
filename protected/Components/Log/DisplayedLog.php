<?php
namespace App\Components\Log;

class DisplayedLog implements Log
{
    private $fields;
    private $log;

    public function __construct(array $fields, Log $log)
    {
        $this->log = $log;
        $this->fields = $fields;
    }

    /**
     * @return array
     */
    public function list(): array
    {
        $log = [];
        foreach (array_reverse($this->log->list()) as $k => $item) {
            foreach ($this->fields as $field) {
                $result = [];
                if ('header' == $field && false !== mb_ereg('\[.+?\[', $item, $result)) {
                    $temp = trim(mb_ereg_replace(':\D+$', '', $result[0]));
                    $log[$k][$field] = mb_strtolower(mb_ereg_replace('\]', ']<br>', $temp));
                    continue;
                }
                if (false !== mb_ereg('\['.$field.'\]=.+?\[', $item, $result)) {
                    $log[$k][$field] = trim(mb_ereg_replace('^\[.+?\]=|;|\[$', '', $result[0]));
                }
            }
        }
        return $log;
    }
}
