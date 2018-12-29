<?php

return [
  'dataUrl' => '/report/AgentsPhonesStatsReportHandler.json',
  'connection' => '',
  'className' => 'App\\ViewModels\\HdsAgentsPhonesStatView',
  'columns' =>
  [
    'regionTitle' =>
    [
      'id' => 'region',
      'name' => 'Регион',
      'width' => 10,
      'sortable' => true,
      'filterable' => true,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'cityTitle' =>
    [
      'id' => 'city',
      'name' => 'Город',
      'width' => 10,
      'sortable' => true,
      'filterable' => true,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'officeTitle' =>
    [
      'id' => 'office',
      'name' => 'Офис',
      'width' => 25,
      'sortable' => true,
      'filterable' => true,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'employees' =>
    [
      'id' => 'people-v',
      'name' => 'Сотрудников',
      'width' => '50px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'hwPhonesActive' =>
    [
      'id' => 'hw-phone-active-v',
      'name' => 'HW Phones<br>(актив.)',
      'width' => '50px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
        0 => 'class_1',
        1 => 'class_2',
      ],
    ],
    'plPrefix' =>
    [
      'id' => 'pl-prefix-v',
      'name' => 'stat-by-prefix',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'plPlatform' =>
    [
      'id' => 'pl-platform-v',
      'name' => 'stat-by-platform',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'officeId' =>
    [
      'id' => 'office-id',
      'name' => 'OfficeId',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
      'classes' =>
      [
      ],
    ],
  ],
  'calculated' =>
  [
  ],
  'aliases' =>
  [
  ],
  'extraColumns' =>
  [
  ],
  'bodyFooterTable' => 'devGeoPivotStatisticByAgentsPhonesWithBodyFooterBF',
  'sortOrderSets' =>
  [
    'region' =>
    [
      'regionTitle' => '',
      'cityTitle' => '',
      'officeTitle' => '',
    ],
    'city' =>
    [
      'cityTitle' => '',
      'officeTitle' => '',
    ],
  ],
  'sortBy' =>
  [
    'regionTitle' => '',
    'cityTitle' => '',
    'officeTitle' => '',
  ],
  'preFilter' =>
  [
  ],
  'pagination' =>
  [
    'rowsOnPageList' =>
    [
      0 => 10,
      1 => 50,
      2 => 100,
      3 => 200,
      4 => 500,
      5 => 'все',
    ],
  ],
  'cssStyles' =>
  [
    'header' =>
    [
      'table' =>
      [
        0 => 'bg-primary',
        1 => 'table-bordered',
        2 => 'table-header-rotated',
      ],
    ],
    'body' =>
    [
      'table' =>
      [
        0 => 'table',
        1 => 'cell-bordered',
        2 => 'cust-table-striped',
      ],
    ],
    'footer' =>
    [
      'table' =>
      [
      ],
    ],
  ],
  'sizes' =>
  [
    'width' => 100,
    'height' => '',
  ],
  'pivot' =>
  [
    'plPrefix' =>
    [
      'column' => 'prefix',
      'preFilter' =>
      [
      ],
      'selectBy' =>
      [
        0 => 'officeId',
      ],
      'sortBy' =>
      [
        'prefix' => 'asc',
      ],
      'itemWidth' => '50px',
    ],
    'plPlatform' =>
    [
      'column' => 'platformTitle',
      'preFilter' =>
      [
      ],
      'selectBy' =>
      [
        0 => 'officeId',
      ],
      'sortBy' =>
      [
        'platformTitle' => 'asc',
      ],
      'itemWidth' => '50px',
    ],
  ],
];