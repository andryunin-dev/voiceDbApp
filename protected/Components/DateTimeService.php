<?php
namespace App\Components;

class DateTimeService
{
    const BASE_TIMEZONE = 'UTC';

    /**
     * @return string
     * @throws \Exception
     */
    public function now(): string
    {
        return (new \DateTime(
            'now',
            new \DateTimeZone(self::BASE_TIMEZONE))
        )->format('Y-m-d H:i:s P');
    }
}
