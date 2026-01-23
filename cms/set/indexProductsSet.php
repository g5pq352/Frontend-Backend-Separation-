<?php
$menu_is = "indexProducts";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '商品管理',
    'tableName' => 'data_set',
    'primaryKey' => 'd_id',
    'menuKey' => 'd_class1',
    'menuValue' => $menu_is,

    'cols' => [
        'date'  => 'd_date',
        'title' => 'd_title',
        'slug'  => 'd_slug',
        'slug_source' => 'd_title',
        'sort' => 'd_sort',
        'top' => 'd_top',
        'active' => 'd_active',
        'delete_time' => 'd_delete_time',
        'file_fk' => 'file_d_id'
    ],
    
    'listPage' => [
        'title' => '列表',
        'itemsPerPage' => 9999999,
        'hasCategory' => false,
        'imageFileType' => 'indexProductsCover',
        'columns' => [
            ['field' => 'd_sort', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            ['field' => 'pin', 'label' => '置頂', 'type' => 'button', 'width' => '50'],
            ['field' => 'd_date', 'label' => '日期', 'type' => 'date', 'width' => '142'],
            ['field' => 'd_title', 'label' => '標題', 'type' => 'text', 'width' => '470'],
            ['field' => 'image', 'label' => '圖片', 'type' => 'image', 'width' => '140'],
            ['field' => 'd_active', 'label' => '狀態', 'type' => 'active', 'width' => '60'],
            ['field' => 'edit', 'label' => '編輯', 'type' => 'button', 'width' => '30'],
            ['field' => 'delete', 'label' => '刪除', 'type' => 'button', 'width' => '30']
        ],
        'orderBy' => 'd_sort ASC, d_date DESC'
    ],
    
    'detailPage' => [
        [
            'sheetTitle' => '資料設定',
            'boxTitle' => '',
            'items' => [
                [
                    'type' => 'text',
                    'field' => 'd_title',
                    'label' => '標題',
                    'required' => true,
                    'size' => 80
                ],
                [
                    'type' => 'datetime',
                    'field' => 'd_date',
                    'label' => '日期',
                    'size' => 50
                ],
                [
                    'type' => 'select',
                    'field' => 'd_active',
                    'label' => '在網頁顯示',
                    'options' => [
                        ['value' => 1, 'label' => '顯示'],
                        ['value' => 0, 'label' => '不顯示']
                    ]
                ],
                [
                    'type' => 'image_upload',
                    'field' => 'imageCover',
                    'label' => '上傳圖片',
                    'fileType' => 'indexProductsCover',
                    'multiple' => false,
                    'dynamic' => true,
                    'size' => [
                        ['w' => 384, 'h' => 452]
                    ],
                    'note' => '* 建議尺寸：384x452px'
                ]
            ]
        ],
    ],
    
    'hiddenFields' => [
        'd_class1' => $menu_is
    ],
    
    'fileUpload' => [
        'enabled' => false
    ]
];

return $settingPage;
?>
