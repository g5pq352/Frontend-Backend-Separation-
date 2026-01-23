/* Add here all your JS customizations */
/*
* Form Image - Dropzone Initialization
*/
var initializeDropzone = function() {       
    var $dropzoneEl = $('#dropzone-form-image');

    // 檢查元素是否存在，或者是否已經初始化過 (避免重複綁定)
    if( !$dropzoneEl.length || $dropzoneEl.hasClass('initialized') ) {
        return;
    }

    // 1. 從 HTML data 屬性取得 PHP 傳來的參數
    var dId = $dropzoneEl.data('d-id');
    var fileType = $dropzoneEl.data('file-type');

    console.log("初始化 Dropzone - ID:", dId, "Type:", fileType);

    // 2. 初始化 Dropzone
    $dropzoneEl.dropzone({
        url: 'upload_dropzone.php', // 對應你的後端檔案
        paramName: "file",          // 對應 PHP $_FILES['file']
        maxFilesize: 512,           // 單位 MB
        acceptedFiles: 'image/*',   // 限制檔案類型
        addRemoveLinks: true,       // 顯示刪除連結
        dictDefaultMessage: "",     // 隱藏預設文字 (因為你有自定義 span)
        
        // 3. 關鍵：在發送前將 ID 和 Type 附加到 formData
        sending: function(file, xhr, formData) {
            formData.append("d_id", dId);
            formData.append("file_type", fileType);
        },

        // 4. 上傳成功後的回調
        success: function(file, response) {
            // 如果後端回傳的是字串，轉成 JSON 物件
            if (typeof response === "string") {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    console.error("JSON 解析失敗:", e);
                }
            }

            if (response.status === 'success') {
                console.log("上傳成功:", response);
                // 成功樣式
                if (file.previewElement) {
                    file.previewElement.classList.add("dz-success");
                }
            } else {
                console.error("上傳失敗:", response.message);
                alert("上傳失敗: " + response.message);
                this.removeFile(file); // 移除失敗的檔案預覽
            }
        },
        
        // 5. 錯誤處理
        error: function(file, errorMessage) {
            console.error("Dropzone Error:", errorMessage);
            var msg = (typeof errorMessage === 'object' && errorMessage.message) ? errorMessage.message : errorMessage;
            // 避免 alert 太多次，可視情況移除
            // alert("系統錯誤: " + msg); 
        },

        // 6. 初始化設定
        init: function() {
            // 這裡可以處理「編輯模式」下，預先顯示已上傳圖片的邏輯
            // 如果你的 $dropzoneEl 有 dz-filled class，可以在此撈取舊圖片顯示
            if( $dropzoneEl.hasClass('dz-filled') ) {
                // TODO: 載入既有圖片的邏輯
            }
            
            // 當佇列完成時 (例如一次上傳 5 張，全部傳完後)
            this.on("queuecomplete", function() {
                console.log("所有檔案處理完畢");
                // 如果需要刷新頁面或更新列表，可在此執行
                // location.reload();
            });
        }

    }).addClass('dropzone initialized'); // 加上 initialized class 避免重複執行
};

// 頁面載入完成時執行
$(document).ready(function(){
    initializeDropzone();
});

/**
 * 切換已讀/未讀狀態
 * @param {HTMLElement} obj 按鈕元素
 * @param {int} id 資料 ID
 * @param {int} nextState 下一個狀態 (1=已讀, 0=未讀)
 */
function toggleRead(obj, id, nextState) {
    $.ajax({
        url: 'ajax_read.php',
        type: 'POST',
        dataType: 'json',
        data: {
            id: id,
            m_read: nextState
        },
        success: function(response) {
            if (response.status === 'success') {
                // 更新按鈕狀態
                var $btn = $(obj);
                if (nextState == 1) {
                    $btn.css('background-color', '#28a745').text('已讀');
                    $btn.attr('onclick', 'toggleRead(this, ' + id + ', 0)');
                } else {
                    $btn.css('background-color', '#dc3545').text('未讀');
                    $btn.attr('onclick', 'toggleRead(this, ' + id + ', 1)');
                }
            } else {
                alert('更新失敗: ' + (response.message || '未知錯誤'));
            }
        },
        error: function() {
            alert('系統錯誤，請稍後再試');
        }
    });
}