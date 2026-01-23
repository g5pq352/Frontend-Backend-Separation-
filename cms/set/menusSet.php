<?php
/**
 * CMS Menu Management Configuration (Hierarchical)
 * CMS 選單管理配置（階層式）
 */

$menu_is = "menus";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '選單管理',
    'tableName' => 'menus_set',
    'primaryKey' => 'm_id',
    'languageEnabled' => true,  // 啟用多語系
    'menuKey' => null,
    'menuValue' => null,

    'cols' => [
        'title' => 'm_title_ch',
        'sort' => 'm_sort',
        'active' => 'm_active',
        'delete_time' => null,
        'top' => null,
        'file_fk' => null,
        'parent_id' => 'm_parent_id'  // 階層導航用
    ],
    
    'listPage' => [
        'title' => '選單列表',
        'itemsPerPage' => 9999999,
        'hasCategory' => false,
        'hasHierarchy' => true,  // 開啟階層導航功能
        'hasLanguage' => true,   // 啟用語系切換
        'columns' => [
            ['field' => 'm_sort', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            ['field' => 'm_title_ch', 'label' => '標題', 'type' => 'text', 'width' => '300'],
            ['field' => 'm_link', 'label' => '連結', 'type' => 'text', 'width' => '200'],
            ['field' => 'm_active', 'label' => '狀態', 'type' => 'active', 'width' => '60'],
            ['field' => 'next_level', 'label' => '下一層', 'type' => 'button', 'width' => '60'],
            ['field' => 'edit', 'label' => '編輯', 'type' => 'button', 'width' => '30'],
            ['field' => 'delete', 'label' => '刪除', 'type' => 'button', 'width' => '30']
        ],
        'orderBy' => 'm_sort ASC'
    ],
    
    'detailPage' => [
        [
            'sheetTitle' => '基本設定',
            'boxTitle' => '選單資訊',
            'items' => [
                [
                    'type' => 'select',
                    'field' => 'm_parent_id',
                    'label' => '父選單',
                    'required' => false,
                    'category' => 'menus',
                    'useChosen' => true,
                    'note' => '選擇「頂層選單」表示這是主選單，選擇其他項目表示這是子選單'
                ],
                [
                    'type' => 'text',
                    'field' => 'm_title_ch',
                    'label' => '中文標題',
                    'required' => true,
                    'size' => 80,
                ],
                [
                    'type' => 'text',
                    'field' => 'm_title_en',
                    'label' => '英文標題',
                    'required' => false,
                    'size' => 80,
                ],
                [
                    'type' => 'text',
                    'field' => 'm_link',
                    'label' => '連結',
                    'required' => false,
                    'size' => 80,
                ],
                [
                    'type' => 'select',
                    'field' => 'm_target',
                    'label' => '連結類型',
                    'options' => [
                        ['value' => 0, 'label' => '無'],
                        ['value' => 1, 'label' => '新視窗'],
                        ['value' => 2, 'label' => '同視窗']
                    ]
                ],
                [
                    'type' => 'number',
                    'field' => 'm_sort',
                    'label' => '排序',
                    'size' => 10,
                ],
                [
                    'type' => 'select',
                    'field' => 'm_active',
                    'label' => '在網頁顯示',
                    'options' => [
                        ['value' => 1, 'label' => '顯示'],
                        ['value' => 0, 'label' => '不顯示']
                    ]
                ]
            ]
        ]
    ],
    
    'hiddenFields' => [],
    
    'fileUpload' => [
        'enabled' => false
    ]
];

return $settingPage;
?>
