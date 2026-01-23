CKEDITOR.editorConfig = function( config ) {
    config.toolbar = [
        { name: 'links', items: ['Link', 'Unlink'] },
        { name: 'insert', items: ['Image'] }, // 加入圖片按鈕
        { name: 'colors', items: ['TextColor', 'BGColor'] }
    ];

    config.removePlugins = 'table,flash,iframe';

    config.language = 'zh';
};