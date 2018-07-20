<?php

namespace App\ViewModels;

use T4\Mvc\Application;

class DevCallsStats
{

    /**
     * @return array [
     *      'total' => [
     *          'd0_totalAmountOfNonCallingHwDev' => 44,   // колличество незвонивших HW телефонов за текущий день во всех офисах
     *          'd0_totalAmountOfNonCallingAnDev' => 55,   // колличество незвонивших ANALOG телефонов за текущий день во всех офисах
     *          'm0_totalAmountOfNonCallingHwDev' => 29,   // колличество незвонивших HW телефонов за текущий месяц во всех офисах
     *          'm0_totalAmountOfNonCallingAnDev' => 39,   // колличество незвонивших ANALOG телефонов за текущий во всех офисах
     *          'm1_totalAmountOfNonCallingHwDev' => 11,   // колличество незвонивших HW телефонов за прошлый месяц во всех офисах
     *          'm2_totalAmountOfNonCallingAnDev' => 21,   // колличество незвонивших ANALOG телефонов за прошлый во всех офисах
     *          'm1_totalAmountOfNonCallingHwDev' => 15,   // колличество незвонивших HW телефонов за позапрошлый месяц во всех офисах
     *          'm2_totalAmountOfNonCallingAnDev' => 16,   // колличество незвонивших ANALOG телефонов за позапрошлый во всех офисах
     *      ],
     *      'offices' => [
     *          office_id => [
     *              'office_id' => 368,                     // Id офиса
     *              'd0_amountOfNonCallingHwDev' => 4,      // колличество незвонивших HW телефонов за текущий день в офисе
     *              'd0_amountOfNonCallingAnDev' => 5,      // колличество незвонивших ANALOG телефонов за текущий день в офисе
     *              'm0_amountOfNonCallingHwDev' => 9,      // колличество незвонивших HW телефонов за текущий месяц в офисе
     *              'm0_amountOfNonCallingAnDev' => 19,     // колличество незвонивших ANALOG телефонов за текущий месяц в офисе
     *              'm1_amountOfNonCallingHwDev' => 1,      // колличество незвонивших HW телефонов за прошлый месяц в офисе
     *              'm1_amountOfNonCallingAnDev' => 1,      // колличество незвонивших ANALOG телефонов за прошлый месяц в офисе
     *              'm2_amountOfNonCallingHwDev' => 1,      // колличество незвонивших HW телефонов за позапрошлый месяц в офисе
     *              'm2_amountOfNonCallingAnDev' => 1,      // колличество незвонивших ANALOG телефонов за позапрошлый месяц в офисе
     *          ]
     *      ]
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
                sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :sss) AS m0_calls_amount,
                sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :last_month) AS m1_calls_amount,
                sum(call_quan) FILTER (WHERE date_trunc(\'month\', date) = :before_last_month) AS m2_calls_amount
              FROM cdr_call_activ.dev_nd_quan
              GROUP BY dev
            ) AS calls_stats
            WHERE d0_calls_amount ISNULL OR m0_calls_amount ISNULL OR m1_calls_amount ISNULL OR m2_calls_amount ISNULL';
        $params = [
            'sss' => date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y'))),
            ':last_month' => date('Y-m-d', mktime(0, 0, 0, date('m')-1, 1, date('Y'))),
            ':before_last_month' => date('Y-m-d', mktime(0, 0, 0, date('m')-2, 1, date('Y'))),
        ];
        $callsStats = $app->db->cdr->query($sql, $params)->fetchAll(\PDO::FETCH_ASSOC);
        $devsCallsStats = [];
        foreach ($callsStats as $item) {
            $devsCallsStats[mb_strtoupper($item['dev'])] = $item;
        }

        // Device location
        $sql = 'SELECT name AS dev, office_id, "isHW" FROM view.dev_phone_info_geo';
        $devInOffice = $app->db->default->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        $devsData = [];
        foreach ($devInOffice as $item) {
            $devsData[mb_strtoupper($item['dev'])] = $item;
        }

        // Statistics Of Non Calling Devices By Offices
        $nonCallingDevicesStatsByOffices = [];
        $nonCallingDevicesStatsByOffices['total']['d0_totalAmountOfNonCallingHwDev'] = 0;
        $nonCallingDevicesStatsByOffices['total']['d0_totalAmountOfNonCallingAnDev'] = 0;
        $nonCallingDevicesStatsByOffices['total']['m0_totalAmountOfNonCallingHwDev'] = 0;
        $nonCallingDevicesStatsByOffices['total']['m0_totalAmountOfNonCallingAnDev'] = 0;
        $nonCallingDevicesStatsByOffices['total']['m1_totalAmountOfNonCallingHwDev'] = 0;
        $nonCallingDevicesStatsByOffices['total']['m1_totalAmountOfNonCallingAnDev'] = 0;
        $nonCallingDevicesStatsByOffices['total']['m2_totalAmountOfNonCallingHwDev'] = 0;
        $nonCallingDevicesStatsByOffices['total']['m2_totalAmountOfNonCallingAnDev'] = 0;

        foreach ($devsCallsStats as $dev => $devCallsStats) {
            $devData = $devsData[$dev];
            if (!is_null($devData)) {
                $officeId = $devData['office_id'];
                if (is_null($nonCallingDevicesStatsByOffices['offices'][$officeId]['office_id'])) {
                    $nonCallingDevicesStatsByOffices['offices'][$officeId]['office_id'] = $officeId;
                }
                if (is_null($devCallsStats['d0_calls_amount'])) {
                    if ($devData['isHW']) {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['d0_amountOfNonCallingHwDev']++;
                        $nonCallingDevicesStatsByOffices['total']['d0_totalAmountOfNonCallingHwDev']++;
                    } else {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['d0_amountOfNonCallingAnDev']++;
                        $nonCallingDevicesStatsByOffices['total']['d0_totalAmountOfNonCallingAnDev']++;
                    }
                }
                if (is_null($devCallsStats['m0_calls_amount'])) {
                    if ($devData['isHW']) {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['m0_amountOfNonCallingHwDev']++;
                        $nonCallingDevicesStatsByOffices['total']['m0_totalAmountOfNonCallingHwDev']++;
                    } else {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['m0_amountOfNonCallingAnDev']++;
                        $nonCallingDevicesStatsByOffices['total']['m0_totalAmountOfNonCallingAnDev']++;
                    }
                }
                if (is_null($devCallsStats['m1_calls_amount'])) {
                    if ($devData['isHW']) {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['m1_amountOfNonCallingHwDev']++;
                        $nonCallingDevicesStatsByOffices['total']['m1_totalAmountOfNonCallingHwDev']++;
                    } else {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['m1_amountOfNonCallingAnDev']++;
                        $nonCallingDevicesStatsByOffices['total']['m1_totalAmountOfNonCallingAnDev']++;
                    }
                }
                if (is_null($devCallsStats['m2_calls_amount'])) {
                    if ($devData['isHW']) {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['m2_amountOfNonCallingHwDev']++;
                        $nonCallingDevicesStatsByOffices['total']['m2_totalAmountOfNonCallingHwDev']++;
                    } else {
                        $nonCallingDevicesStatsByOffices['offices'][$officeId]['m2_amountOfNonCallingAnDev']++;
                        $nonCallingDevicesStatsByOffices['total']['m2_totalAmountOfNonCallingAnDev']++;
                    }
                }
            }
        }
        return $nonCallingDevicesStatsByOffices;
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
