<?php

return [
  'dataUrl' => '/report/PhoneStatsByNotUsedReportHandler.json',
  'connection' => '',
  'className' => 'App\\ViewModels\\DevGeo_ViewMat',
  'columns' =>
  [
    'textField' =>
    [
      'id' => 'txt_field',
      'name' => 'ИТОГО:',
      'width' => 31,
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'appType' =>
    [
      'id' => 'app_type',
      'name' => 'appType',
      'width' => 10,
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
      'classes' =>
      [
      ],
    ],
    'employees' =>
    [
      'id' => 'people-v',
      'name' => 'Сотр.',
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
      'id' => 'hw-active',
      'name' => 'HW Phones',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'HWNotActive' =>
    [
      'id' => 'hw-not-active',
      'name' => 'HW not active Phones',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'notHWActive' =>
    [
      'id' => 'not-hw-active-v',
      'name' => 'not HW Phones',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'd0_totalAmountOfNonCallingHwDev' =>
    [
      'id' => 'd0-amount-OfNonCallingHwDev-v',
      'name' => '',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'm0_totalAmountOfNonCallingHwDev' =>
    [
      'id' => 'm0-amount-OfNonCallingHwDev-v',
      'name' => '',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'm1_totalAmountOfNonCallingHwDev' =>
    [
      'id' => 'm1-amount-OfNonCallingHwDev-v',
      'name' => '',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'm2_totalAmountOfNonCallingHwDev' =>
    [
      'id' => 'm2-amount-OfNonCallingHwDev-v',
      'name' => '',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'd0_totalAmountOfNonCallingAnDev' =>
    [
      'id' => 'd0-amount-OfNonCallingAnalogDev-v',
      'name' => '',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'm0_totalAmountOfNonCallingAnDev' =>
    [
      'id' => 'm0-amount-OfNonCallingAnalogDev-v',
      'name' => '',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'm1_totalAmountOfNonCallingAnDev' =>
    [
      'id' => 'm1-amount-OfNonCallingAnalogDev-v',
      'name' => '',
      'width' => '60px',
      'sortable' => false,
      'filterable' => false,
      'visible' => true,
      'classes' =>
      [
      ],
    ],
    'm2_totalAmountOfNonCallingAnDev' =>
    [
      'id' => 'm2-amount-OfNonCallingAnalogDev-v',
      'name' => '',
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
      ],
      'selectBy' =>
      [
      ],
    ],
    'HWNotActive' =>
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
          'ge' =>
          [
            0 => 73,
          ],
        ],
      ],
      'selectBy' =>
      [
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
      ],
      'selectBy' =>
      [
      ],
    ],
  ],
  'aliases' =>
  [
  ],
  'extraColumns' =>
  [
    0 => 'textField',
    1 => 'employees',
    2 => 'd0_totalAmountOfNonCallingHwDev',
    3 => 'm0_totalAmountOfNonCallingHwDev',
    4 => 'm1_totalAmountOfNonCallingHwDev',
    5 => 'm2_totalAmountOfNonCallingHwDev',
    6 => 'd0_totalAmountOfNonCallingAnDev',
    7 => 'm0_totalAmountOfNonCallingAnDev',
    8 => 'm1_totalAmountOfNonCallingAnDev',
    9 => 'm2_totalAmountOfNonCallingAnDev',
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
  ],
];