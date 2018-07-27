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
    'd0Hw_total_nonCallingDevAmount' =>
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
    'm0Hw_total_nonCallingDevAmount' =>
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
    'm1Hw_total_nonCallingDevAmount' =>
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
    'm2Hw_total_nonCallingDevAmount' =>
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
    'd0An_total_nonCallingDevAmount' =>
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
    'm0An_total_nonCallingDevAmount' =>
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
    'm1An_total_nonCallingDevAmount' =>
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
    'm2An_total_nonCallingDevAmount' =>
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
    2 => 'd0Hw_total_nonCallingDevAmount',
    3 => 'm0Hw_total_nonCallingDevAmount',
    4 => 'm1Hw_total_nonCallingDevAmount',
    5 => 'm2Hw_total_nonCallingDevAmount',
    6 => 'd0An_total_nonCallingDevAmount',
    7 => 'm0An_total_nonCallingDevAmount',
    8 => 'm1An_total_nonCallingDevAmount',
    9 => 'm2An_total_nonCallingDevAmount',
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