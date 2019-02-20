<?php

return [
  'dataUrl' => '/report/AgentsPhonesStatsReportHandler.json',
  'connection' => '',
  'className' => 'App\\ViewModels\\HdsAgentsPhonesStatView',
  'columns' =>
  [
    'textField' =>
    [
      'id' => 'txt_field',
      'name' => 'ИТОГО:',
      'width' => 45,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'employees' =>
    [
      'id' => 'people',
      'name' => 'Сотр.',
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
      'id' => 'hw-phone-active',
      'name' => 'HW Phones<br>(актив.)',
      'width' => '50px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'plPrefix' =>
    [
      'id' => 'pl-prefix',
      'name' => 'stat-by-prefix',
      'width' => 65,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'plPlatform' =>
    [
      'id' => 'pl-platform',
      'name' => 'stat-by-platform',
      'width' => 65,
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
      'name' => '',
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
    0 => 'textField',
    1 => 'employees',
  ],
  'bodyFooterTable' => '',
  'sortOrderSets' =>
  [
    'default' =>
    [
    ],
  ],
  'sortBy' =>
  [
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
      4 => 'все',
    ],
  ],
  'cssStyles' =>
  [
    'header' =>
    [
      'table' =>
      [
      ],
    ],
    'body' =>
    [
      'table' =>
      [
        0 => 'table',
        1 => 'bg-success',
        2 => 'table-bordered',
        3 => 'body-footer',
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