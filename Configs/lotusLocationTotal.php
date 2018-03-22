<?php

return [
  'dataUrl' => '/test/devicesPivotTable.json',
  'connection' => 'lotusData',
  'className' => 'App\\Models\\LotusLocation',
  'columns' =>
  [
    'employees' =>
    [
      'id' => 'employees',
      'name' => 'Сотр.',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
  ],
  'calculated' =>
  [
    'employees' =>
    [
      'column' => 'employees',
      'method' => 'sum',
      'preFilter' =>
      [
      ],
    ],
  ],
  'aliases' =>
  [
  ],
  'extraColumns' =>
  [
  ],
  'bodyFooterTable' => '',
  'sortOrderSets' =>
  [
    'default' =>
    [
      'employees' => '',
    ],
  ],
  'sortBy' =>
  [
    'employees' => '',
  ],
  'preFilter' =>
  [
  ],
  'pagination' =>
  [
    'rowsOnPageList' =>
    [
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
  ],
];