<?php

namespace App\Components;

use T4\Core\TStdGetSet;

/**
 * Class Links
 * @package App\Components
 *
 * @property string $addOffice
 * @property string $offices
 */
class Links
{
    use TStdGetSet;

    protected function __construct()
    {
        $this->addOffice = '/modal/addOffice';
        $this->offices = '/admin/offices';
    }

    protected function __clone()
    {
    }

    /**
     * @param bool $new
     * @return static
     */
    public static function instance($new = false)
    {
        static $instance = null;
        if (null === $instance || $new)
            $instance = new static;
        return $instance;
    }
}