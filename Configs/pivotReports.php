<?php

return [
  'test2' =>
  [
    'className' => 'App\\ViewModels\\GeoDev_View',
    'pivotColumn' =>
    [
      'name' =>
      [
        0 => 'platformTitle',
      ],
      'sortOrder' =>
      [
        'platformTitle' => 'ASC',
      ],
      'filter' =>
      [
        'allowNull' => false,
        'conditions' =>
        [
          'appType' =>
          [
            0 => 'phone',
          ],
        ],
      ],
    ],
    'columns' =>
    [
      'name' =>
      [
        0 => 'region',
        1 => 'office',
        2 => 'platformTitle',
        3 => 'officeAddress',
      ],
      'sortOrder' =>
      [
        'region' => '',
        'office' => '',
        'officeAddress' => '',
      ],
      'filter' =>
      [
        'allowNull' => false,
      ],
    ],
  ],
];