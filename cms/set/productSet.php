<?php
$menu_is = "product";
$category = "productCate";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '商品',
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
        'hasCategory' => true,
        'categoryName' => $category,
        'categoryField' => 'd_tag',
        'imageFileType' => 'productCover',
        'hasLanguage' => true,
        'useTaxonomyMapSort' => true,
        'columns' => [
            ['field' => 'd_sort', 'label' => '排序', 'type' => 'sort', 'width' => '74'],
            ['field' => 'pin', 'label' => '置頂', 'type' => 'button', 'width' => '50'],
            ['field' => 'd_date', 'label' => '日期', 'type' => 'date', 'width' => '142'],
            // ['field' => 't_name', 'label' => '分類', 'type' => 'text', 'width' => '120'],
            ['field' => 'd_title', 'label' => '標題', 'type' => 'text', 'width' => '350'],
            ['field' => 'd_view', 'label' => '瀏覽次數', 'type' => 'text', 'width' => '60'],
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
                    'type' => 'select',
                    'field' => 'd_tag',
                    'label' => '分類',
                    'required' => true,
                    'category' => $category,
                    'multiple' => true,
                    'useChosen' => true,
                ],
                [
                    'type' => 'text',
                    'field' => 'd_title',
                    'label' => '標題',
                    'required' => true,
                ],
                [
                    'type' => 'datetime',
                    'field' => 'd_date',
                    'label' => '日期',
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
                    'fileType' => 'productCover',
                    'multiple' => false,
                    'size' => [
                        ['w' => 798, 'h' => 636]
                    ],
                    'note' => '* 圖片請上傳寬 798pixel、高 636pixel之圖檔。'
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
