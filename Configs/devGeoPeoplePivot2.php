<?php

return [
  'dataUrl' => '/test/devicesPivotTable.json',
  'className' => 'App\\ViewModels\\DevGeoPeople_1',
  'columns' =>
  [
    'regCenter' =>
    [
      'id' => 'regcent',
      'name' => 'Рег.центр',
      'width' => 12,
      'sortable' => true,
      'filterable' => true,
    ],
    'region' =>
    [
      'id' => 'region',
      'name' => 'Регион',
      'width' => 10,
      'sortable' => true,
      'filterable' => true,
    ],
    'city' =>
    [
      'id' => 'city',
      'name' => 'Город',
      'width' => 10,
      'sortable' => true,
      'filterable' => true,
    ],
    'office' =>
    [
      'id' => 'office',
      'name' => 'Офис',
      'width' => 15,
      'sortable' => true,
      'filterable' => true,
    ],
    'people' =>
    [
      'id' => 'people',
      'name' => 'Сотр.',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
    ],
    'plTitle' =>
    [
      'id' => 'pl',
      'name' => 'Оборудование',
      'width' => 65,
      'sortable' => false,
      'filterable' => false,
    ],
    'plTitleActive' =>
    [
      'id' => '',
      'name' => '',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
    ],
  ],
  'aliases' =>
  [
  ],
  'extraColumns' =>
  [
  ],
  'sortOrderSets' =>
  [
    'regCenter' =>
    [
      'regCenter' => '',
      'region' => '',
      'city' => '',
      'office' => '',
    ],
    'region' =>
    [
      'region' => '',
      'city' => '',
      'office' => '',
    ],
    'city' =>
    [
      'city' => '',
      'office' => '',
    ],
  ],
  'sortBy' =>
  [
    'regCenter' => '',
    'region' => '',
    'city' => '',
    'office' => '',
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
    'plTitle' =>
    [
      'column' => 'platformTitle',
      'display' => true,
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
      'sortBy' =>
      [
        'platformTitle' => 'desc',
      ],
      'itemWidth' => '70px',
    ],
    'plTitleActive' =>
    [
      'column' => 'platformTitle',
      'display' => false,
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
            0 => 200,
          ],
        ],
      ],
      'sortBy' =>
      [
      ],
      'itemWidth' => 0,
    ],
  ],
];