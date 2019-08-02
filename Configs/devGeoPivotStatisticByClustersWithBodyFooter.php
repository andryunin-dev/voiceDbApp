<?php

return [
  'dataUrl' => '/report/PhoneStatsByClustersReportHandler.json',
  'connection' => '',
  'className' => 'App\\ViewModels\\DevPhoneInfoGeoMat',
  'columns' =>
  [
    'region' =>
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
    'city' =>
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
    'office' =>
    [
      'id' => 'office',
      'name' => 'Офис',
      'width' => 15,
      'sortable' => true,
      'filterable' => true,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'lotus_employees' =>
    [
      'id' => 'people-v',
      'name' => 'Сотрудников',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'phoneAmount' =>
    [
      'id' => 'phone-count',
      'name' => 'кол-во тел.',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'HWActive' =>
    [
      'id' => 'hw-active-v',
      'name' => 'HW Phones<br>(актив.)',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
        0 => 'class_1',
        1 => 'class_2',
      ],
    ],
    'notHWActive' =>
    [
      'id' => 'not-hw-active-v',
      'name' => 'virtual & analog<br>Phones(актив.)',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
        0 => 'class_1',
        1 => 'class_2',
      ],
    ],
    'byPublishIp' =>
    [
      'id' => 'pub',
      'name' => 'Оборудование',
      'width' => 65,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'byPublishIpActive' =>
    [
      'id' => 'pub-active',
      'name' => 'Оборудование',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
      'classes' =>
      [
      ],
    ],
    'byPublishIpActiveHW' =>
    [
      'id' => 'pub-active-hw',
      'name' => 'Оборудование',
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
    'phoneAmount' =>
    [
      'column' => 'appliance_id',
      'method' => 'count',
      'preFilter' =>
      [
      ],
      'selectBy' =>
      [
      ],
    ],
    'HWActive' =>
    [
      'column' => 'appType',
      'method' => 'count',
      'preFilter' =>
      [
        'appType' =>
        [
          'eq' =>
          [
            0 => 'phone',
          ],
        ],
        'isHW' =>
        [
          'eq' =>
          [
            0 => 'true',
          ],
        ],
        'appAge' =>
        [
          'lt' =>
          [
            0 => 73,
          ],
        ],
        'publisherIp' =>
        [
          'eq' =>
          [
            0 => '10.30.30.70',
            1 => '10.30.30.21',
            2 => '10.101.19.100',
            3 => '10.101.15.10',
            4 => '10.30.48.10',
          ],
        ],
      ],
      'selectBy' =>
      [
        0 => 'lotusId',
      ],
    ],
    'notHWActive' =>
    [
      'column' => 'appType',
      'method' => 'count',
      'preFilter' =>
      [
        'appType' =>
        [
          'eq' =>
          [
            0 => 'phone',
          ],
        ],
        'isHW' =>
        [
          'eq' =>
          [
            0 => 'false',
          ],
        ],
        'appAge' =>
        [
          'lt' =>
          [
            0 => 73,
          ],
        ],
        'publisherIp' =>
        [
          'eq' =>
          [
            0 => '10.30.30.70',
            1 => '10.30.30.21',
            2 => '10.101.19.100',
            3 => '10.101.15.10',
            4 => '10.30.48.10',
          ],
        ],
      ],
      'selectBy' =>
      [
        0 => 'lotusId',
      ],
    ],
  ],
  'aliases' =>
  [
  ],
  'extraColumns' =>
  [
  ],
  'bodyFooterTable' => 'devGeoPivotStatisticByClustersWithBodyFooterBF',
  'sortOrderSets' =>
  [
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
    'publisherIp' =>
    [
      'eq' =>
      [
        0 => '10.30.30.70',
        1 => '10.30.30.21',
        2 => '10.101.19.100',
        3 => '10.101.15.10',
        4 => '10.30.48.10',
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
    'byPublishIp' =>
    [
      'column' => 'publisherIp',
      'preFilter' =>
      [
        'appType' =>
        [
          'eq' =>
          [
            0 => 'phone',
          ],
        ],
        'publisherIp' =>
        [
          'eq' =>
          [
            0 => '10.30.30.70',
            1 => '10.30.30.21',
            2 => '10.101.19.100',
            3 => '10.101.15.10',
            4 => '10.30.48.10',
          ],
        ],
      ],
      'selectBy' =>
      [
        0 => 'lotusId',
      ],
      'sortBy' =>
      [
        'publisherIp' => 'desc',
      ],
      'itemWidth' => '67px',
    ],
    'byPublishIpActive' =>
    [
      'column' => 'publisherIp',
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
        'publisherIp' =>
        [
          'eq' =>
          [
            0 => '10.30.30.70',
            1 => '10.30.30.21',
            2 => '10.101.19.100',
            3 => '10.101.15.10',
            4 => '10.30.48.10',
          ],
        ],
      ],
      'selectBy' =>
      [
        0 => 'lotusId',
      ],
      'sortBy' =>
      [
      ],
      'itemWidth' => 0,
    ],
    'byPublishIpActiveHW' =>
    [
      'column' => 'publisherIp',
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
        'isHW' =>
        [
          'eq' =>
          [
            0 => 'true',
          ],
        ],
        'publisherIp' =>
        [
          'eq' =>
          [
            0 => '10.30.30.70',
            1 => '10.30.30.21',
            2 => '10.101.19.100',
            3 => '10.101.15.10',
            4 => '10.30.48.10',
          ],
        ],
      ],
      'selectBy' =>
      [
        0 => 'lotusId',
      ],
      'sortBy' =>
      [
      ],
      'itemWidth' => 0,
    ],
  ],
];