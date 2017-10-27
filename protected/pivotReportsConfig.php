<?php

return [
  'test' =>
  [
    'className' => 'App\\ViewModels\\GeoDev_View',
    'pivotColumn' =>
    [
      'filter' =>
      [
        'allowNull' => false,
        'conditions' =>
        [
          'appType' => 'phone',
        ],
      ],
      'name' => 'platformTitle',
      'orderBy' => 'platformTitle',
      'direction' => '',
      'sqlType' => 'citext',
    ],
    'rowNamesColumn' =>
    [
      'name' => 'office',
      'orderBy' => 'office',
      'direction' => '',
      'sqlType' => 'citext',
    ],
    'valueColumn' =>
    [
      'name' => 'appliance_id',
      'sqlType' => 'citext',
      'countMethod' => 'sum',
    ],
    'extraColumns' =>
    [
      'region' => 'citext',
    ],
  ],
];