<?php
$menu_is = "historyCate";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '歷史分類',
    'tableName' => 'taxonomies',
    'primaryKey' => 't_id',
    'menuKey' => 'taxonomy_type_id',

    'cols' => [
        'date'  => 'created_at',
        'title' => 't_name',
        'slug'  => 't_slug',
        'slug_source' => 't_name_en',
        'sort' => 'sort_order',
        'active' => 't_active',
        'delete_time' => 'deleted_at', 
        'parent_id' => 'parent_id',
        'top' => null,
        'file_fk' => 'file_t_id' // 【修正】taxonomies 表使用 file_t_id
    ],
    
    'listPage' => [
        'title' => '分類列表',
        'itemsPerPage' => 9999999,
        'hasCategory' => false,
        'hasHierarchy' => false,
        'columns' => [
            ['field' => 'sort_order', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            ['field' => 'created_at', 'label' => '建立日期', 'type' => 'date', 'width' => '142'],
            ['field' => 't_name', 'label' => '分類名稱', 'type' => 'text', 'width' => '400'],
            // ['field' => 'next_level', 'label' => '下一層', 'type' => 'button', 'width' => '60'],
            ['field' => 't_active', 'label' => '狀態', 'type' => 'active', 'width' => '60'],
            ['field' => 'edit', 'label' => '編輯', 'type' => 'button', 'width' => '30'],
            ['field' => 'delete', 'label' => '刪除', 'type' => 'button', 'width' => '30']
        ],
        'orderBy' => 'sort_order ASC, created_at DESC'
    ],
    
    'detailPage' => [
        [
            'sheetTitle' => '資料設定',
            'boxTitle' => '基本資訊',
            'items' => [
                // [
                //     'type' => 'select',
                //     'field' => 'parent_id',
                //     'label' => '父層分類',
                //     'required' => false,
                //     'category' => 'historyCate',
                //     'useChosen' => true,
                //     'note' => '選擇「頂層」或所屬的父層分類'
                // ],
                [
                    'type' => 'text',
                    'field' => 't_name',
                    'label' => '分類名稱 (中文)',
                    'required' => true,
                    'size' => 80
                ],
                [
                    'type' => 'text',
                    'field' => 't_name_en',
                    'label' => '分類名稱 (英文)',
                    'required' => false,
                    'size' => 80
                ],
                [
                    'type' => 'datetime',
                    'field' => 'created_at',
                    'label' => '建立日期',
                    'required' => true
                ],
                [
                    'type' => 'number',
                    'field' => 'sort_order',
                    'label' => '排序 (數字越小越前面)',
                    'size' => 10
                ],
                [
                    'type' => 'select',
                    'field' => 't_active',
                    'label' => '顯示狀態',
                    'options' => [
                        ['value' => 1, 'label' => '顯示'],
                        ['value' => 0, 'label' => '不顯示']
                    ]
                ],
            ]
        ],
        [
            'sheetTitle' => 'SEO設定',
            'boxTitle' => '搜尋引擎優化',
            'items' => [
                [
                    'type' => 'text',
                    'field' => 't_slug',
                    'label' => '網址別名 (slug)',
                    'size' => 80,
                    'note' => '用於網址列，留空則自動從標題產生'
                ],
                [
                    'type' => 'text',
                    'field' => 't_seo_title',
                    'label' => 'SEO 標題 (Meta Title)',
                    'size' => 80,
                    'note' => '建議長度：50-60 字元'
                ],
                [
                    'type' => 'textarea',
                    'field' => 't_description',
                    'label' => 'SEO 描述 (Meta Description)',
                    'rows' => 4,
                    'cols' => 80,
                    'note' => '建議長度：150-160 字元'
                ],
            ]
        ]
    ],
    
    'hiddenFields' => [
        'taxonomy_type_id' => null
    ]
];

return $settingPage;
?>
