<?php

namespace App\ViewModels;

use T4\Mvc\Application;
use T4\Orm\Model;

class DevCallsStats extends Model
{

    protected static $schema = [
        'table' => 'view.dev_calls_stats',
        'columns' => [
            'appliance_id' => ['type' => 'int', 'length' => 'big'],
            'device_name' => ['type' => 'string'],
            'last_call_day' => ['type' => 'datetime'],
            'd0_calls_amount' => ['type' => 'int', 'length' => 'big'],
            'm0_calls_amount' => ['type' => 'int', 'length' => 'big'],
            'm1_calls_amount' => ['type' => 'int', 'length' => 'big'],
            'm2_calls_amount' => ['type' => 'int', 'length' => 'big'],
        ]
    ];

    protected function beforeSave()
    {
        return false;
    }


    /**
     * @return array [
     *      'total' => [
     *          'd0Hw_total_nonCallingDevAmount' => 44,   // колличество незвонивших HW телефонов за текущий день во всех офисах
     *          'd0An_total_nonCallingDevAmount' => 55,   // колличество незвонивших ANALOG телефонов за текущий день во всех офисах
     *          'm0Hw_total_nonCallingDevAmount' => 29,   // колличество незвонивших HW телефонов за текущий месяц во всех офисах
     *          'm0An_total_nonCallingDevAmount' => 39,   // колличество незвонивших ANALOG телефонов за текущий во всех офисах
     *          'm1Hw_total_nonCallingDevAmount' => 11,   // колличество незвонивших HW телефонов за прошлый месяц во всех офисах
     *          'm1An_total_nonCallingDevAmount' => 21,   // колличество незвонивших ANALOG телефонов за прошлый во всех офисах
     *          'm2Hw_total_nonCallingDevAmount' => 15,   // колличество незвонивших HW телефонов за позапрошлый месяц во всех офисах
     *          'm2An_total_nonCallingDevAmount' => 16,   // колличество незвонивших ANALOG телефонов за позапрошлый во всех офисах
     *      ],
     *      'offices' => [
     *          office_id => [
     *              'office_id' => 368,                   // Id офиса
     *              'd0Hw_nonCallingDevAmount' => 4,      // колличество незвонивших HW телефонов за текущий день в офисе
     *              'd0An_nonCallingDevAmount' => 5,      // колличество незвонивших ANALOG телефонов за текущий день в офисе
     *              'm0Hw_nonCallingDevAmount' => 9,      // колличество незвонивших HW телефонов за текущий месяц в офисе
     *              'm0An_nonCallingDevAmount' => 19,     // колличество незвонивших ANALOG телефонов за текущий месяц в офисе
     *              'm1Hw_nonCallingDevAmount' => 1,      // колличество незвонивших HW телефонов за прошлый месяц в офисе
     *              'm1An_nonCallingDevAmount' => 1,      // колличество незвонивших ANALOG телефонов за прошлый месяц в офисе
     *              'm2Hw_nonCallingDevAmount' => 1,      // колличество незвонивших HW телефонов за позапрошлый месяц в офисе
     *              'm2An_nonCallingDevAmount' => 1,      // колличество незвонивших ANALOG телефонов за позапрошлый месяц в офисе
     *          ]
     *      ]
     * ]
     */
    public static function getAmountOfNonCallingDevicesByOffices()
    {
        $app = Application::instance();

        $sql = '
            SELECT
                office_id,
                count(*) FILTER (WHERE "isHW" = TRUE AND d0_calls_amount ISNULL) AS "d0Hw_nonCallingDevAmount",
                count(*) FILTER (WHERE "isHW" = TRUE AND m0_calls_amount ISNULL) AS "m0Hw_nonCallingDevAmount",
                count(*) FILTER (WHERE "isHW" = TRUE AND m1_calls_amount ISNULL) AS "m1Hw_nonCallingDevAmount",
                count(*) FILTER (WHERE "isHW" = TRUE AND m2_calls_amount ISNULL) AS "m2Hw_nonCallingDevAmount",
                
                count(*) FILTER (WHERE "isHW" = FALSE AND d0_calls_amount ISNULL) AS "d0An_nonCallingDevAmount",
                count(*) FILTER (WHERE "isHW" = FALSE AND m0_calls_amount ISNULL) AS "m0An_nonCallingDevAmount",
                count(*) FILTER (WHERE "isHW" = FALSE AND m1_calls_amount ISNULL) AS "m1An_nonCallingDevAmount",
                count(*) FILTER (WHERE "isHW" = FALSE AND m2_calls_amount ISNULL) AS "m2An_nonCallingDevAmount"
            FROM view.dev_phone_info_geo
            WHERE "appType" = \'phone\' AND (d0_calls_amount ISNULL OR m0_calls_amount ISNULL OR m1_calls_amount ISNULL OR m2_calls_amount ISNULL)
            GROUP BY office_id';
        $callsStats = $app->db->default->query($sql)->fetchAll(\PDO::FETCH_ASSOC);


        // Statistics Of Non Calling Devices By Offices
        $nonCallingDevicesStatsByOffices = [];
        foreach ($callsStats as $item) {
            $nonCallingDevicesStatsByOffices['total']['d0Hw_total_nonCallingDevAmount'] += $item['d0Hw_nonCallingDevAmount'];
            $nonCallingDevicesStatsByOffices['total']['m0Hw_total_nonCallingDevAmount'] += $item['m0Hw_nonCallingDevAmount'];
            $nonCallingDevicesStatsByOffices['total']['m1Hw_total_nonCallingDevAmount'] += $item['m1Hw_nonCallingDevAmount'];
            $nonCallingDevicesStatsByOffices['total']['m2Hw_total_nonCallingDevAmount'] += $item['m2Hw_nonCallingDevAmount'];

            $nonCallingDevicesStatsByOffices['total']['d0An_total_nonCallingDevAmount'] += $item['d0An_nonCallingDevAmount'];
            $nonCallingDevicesStatsByOffices['total']['m0An_total_nonCallingDevAmount'] += $item['m0An_nonCallingDevAmount'];
            $nonCallingDevicesStatsByOffices['total']['m1An_total_nonCallingDevAmount'] += $item['m1An_nonCallingDevAmount'];
            $nonCallingDevicesStatsByOffices['total']['m2An_total_nonCallingDevAmount'] += $item['m2An_nonCallingDevAmount'];

            $nonCallingDevicesStatsByOffices['offices'][$item['office_id']] = $item;
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
