<?php
$menu_is = "news";
$category = "newsCate";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '最新消息管理',
    'tableName' => 'data_set',
    'primaryKey' => 'd_id',
    'menuKey' => 'd_class1',
    'menuValue' => $menu_is,

    'cols' => [
        'date'  => 'd_date',
        'title' => 'd_title',
        'slug'  => 'd_slug',
        'slug_source' => 'd_title',
        'view_count' => 'd_view',
        'sort' => 'd_sort',
        'top' => 'd_top',
        'active' => 'd_active',
        'delete_time' => 'd_delete_time',
        'file_fk' => 'file_d_id'
    ],
    
    'listPage' => [
        'title' => '列表',
        'itemsPerPage' => 9999999,
        'hasCategory' => true,
        'categoryName' => $category,
        'categoryField' => 'd_class2',
        'useTaxonomyMapSort' => false,
        // 'categoryField' => 'd_tag',
        // 'useTaxonomyMapSort' => true,
        'imageFileType' => 'newsCover',
        'customQuery' => "SELECT data_set.*, taxonomies.t_name 
                          FROM data_set 
                          LEFT JOIN taxonomies ON data_set.d_class2 = taxonomies.t_id AND data_set.lang = taxonomies.lang",
        'columns' => [
            ['field' => 'd_sort', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            ['field' => 'pin', 'label' => '置頂', 'type' => 'button', 'width' => '50'],
            ['field' => 'd_date', 'label' => '日期', 'type' => 'date', 'width' => '142'],
            ['field' => 'd_title', 'label' => '標題', 'type' => 'text', 'width' => '470'],
            ['field' => 'd_view', 'label' => '瀏覽次數', 'type' => 'view_count', 'width' => '60'],
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
                // [
                //     'type' => 'select',
                //     'field' => 'd_tag',
                //     'label' => '分類',
                //     'required' => true,
                //     'category' => $category,
                //     'multiple' => true,
                //     'useChosen' => true,
                // ],
                [
                    'type' => 'select',
                    'field' => 'd_class2',
                    'label' => '分類',
                    'required' => true,
                    'category' => $category,
                    'useChosen' => true
                ],
                [
                    'type' => 'text',
                    'field' => 'd_title',
                    'label' => '標題',
                    'required' => true,
                    'size' => 80
                ],
                [
                    'type' => 'text',
                    'field' => 'd_data3',
                    'label' => '作者欄位',
                    'size' => 80
                ],
                [
                    'type' => 'textarea',
                    'field' => 'd_data1',
                    'label' => '副標題',
                    'rows' => 6,
                    'cols' => 80
                ],
                [
                    'type' => 'textarea',
                    'field' => 'd_data2',
                    'label' => '摘要',
                    'rows' => 6,
                    'cols' => 80
                ],
                [
                    'type' => 'editor',
                    'field' => 'd_content',
                    'label' => '內容',
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,  
                    'hasGallery' => true,
                    'note' => '*小斷行請按Shift+Enter。<br />輸入區域的右下角可以調整輸入空間的大小。'
                ],
                [
                    'type' => 'date',
                    'field' => 'd_decade',
                    'label' => '上版日期',
                    'size' => 50
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
                        ['value' => 2, 'label' => '草稿'],
                        ['value' => 1, 'label' => '顯示'],
                        ['value' => 0, 'label' => '不顯示']
                    ]
                ],
                [
                    'type' => 'image_upload',
                    'field' => 'imageCover',
                    'label' => '上傳封面圖片',
                    'fileType' => 'newsCover',
                    'multiple' => false,
                    'dynamic' => true,
                    'size' => [
                        ['w' => 1030, 'h' => 570]
                    ],
                    'note' => '* 建議尺寸：1030x570px'
                ],
                [
                    'type' => 'image_upload',
                    'field' => 'image',
                    'label' => '上傳圖片',
                    'fileType' => 'image',
                    'multiple' => true,
                    'size' => [
                        ['w' => 1030, 'h' => 570]
                    ],
                    'note' => '* 建議尺寸：1030x570px'
                ]
            ]
        ],
        [
            'sheetTitle' => 'SEO設定',
            'boxTitle' => '',
            'items' => [
                [
                    'type' => 'text',
                    'field' => 'd_slug',
                    'label' => '網址別名 (slug)',
                    'size' => 80,
                    'note' => '留空則自動從標題產生'
                ],
                [
                    'type' => 'text',
                    'field' => 'd_seo_title',
                    'label' => 'SEO 標題',
                    'size' => 80,
                    'note' => '建議長度：50-60 字元'
                ],
                [
                    'type' => 'textarea',
                    'field' => 'd_description',
                    'label' => 'SEO 描述 (meta description)',
                    'rows' => 4,
                    'cols' => 80,
                    'note' => '建議長度：150-160 字元'
                ],
            ]
        ]
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
