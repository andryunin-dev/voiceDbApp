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

    /**
     * Datetime difference relative to current datetime
     * @param string $datetime
     * @return \DateInterval|false
     * @throws \Exception
     */
    public function timeDifference(string $datetime): \DateInterval
    {
        $now =
            (new \DateTime('now'))
                ->setTimezone(
                    new \DateTimeZone('Europe/Moscow')
                );
        $datetime =
            (new \DateTime($datetime))
                ->setTimezone(
                    new \DateTimeZone('Europe/Moscow')
                );
        return $now->diff($datetime);
    }
}
