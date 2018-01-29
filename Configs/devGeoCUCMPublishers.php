<?php

return [
  'dataUrl' => '/report/getCucmPublishers.json',
  'connection' => '',
  'className' => 'App\\ViewModels\\DevGeo_View',
  'columns' =>
  [
    'managementIp' =>
    [
      'id' => '',
      'name' => '',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
      'classes' =>
      [
      ],
    ],
    'appDetails' =>
    [
      'id' => '',
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
  ],
  'bodyFooterTable' => '',
  'sortOrderSets' =>
  [
    'default' =>
    [
      'managementIp' => '',
    ],
  ],
  'sortBy' =>
  [
    'managementIp' => '',
  ],
  'preFilter' =>
  [
    'appType' =>
    [
      'eq' =>
      [
        0 => 'cmp',
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
      4 => 300,
      5 => 500,
      6 => 'все',
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