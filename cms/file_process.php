<?php
require_once '../Connections/connect2data.php';

function file_process(PDO $pdo, $file_name, $deal_type) {
    //echo count($_FILES['upfile']['name']);//上傳物件數量
    //echo count($_REQUEST[upfile_title]);//上傳物件的說明之數量
    //echo $_FILES['upfile']['tmp_name'][0];

    $all_file_name = array(); //建立回傳的資料陣列

    // 允許上傳的 MIME 類型（比副檔名更安全）
    $allowed_mimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf'
    ];

    // 白名單（可以自訂）
    // $allowed_mimes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

    //******產生相對應的資料夾begin*******//
    // $file_path = "upload_file";
    $upload_dir = __DIR__ . '/../upload_file/';
    check_path2($upload_dir); //如果沒有資料夾，產生資料夾

    $file_path = "{$upload_dir}{$file_name}/";
    check_path2($file_path); //如果沒有資料夾，產生資料夾

    //******產生相對應的資料夾end*******//

    //******如果是插入記錄的上傳檔案begin*******/
    if ($deal_type == "add") {

        // pdo 已經是不同的了所以 lastInsertId 不能用只好用這樣
        $sql_max_pic = "SELECT MAX(file_id) FROM file_set";
        $sth = $pdo->query($sql_max_pic)->fetch();
        $new_file_num = $sth[0] + 1;

    }
    //******如果是插入記錄的上傳檔案end*******/

    //******如果是更新記錄的上傳檔案begin*******/
    if ($deal_type == "edit") {

        $new_file_num = $_POST['file_id'];

        //echo $new_file_num;

    }
    //******如果是更新記錄的上傳檔案end*******/

    for ($j = 0; $j < count($_FILES['upfile']['name']); $j++) {
        // 跳過未上傳的欄位
        if ($_FILES['upfile']['error'][$j] !== UPLOAD_ERR_OK) {
            continue;
        }
        
        $tmp_name = $_FILES['upfile']['name'][$j];
        // ⭐ 取得暫存檔案
        $tmp_file = $_FILES['upfile']['tmp_name'][$j];

        // if ($tmp_name != '') //如果有上傳檔案
        if ($tmp_file != '') //如果有上傳檔案
        {

            // $file_type = end(explode(".", $_FILES['upfile']['name'][$j])); //將檔案已"."分開，放到陣,列呼叫array最後一個資料,為檔案副檔名

            $file_type = strtolower(pathinfo($_FILES['upfile']['name'][$j], PATHINFO_EXTENSION)); //將檔案已"."分開，放到陣,列呼叫array最後一個資料,為檔案副檔名

            // ---------------------------------------------------------
            // ⭐ (1) 使用 finfo 確認 MIME 類型
            // ---------------------------------------------------------
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp_file);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_mimes)) {
                echo "不允許的檔案類型：{$mime}";
                // continue;
                exit;
            }

            // ---------------------------------------------------------
            // ⭐ (2) 產生安全檔名（防止覆寫）
            // ---------------------------------------------------------
            $ext_map = [
                'image/jpeg' => '.jpg',
                'image/png'  => '.png',
                'image/gif'  => '.gif',
                'image/webp' => '.webp',
                'application/pdf' => '.pdf'
            ];

            $ext = $ext_map[$mime];  // 依 MIME 決定副檔名
            $safe_name = $file_name . "_" . bin2hex(random_bytes(16)) . $ext; // 產生唯一檔名

            // $destination = $upload_dir . $file_name . $safe_name;
            $destination = $file_path . $safe_name;

            // echo "destination => {$destination}<br>";

            /* // 取得檔名
            $filename = $_FILES['upfile']['name'][$j];

            // 分解副檔名（修正 Only variables error）
            $parts = explode(".", $filename);
            $file_type = strtolower(end($parts)); */

            // ---------------------------------------------------------
            // ⭐ (3) 移動檔案
            // ---------------------------------------------------------
            if (!move_uploaded_file($tmp_file, $destination)) {
                echo "檔案移動失敗";
                continue;
            }


            // $d_id = intval($_POST['d_id'] ?? 0);

            $file_link  = "upload_file/" . "{$file_name}/" . $safe_name;
            $file_name  = $safe_name;
            
            $file_type  = "file";       // 固定字串
            $file_d_id  = intval($_POST['d_id'] ?? 0);
            $file_title = $_POST['upfile_title'][$j] ?? null;

            $all_file_name[$j][0] = $file_name; //儲存檔案名
            $all_file_name[$j][1] = $file_link; //檔案位置
            $all_file_name[$j][2] = $file_title; //檔案title(說明)
            

            /* // 檢查安全性
            if (!in_array($file_type, $allowed_mimes)) {
                die("無效檔案類型。");
            } */

            /* 
            $upfile_name = $new_file_num + $j; //將新id轉成檔案名，已上傳的數量來增加
            //echo "upfile_name = ".$upfile_name."<br>";

            //echo  $_FILES['upfile']['name'][$j]."<br>";
            // $file_type = end(explode(".", $_FILES['upfile']['name'][$j])); //將檔案已"."分開，放到陣,列呼叫array最後一個資料,為檔案副檔名
            //echo $file_type."<br>";//

            $_FILES['upfile']['name'][$j] = str_replace(" ", "", $_FILES['upfile']['name'][$j]); //將檔案名內有空白的除掉

            $size = getimagesize($_FILES['upfile']['tmp_name'][$j]);
            //echo "filename = ".$_FILES['upfile']['tmp_name'][$j]."<br>";
            //echo "width = ".$size[0]."<br>";//寬
            //echo "height = ".$size[1]."<br>";//長
            //echo "width + height = ".$size[3]."<br>";//長
            $orginal_width = $size[0]; //寬
            $orginal_height = $size[1]; //長

            //$file_name= md5($file_name);

            $this_path = $file_path . "/" . $file_name . "_" . $upfile_name . "." . $file_type;

            $this_path = mb_convert_encoding($this_path, "BIG5", "UTF-8"); //如果檔案是中文名，要轉成big5才能存成真實的檔
            //echo $this_path."<br>";
            //echo $_FILES['upfile']['tmp_name'][$j];

            copy($_FILES['upfile']['tmp_name'][$j], "../" . $this_path);
            $this_path = mb_convert_encoding($this_path, "UTF-8", "BIG5"); //如果檔案是中文名，要轉成utf-8才能放在資料庫
            //echo $this_path;

            $db_file_name = $file_name . "_" . $upfile_name . "." . $file_type; //儲存到資料庫的檔案名稱
            $all_file_name[$j][0] = $db_file_name; //儲存檔案名
            $all_file_name[$j][1] = $this_path; //檔案位置
            $all_file_name[$j][2] = (isset($_REQUEST['upfile_title'][$j])) ? $_REQUEST['upfile_title'][$j] : ''; //檔案title(說明)
            // $all_file_name[$j][2] = (isset($file_title[$j])) ? $file_title[$j] : ''; //檔案title(說明)
            $all_file_name[$j][3] = $orginal_width;
            $all_file_name[$j][4] = $orginal_height;
 */
        } else //沒上傳檔案
        {
            //echo "沒上傳檔案";

        }

    }

    //print_r($all_file_name);//列出陣列
    return $all_file_name;
}

function check_path2($file_path) {

    /* if (!is_dir("../" . $file_path)) //如果沒有資料夾
    {
        mkdir("../" . $file_path); //產生資料夾
    } else {
        //dont do thing
    } */
    if (!is_dir($file_path)) //如果沒有資料夾
    {
        mkdir($file_path, 0755, true);
    } else {
        //dont do thing
    }
}

?>

