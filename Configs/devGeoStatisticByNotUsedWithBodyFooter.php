<?php

return [
  'dataUrl' => '/report/PhoneStatsByNotUsedReportHandler.json',
  'connection' => '',
  'className' => 'App\\ViewModels\\DevGeo_ViewMat',
  'columns' =>
  [
    'region' =>
    [
      'id' => 'region',
      'name' => 'Регион',
      'width' => 13,
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
      'width' => 13,
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
    'HWNotActive' =>
    [
      'id' => 'hw-not-active-v',
      'name' => 'HW Phones<br>(не актив.)',
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
    'lotusId' =>
    [
      'id' => 'lot_id',
      'name' => 'ID',
      'width' => '50px',
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
      'classes' =>
      [
      ],
    ],
    'office_id' =>
    [
      'id' => 'officeId',
      'name' => 'office-id',
      'width' => '50px',
      'sortable' => false,
      'filterable' => false,
      'visible' => false,
      'classes' =>
      [
      ],
    ],
    'd0_amountOfNonCallingHwDev' =>
    [
      'id' => 'd0-amount-OfNonCallingHwDev-v',
      'name' => 'Phones HW<br>not used<br>ДЕНЬ тек.',
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
    'm0_amountOfNonCallingHwDev' =>
    [
      'id' => 'm0-amount-OfNonCallingHwDev-v',
      'name' => 'Phones HW<br>not used<br>МЕСЯЦ тек.',
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
    'm1_amountOfNonCallingHwDev' =>
    [
      'id' => 'm1-amount-OfNonCallingHwDev-v',
      'name' => 'Phones HW<br>not used<br>1 МЕС. назад',
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
    'm2_amountOfNonCallingHwDev' =>
    [
      'id' => 'm2-amount-OfNonCallingHwDev-v',
      'name' => 'Phones HW<br>not used<br>2 МЕС. назад',
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
    'd0_amountOfNonCallingAnDev' =>
    [
      'id' => 'd0-amount-OfNonCallingAnalogDev-v',
      'name' => 'Phones AN<br>not used<br>ДЕНЬ тек.',
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
    'm0_amountOfNonCallingAnDev' =>
    [
      'id' => 'm0-amount-OfNonCallingAnalogDev-v',
      'name' => 'Phones AN<br>not used<br>МЕСЯЦ тек.',
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
    'm1_amountOfNonCallingAnDev' =>
    [
      'id' => 'm1-amount-OfNonCallingAnalogDev-v',
      'name' => 'Phones AN<br>not used<br>1 МЕС. назад',
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
    'm2_amountOfNonCallingAnDev' =>
    [
      'id' => 'm2-amount-OfNonCallingAnalogDev-v',
      'name' => 'Phones AN<br>not used<br>2 МЕС. назад',
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
        0 => 'lotusId',
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
    0 => 'd0_amountOfNonCallingHwDev',
    1 => 'm0_amountOfNonCallingHwDev',
    2 => 'm1_amountOfNonCallingHwDev',
    3 => 'm2_amountOfNonCallingHwDev',
    4 => 'd0_amountOfNonCallingAnDev',
    5 => 'm0_amountOfNonCallingAnDev',
    6 => 'm1_amountOfNonCallingAnDev',
    7 => 'm2_amountOfNonCallingAnDev',
  ],
  'bodyFooterTable' => 'devGeoStatisticByNotUsedWithBodyFooterBF',
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
  ],
  'pagination' =>
  [
    'rowsOnPageList' =>
    [
      0 => 10,
      1 => 50,
      2 => 100,
      3 => 200,
      4 => 500,
      5 => 'все',
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
  ],
];