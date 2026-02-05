<?php
$hasHierarchy = true; // 開放多階層分類 (使用陣列欄位)

$menu_is = "client";
$category = "hostCate";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '客戶管理',
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
        'title'         => '列表',
        'imageFileType' => 'productCover',
        'categoryName'  => $category,
        'categoryField' => $hasHierarchy ? ['d_class2', 'd_class3', 'd_class4', 'd_class5'] : 'd_class2',
        // ----------------------------------------------------------
        'hasCategory' => true, // 列表是否顯示Filter By
        'useTaxonomyMapSort' => true, // 資料與分類關聯對照表
        'globalSort' => true, // 啟用全域排序
        // ----------------------------------------------------------
        'columns' => [
            ['field' => 'd_sort', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            ['field' => 'pin', 'label' => '置頂', 'type' => 'button', 'width' => '50'],
            ['field' => 'd_date', 'label' => '日期', 'type' => 'date', 'width' => '142'],
            ['field' => 'd_title', 'label' => '標題', 'type' => 'text', 'width' => '470'],
            ['field' => 'd_view', 'label' => '瀏覽次數', 'type' => 'view_count', 'width' => '60'],
            ['field' => 'image', 'label' => '圖片', 'type' => 'image', 'width' => '140'],
            ['field' => 'd_active', 'label' => '狀態', 'type' => 'active', 'width' => '60'],
            ['field' => 'edit', 'label' => '編輯', 'type' => 'button', 'width' => '30'],
            ['field' => 'delete', 'label' => '刪除', 'type' => 'button', 'width' => '30'],
            
            // 【標準】標準複選分類顯示方式 (從資料庫抓取名稱)
            // ['field' => 'd_tag', 'label' => '自定義複選', 'type' => 'select', 'width' => '120',
            //     'options' => [
            //         ['value' => 1, 'label' => '汪汪'],
            //         ['value' => 5, 'label' => '喵喵'],
            //         ['value' => 8, 'label' => '嗚嗚']
            //     ]
            // ],
            // ['field' => 'd_tag', 'label' => '系統分類', 'type' => 'category', 'category' => $category, 'width' => '150'],
            ['field' => 'd_class2', 'label' => '分類路徑', 'type' => 'category_path', 'category' => $category, 'width' => '134'],
        ],
        'itemsPerPage'  => 9999999,
        'orderBy' => 'd_sort ASC, d_date DESC',
        'customQuery' => "SELECT data_set.*, taxonomies.t_name FROM data_set
                          LEFT JOIN taxonomies ON data_set.d_class2 = taxonomies.t_id
                          AND data_set.lang = taxonomies.lang
                          AND (taxonomies.deleted_at IS NULL)",
    ],
    
    'detailPage' => [
        [
            'sheetTitle' => '資料設定',
            'boxTitle' => '',
            'items' => [
                [
                    'type' => 'select',
                    'field' => $hasHierarchy ? ['d_class2', 'd_class3', 'd_class4', 'd_class5'] : 'd_class2',
                    'label' => $hasHierarchy ? '主機在哪裡' : '分類',
                    'category' => $category,
                    'linked' => $hasHierarchy,
                ],
                [
                    'type' => 'text',
                    'field' => 'd_title',
                    'label' => '標題',
                    'required' => true,
                ],
                [
                    'type' => 'date',
                    'field' => 'd_data1',
                    'label' => '上線日期',
                    'required' => false,
                ],
                [
                    'type' => 'text',
                    'field' => 'd_data2',
                    'label' => '前台網址',
                    'required' => false,
                ],
                [
                    'type' => 'editor',
                    'field' => 'd_data3',
                    'label' => '主機資料 (cPanel、網域等)',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'text',
                    'field' => 'd_data4',
                    'label' => '後台網址',
                    'required' => false,
                ],
                [
                    'type' => 'textarea',
                    'field' => 'd_data5',
                    'label' => '後台帳密',
                    'required' => false,
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
                    'note' => ''
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
