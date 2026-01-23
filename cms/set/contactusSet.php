<?php
$menu_is = "contactus";
$settingPage = [
    'module' => $menu_is,
    'moduleName' => '聯絡我們表單',
    'tableName' => 'message_set',
    'primaryKey' => 'm_id',
    'viewOnly' => true,
    'replyActive' => true,

    'cols' => [
        'date'  => 'm_date',
        'title' => 'm_title',
        'read'  => 'm_read',
        'reply' => 'm_reply',
    ],
    
    'listPage' => [
        'title' => '列表',
        'itemsPerPage' => 9999999,
        'hasCategory' => false,
        'showAddButton' => false,
        'columns' => [
            ['field' => 'm_date', 'label' => '日期', 'type' => 'date', 'width' => '142'],
            ['field' => 'm_title', 'label' => '姓名', 'type' => 'text', 'width' => '470'],
            ['field' => 'read', 'label' => '狀態', 'type' => 'read_toggle', 'width' => '80'],
            ['field' => 'reply', 'label' => '回覆', 'type' => 'reply_status', 'width' => '80'],
            ['field' => 'view', 'label' => '查看', 'type' => 'button', 'width' => '30'],
            ['field' => 'delete', 'label' => '刪除', 'type' => 'button', 'width' => '30']
        ],
        'orderBy' => 'm_date DESC',
    ],
    
    'detailPage' => [
        [
            'sheetTitle' => '資料設定',
            'boxTitle' => '',
            'items' => [
                [
                    'type' => 'text',
                    'field' => 'm_title',
                    'label' => '姓名',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'text',
                    'field' => 'm_type',
                    'label' => '詢問類型',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'text',
                    'field' => 'm_email',
                    'label' => '電子郵件',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'text',
                    'field' => 'm_phone',
                    'label' => '手機',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'text',
                    'field' => 'm_address',
                    'label' => '聯絡地址',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'text',
                    'field' => 'm_data1',
                    'label' => '方便聯絡時間',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'text',
                    'field' => 'm_data2',
                    'label' => '聯繫日期',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'textarea',
                    'field' => 'm_content',
                    'label' => '訊息',
                    'readonly' => 'readonly',
                ],
                [
                    'type' => 'datetime',
                    'field' => 'm_date',
                    'label' => '日期',
                    'readonly' => 'readonly',
                    'size' => 50
                ],
            ]
        ],
    ],
    
    'hiddenFields' => [],
];

return $settingPage;
?>
