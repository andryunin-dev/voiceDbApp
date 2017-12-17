<?php

return [
  'dataUrl' => '/test/devicesTable.json',
  'className' => 'App\\ViewModels\\DevModulePortGeo',
  'columns' =>
  [
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
      'id' => '',
      'name' => '',
      'width' => 0,
      'sortable' => false,
      'filterable' => false,
    ],
    'office' =>
    [
      'id' => 'office',
      'name' => 'Оффисе',
      'width' => 150,
      'sortable' => true,
      'filterable' => true,
    ],
    'hostname_dn' =>
    [
      'id' => 'hostname_dn',
      'name' => 'hostname',
      'width' => 15,
      'sortable' => true,
      'filterable' => true,
    ],
    'appType' =>
    [
      'id' => 'app-type',
      'name' => 'Тип',
      'width' => '70px',
      'sortable' => true,
      'filterable' => true,
    ],
    'platformTitle' =>
    [
      'id' => 'appliance',
      'name' => 'Оборудование',
      'width' => 20,
      'sortable' => true,
      'filterable' => true,
    ],
    'softwareAndVersion' =>
    [
      'id' => 'soft',
      'name' => 'ПО',
      'width' => 15,
      'sortable' => true,
      'filterable' => true,
    ],
    'moduleInfo' =>
    [
      'id' => 'module',
      'name' => 'Модуль',
      'width' => 10,
      'sortable' => true,
      'filterable' => false,
    ],
    'portInfo' =>
    [
      'id' => 'dport',
      'name' => 'Интерфейсы',
      'width' => 15,
      'sortable' => true,
      'filterable' => false,
    ],
    'action' =>
    [
      'id' => 'action',
      'name' => 'Действия',
      'width' => '105px',
      'sortable' => false,
      'filterable' => false,
    ],
  ],
  'extraColumns' =>
  [
    0 => 'action',
  ],
  'sortOrderSets' =>
  [
    'region' =>
    [
      'region' => '',
      'city' => '',
      'appSortOrder' => 'desc',
    ],
    'city' =>
    [
      'city' => '',
      'office' => '',
      'appSortOrder' => 'desc',
    ],
  ],
  'sortBy' =>
  [
    'region' => '',
    'city' => '',
    'appSortOrder' => 'desc',
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