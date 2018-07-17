<?php

namespace App\ViewModels;

use T4\Mvc\Application;

class DevCallsStats
{

    /**
     * @return array [
     *    office_id => [
     *      'office_id' => 368,                         // Id офиса
     *      'd0_amount_of_non_calling_devices' => 4,    // колличество незвонивших телефонов за текущий день в офисе
     *      'm0_amount_of_non_calling_devices' => 19,   // колличество незвонивших телефонов за текущий месяц в офисе
     *      'm1_amount_of_non_calling_devices' => 1,    // колличество незвонивших телефонов за прошлый месяц в офисе
     *      'm2_amount_of_non_calling_devices' => 1,    // колличество незвонивших телефонов за позапрошлый месяц в офисе
     *    ]
     * ]
     */
    public static function getAmountOfNonCallingDevicesByOffices()
    {
        $app = Application::instance();

        // Device calls statistics
        $sql = '
            SELECT
              *
            FROM (
              SELECT
                dev,
                sum(call_quan) FILTER (WHERE date = current_date) AS d0_calls_amount,
                sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :current_month) AS m0_calls_amount,
                sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :last_month) AS m1_calls_amount,
                sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :before_last_month) AS m2_calls_amount
              FROM cdr_call_activ.dev_nd_quan
              GROUP BY dev
            ) AS calls_stats
            WHERE d0_calls_amount ISNULL OR m0_calls_amount ISNULL OR m1_calls_amount ISNULL OR m2_calls_amount ISNULL';
        $params = [
            ':current_month' => date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y'))),
            ':last_month' => date('Y-m-d', mktime(0, 0, 0, date('m')-1, 1, date('Y'))),
            ':before_last_month' => date('Y-m-d', mktime(0, 0, 0, date('m')-2, 1, date('Y'))),
        ];
        $callsStats = $app->db->cdr->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        $devCallsStats = [];
        foreach ($callsStats as $item) {
            $devCallsStats[mb_strtoupper($item['dev'])] = $item;
        }

        // Device location
        $sql = 'SELECT name AS dev, office_id FROM view.dev_phone_info_geo';
        $devInOffice = $app->db->default->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $devLocation = [];
        foreach ($devInOffice as $item) {
            $devLocation[mb_strtoupper($item['dev'])] = $item;
        }

        // Amount Of Non Calling Devices By Offices
        $locationNullCallsStats = [];
        foreach ($devCallsStats as $dev => $callsStat) {
            if (array_key_exists($dev, $devLocation)) {
                $officeId = $devLocation[$dev]['office_id'];
                if (is_null($locationNullCallsStats[$officeId]['office_id'])) {
                    $locationNullCallsStats[$officeId]['office_id'] = $devLocation[$dev]['office_id'];
                }
                if (is_null($callsStat['d0_calls_amount'])) {
                    $locationNullCallsStats[$officeId]['d0_amount_of_non_calling_devices']++;
                }
                if (is_null($callsStat['m0_calls_amount'])) {
                    $locationNullCallsStats[$officeId]['m0_amount_of_non_calling_devices']++;
                }
                if (is_null($callsStat['m1_calls_amount'])) {
                    $locationNullCallsStats[$officeId]['m1_amount_of_non_calling_devices']++;
                }
                if (is_null($callsStat['m2_calls_amount'])) {
                    $locationNullCallsStats[$officeId]['m2_amount_of_non_calling_devices']++;
                }
            }
        }
        return $locationNullCallsStats;
    }

    /**
     * @return array [
     *    dev => [
     *      'dev' => 'SEP............',         // Device's name
     *      'last_call_day' => '2018-07-16',    // дата последнего звонка
     *      'd0_calls_amount' => null,          // колличество звонков за текущий день
     *      'm0_calls_amount' => 19,            // колличество звонков за текущий месяц
     *      'm1_calls_amount' => 1,             // колличество звонков за прошлый месяц
     *      'm2_calls_amount' => 1,             // колличество звонков за позапрошлый месяц
     *    ]
     * ]
     */
    public static function getDeviceCallStatisticsForLastThreeMonth()
    {
        $app = Application::instance();

        $sql = '
            SELECT
              dev,
              max(date) AS last_call_day,
              sum(call_quan) FILTER (WHERE date = current_date) AS d0_calls_amount,
              sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :current_month) AS m0_calls_amount,
              sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :last_month) AS m1_calls_amount,
              sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :before_last_month) AS m2_calls_amount
            FROM cdr_call_activ.dev_nd_quan
            GROUP BY dev;';
        $params = [
            ':current_month' => date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y'))),
            ':last_month' => date('Y-m-d', mktime(0, 0, 0, date('m')-1, 1, date('Y'))),
            ':before_last_month' => date('Y-m-d', mktime(0, 0, 0, date('m')-2, 1, date('Y'))),
        ];
        $items = $app->db->cdr->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);

        $phonesCallsStats = [];
        foreach ($items as $item) {
            $phonesCallsStats[$item['dev']] = $item;
        }
        return $phonesCallsStats;
    }
}
