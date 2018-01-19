<?php

return [
  'dataUrl' => '/report/PhoneStatsReportHandler.json',
  'connection' => '',
  'className' => 'App\\ViewModels\\DevGeo_View',
  'columns' =>
  [
    'textField' =>
    [
      'id' => 'txt_field',
      'name' => 'txtField',
      'width' => 35,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
    ],
    'appType' =>
    [
      'id' => 'app_type',
      'name' => 'appType',
      'width' => 10,
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
    ],
    'people' =>
    [
      'id' => 'people',
      'name' => 'Сотр.',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
    ],
    'phoneAmount' =>
    [
      'id' => 'phone-count',
      'name' => 'кол-во тел.',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
    ],
    'plTitle' =>
    [
      'id' => 'pl',
      'name' => 'Оборудование',
      'width' => 65,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
    ],
    'plTitleActive' =>
    [
      'id' => 'pl_active',
      'name' => '',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
    ],
  ],
  'calculated' =>
  [
    'phoneAmount' =>
    [
      'column' => 'appliance_id',
      'method' => 'count',
    ],
  ],
  'aliases' =>
  [
  ],
  'extraColumns' =>
  [
    0 => 'textField',
    1 => 'people',
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
    'appType' =>
    [
      'eq' =>
      [
        0 => 'phone',
      ],
    ],
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
    'plTitle' =>
    [
      'column' => 'platformTitle',
      'preFilter' =>
      [
        'appType' =>
        [
          'eq' =>
          [
            0 => 'phone',
          ],
        ],
      ],
      'selectPivotItemsBy' =>
      [
        0 => 'appType',
      ],
      'sortBy' =>
      [
        'platformTitle' => 'desc',
      ],
      'itemWidth' => '65px',
    ],
    'plTitleActive' =>
    [
      'column' => 'platformTitle',
      'preFilter' =>
      [
        'appType' =>
        [
          'eq' =>
          [
            0 => 'phone',
          ],
        ],
        'appAge' =>
        [
          'lt' =>
          [
            0 => 73,
          ],
        ],
      ],
      'selectPivotItemsBy' =>
      [
        0 => 'appType',
      ],
      'sortBy' =>
      [
      ],
      'itemWidth' => 0,
    ],
  ],
];