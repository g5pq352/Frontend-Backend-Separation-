<?php
$menu_is = "portfolio";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '作品案例管理',
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
        'imageFileType' => 'portfolioCover',
        'showAddButton' => true,
        'columns' => [
            ['field' => 'd_sort', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            // ['field' => 'pin', 'label' => '置頂', 'type' => 'button', 'width' => '50'],
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
                    'checkDuplicate' => true,
                    'size' => 80
                ],
                [
                    'type' => 'select',
                    'field' => 'd_class6',
                    'label' => '作者',
                    'category' => "authorC",
                    'multiple' => true,
                    'canCreate' => true,
                ],
                [
                    'type' => 'select',
                    'field' => 'd_class7',
                    'label' => '專案',
                    'category' => "projectC",
                    'multiple' => true,
                    'canCreate' => true,
                ],
                [
                    'type' => 'select',
                    'field' => 'd_class2',
                    'label' => '類型',
                    'category' => "typeC",
                    'multiple' => true,
                    'canCreate' => true,
                ],
                [
                    'type' => 'select',
                    'field' => 'd_class3',
                    'label' => '分類',
                    'category' => "categoryC",
                    'multiple' => true,
                    'canCreate' => true,
                ],
                [
                    'type' => 'select',
                    'field' => 'd_class4',
                    'label' => '顏色',
                    'category' => "colorC",
                    'multiple' => true,
                    'canCreate' => true,
                ],
                [
                    'type' => 'select',
                    'field' => 'd_class5',
                    'label' => '標籤',
                    'category' => "tagC",
                    'multiple' => true,
                    'canCreate' => true,
                ],
                [
                    'type' => 'text',
                    'field' => 'd_data1',
                    'label' => '連結',
                    'required' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 'd_content',
                    'label' => '內容',
                    'useTiny' => true,  
                    'hasGallery' => true,
                    'note' => '*小斷行請按Shift+Enter。<br />輸入區域的右下角可以調整輸入空間的大小。'
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
                    'label' => '上傳封面圖片',
                    'fileType' => 'portfolioCover',
                    'multiple' => false,
                    'size' => [
                        ['w' => 1920, 'h' => 1080],
                        // 'maxSize' => 3
                    ],
                    'note' => ''
                ],
                
                // [
                //     'type' => 'image_upload',
                //     'field' => 'image',
                //     'label' => '上傳圖片',
                //     'fileType' => 'image',
                //     'multiple' => true,
                //     'size' => [
                //         ['w' => 0, 'h' => 0]
                //     ],
                //     // 'note' => '* 建議尺寸：384x452px'
                // ]
            ]
        ],
        [
            'sheetTitle' => 'SEO設定',
            'boxTitle' => '',
            'items' => [
                [
                    'type' => 'image_upload',
                    'field' => 'imageOg',
                    'label' => '上傳og圖片',
                    'fileType' => 'portfolioOg',
                    'multiple' => false,
                    'size' => [
                        ['w' => 1200, 'h' => 630]
                    ],
                    'note' => ''
                ],
            ]
        ]
    ],
    
    'hiddenFields' => [
        'd_class1' => $menu_is
    ]
];

return $settingPage;
?>
