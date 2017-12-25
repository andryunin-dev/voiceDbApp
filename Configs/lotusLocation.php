<?php

return [
  'dataUrl' => '/test/devicesPivotTable.json',
  'connection' => 'lotusData',
  'className' => 'App\\Models\\LotusLocation',
  'columns' =>
  [
    'lotus_id' =>
    [
      'id' => 'lotus-id',
      'name' => 'Lotus ID',
      'width' => 12,
      'sortable' => true,
      'filterable' => true,
    ],
    'title' =>
    [
      'id' => 'title',
      'name' => 'Офис',
      'width' => 10,
      'sortable' => true,
      'filterable' => true,
    ],
    'reg_center' =>
    [
      'id' => 'regc',
      'name' => 'Рег.ц',
      'width' => 10,
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
    'address' =>
    [
      'id' => 'addr',
      'name' => 'Адрес',
      'width' => 15,
      'sortable' => true,
      'filterable' => true,
    ],
    'employees' =>
    [
      'id' => 'employees',
      'name' => 'Сотр.',
      'width' => '60px',
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
      'reg_center' => '',
      'region' => '',
      'city' => '',
      'title' => '',
    ],
    'region' =>
    [
      'region' => '',
      'city' => '',
      'title' => '',
    ],
    'city' =>
    [
      'city' => '',
      'title' => '',
    ],
  ],
  'sortBy' =>
  [
    'reg_center' => '',
    'region' => '',
    'city' => '',
    'title' => '',
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
];