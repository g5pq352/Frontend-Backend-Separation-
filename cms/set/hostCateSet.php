<?php
$menu_is = "hostCate";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '主機分類',
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
        'hasHierarchy' => true,
        'columns' => [
            ['field' => 'sort_order', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            ['field' => 'created_at', 'label' => '建立日期', 'type' => 'date', 'width' => '142'],
            ['field' => 't_name', 'label' => '分類名稱', 'type' => 'text', 'width' => '400'],
            ['field' => 'next_level', 'label' => '下一層', 'type' => 'button', 'width' => '60'],
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
                [
                    'type' => 'select',
                    'field' => 'parent_id',
                    'label' => '父層分類',
                    'required' => false,
                    'category' => 'hostC',
                    'useChosen' => true,
                    'note' => '選擇「頂層」或所屬的父層分類'
                ],
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
                    'type' => 'text',
                    'field' => 't_ip',
                    'label' => 'A 紀錄IP',
                    'required' => false,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_content',
                    'label' => '內容',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data1',
                    'label' => '網域哪買',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data2',
                    'label' => 'DNS伺服器(Name Server)',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data3',
                    'label' => 'Cpanel登入位置',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data4',
                    'label' => 'WHM登入帳號',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data5',
                    'label' => '備註1',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data6',
                    'label' => '備註2',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data7',
                    'label' => '備註3',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
                ],
                [
                    'type' => 'editor',
                    'field' => 't_data8',
                    'label' => '備註4',
                    'required' => false,
                    'rows' => 6,
                    'cols' => 80,
                    'useTiny' => true,
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
    ],
    
    'hiddenFields' => [
        'taxonomy_type_id' => null
    ]
];

return $settingPage;
?>
