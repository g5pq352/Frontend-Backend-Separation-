<?php
$menu_is = "popInfo";
$settingPage = [
    'pageType' => 'info',
    'module' => $menu_is,
    'moduleName' => '燈箱設定',
    'tableName' => 'data_set',
    'primaryKey' => 'd_id',
    'menuKey' => 'd_class1',
    'menuValue' => $menu_is,

    'cols' => [
        'title' => 'd_title',
        'active' => 'd_active',
        'file_fk' => 'file_d_id'
    ],
    
    'detailPage' => [
        [
            'sheetTitle' => '資料設定',
            'boxTitle' => '',
            'items' => [
                [
                    'type' => 'datetime',
                    'field' => 'd_date',
                    'label' => '日期',
                    'size' => 50,
                    'default' => 'now'
                ],
                [
                    'type' => 'select',
                    'field' => 'd_active',
                    'label' => '在網頁顯示',
                    'options' => [
                        ['value' => 1, 'label' => '顯示'],
                        ['value' => 0, 'label' => '不顯示']
                    ],
                    'default' => 1
                ],
                [
                    'type' => 'image_upload',
                    'field' => 'imageCover',
                    'label' => '上傳封面圖片',
                    'fileType' => 'popInfoCover',
                    'multiple' => false,
                    'size' => [
                        ['w' => 1920, 'h' => 1080]
                    ],
                    'note' => '* 建議尺寸：1920x1080px'
                ],
            ]
        ],
        // [
        //     'sheetTitle' => 'SEO設定',
        //     'boxTitle' => '',
        //     'items' => [
        //         [
        //             'type' => 'text',
        //             'field' => 'd_slug',
        //             'label' => '網址別名 (slug)',
        //             'size' => 80,
        //             'note' => '留空則自動從標題產生'
        //         ],
        //         [
        //             'type' => 'text',
        //             'field' => 'd_seo_title',
        //             'label' => 'SEO 標題',
        //             'size' => 80,
        //             'note' => '建議長度：50-60 字元'
        //         ],
        //         [
        //             'type' => 'textarea',
        //             'field' => 'd_description',
        //             'label' => 'SEO 描述 (meta description)',
        //             'rows' => 4,
        //             'cols' => 80,
        //             'note' => '建議長度：150-160 字元'
        //         ],
        //     ]
        // ]
    ],
    
    'hiddenFields' => [
        'd_class1' => $menu_is,
    ],
    
    'fileUpload' => [
        'enabled' => false
    ]
];

return $settingPage;
?>
