<?php
require_once('../Connections/connect2data.php');
require_once 'auth.php';
?>
<?php require_once('photo_process.php'); ?>
<?php require_once('imagesSize.php'); ?>

<?php

// 優先使用 URL 參數，其次使用 REQUEST 參數，最後使用 session
if (isset($_GET['file_type']) && !empty($_GET['file_type'])) {
    $type = $_GET['file_type'];
} elseif (isset($_REQUEST['type']) && !empty($_REQUEST['type'])) {
    $type = $_REQUEST['type'];
} else {
    $type = $_SESSION['nowMenu'] ?? 'blog';
}

// 優先使用 URL 傳遞的尺寸參數
if (isset($_GET['w']) && isset($_GET['h'])) {
    $IWidth = intval($_GET['w']);
    $IHeight = intval($_GET['h']);
    $fileType = $type;
    $not = "* 圖片尺寸限制：{$IWidth}x{$IHeight}px";
} elseif (isset($imagesSize[$type])) {
    // 其次使用 imagesSize 配置
    $fileType = $type;
    $not = $imagesSize[$type]['note'] ?? '';
    $IWidth = $imagesSize[$type]['IW'] ?? 800;
    $IHeight = $imagesSize[$type]['IH'] ?? 600;
} else {
    // 預設值
    $fileType = 'image';
    $not = '';
    $IWidth = 800;
    $IHeight = 600;
}

?>

<?php
if ((isset($_POST["MM_update"])) && ($_POST["MM_update"] == "form1")) {

    //----------插入圖片資料到資料庫begin(須放入插入主資料後)----------
    if (isset($_REQUEST['file_youtube_code'])) {

            $file_title = trim($_POST['file_title'] ?? '');
            $file_youtube_code = trim($_POST['file_youtube_code'] ?? '');
            $file_id = intval($_POST['file_id'] ?? 0);

            $updateSQL = "UPDATE file_set SET file_title=:file_title, file_youtube_code=:file_youtube_code WHERE file_type=:file_type AND file_id=:file_id";

            $sth = $conn->prepare($updateSQL);
            $sth->bindParam(':file_title', $file_title, PDO::PARAM_STR);
            $sth->bindParam(':file_youtube_code', $file_youtube_code, PDO::PARAM_STR);
            $sth->bindParam(':file_type', $fileType, PDO::PARAM_STR);
            $sth->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $sth->execute();

    }else{
        $imgType = $type; // 使用上面檢測到的類型
        
        // 調試：檢查 $_FILES 內容
        error_log("Image Edit Debug - FILES content: " . print_r($_FILES, true));
        
        // 處理 $_FILES['image'] 可能是陣列的情況
        $imageFiles = $_FILES['image'];
        
        // 如果是陣列格式（image[]），需要重新組織
        if (isset($imageFiles['name']) && is_array($imageFiles['name'])) {
            // image[] 格式已經是正確的，直接使用
            error_log("Image Edit Debug - Using array format directly");
        } else {
            // 如果是單一檔案格式，需要包裝成陣列格式
            // image_process 期望 $FILES_A['name'][0], $FILES_A['tmp_name'][0] 等
            $imageFiles = array(
                'name' => array($imageFiles['name']),
                'type' => array($imageFiles['type']),
                'tmp_name' => array($imageFiles['tmp_name']),
                'error' => array($imageFiles['error']),
                'size' => array($imageFiles['size'])
            );
            error_log("Image Edit Debug - Wrapped single file into array format");
        }
        
        error_log("Image Edit Debug - Processed files: " . print_r($imageFiles, true));
        
        // 檢查是否有檔案上傳
        if (empty($imageFiles['name']) || empty($imageFiles['name'][0])) {
            error_log("Image Edit Debug - No file uploaded, skipping image_process");
            $image_result_check = array(array(0)); // 沒有上傳檔案
        } else {
            // image_process 期望 title 是陣列格式
            $titleArray = array($_REQUEST['file_title']);
            $image_result_check = image_process($conn, $imageFiles, $titleArray, $imgType, "edit", $IWidth, $IHeight);
            error_log("Image Edit Debug - image_process result: " . print_r($image_result_check, true));
        }

        // 表示有傳圖
        if ( count($image_result_check) == 2 ) {

            //刪除圖片真實檔案begin----
            $sql = "SELECT * FROM file_set WHERE file_id=:file_id";
            $sth = $conn->prepare($sql);
            $sth->bindParam(':file_id', $_POST['file_id'], PDO::PARAM_INT);
            $sth->execute();

            $existingFile = $sth->fetch();
            if ($existingFile) {
                // 使用實際的 file_type
                $actualFileType = $existingFile['file_type'];
                error_log("Image Edit Debug - Using actual file_type: " . $actualFileType);

                if ((isset($existingFile['file_link1'])) && file_exists("../" . $existingFile['file_link1'])) {
                    unlink("../" . $existingFile['file_link1']); //刪除檔案
                }
                if ((isset($existingFile['file_link2'])) && file_exists("../" . $existingFile['file_link2'])) {
                    unlink("../" . $existingFile['file_link2']); //刪除檔案
                }
                if ((isset($existingFile['file_link3'])) && file_exists("../" . $existingFile['file_link3'])) {
                    unlink("../" . $existingFile['file_link3']); //刪除檔案
                }
                if ((isset($existingFile['file_link4'])) && file_exists("../" . $existingFile['file_link4'])) {
                    unlink("../" . $existingFile['file_link4']); //刪除檔案
                }
                if ((isset($existingFile['file_link5'])) && file_exists("../" . $existingFile['file_link5'])) {
                    unlink("../" . $existingFile['file_link5']); //刪除檔案
                }
            }
            //刪除圖片真實檔案end----


            for ($j = 1; $j < count($image_result_check); $j++) {
                $updateSQL = "UPDATE file_set SET file_title=:file_title, file_content=:file_content, file_name=:file_name, file_link1=:file_link1, file_link2=:file_link2, file_link3=:file_link3, file_link4=:file_link4, file_link5=:file_link5 WHERE file_id=:file_id";

                $sth = $conn->prepare($updateSQL);
                $sth->bindParam(':file_title', $_POST['file_title'], PDO::PARAM_STR);
                $sth->bindParam(':file_content', $_POST['file_content'], PDO::PARAM_STR);
                $sth->bindParam(':file_name', $image_result_check[$j][0], PDO::PARAM_STR);
                $sth->bindParam(':file_link1', $image_result_check[$j][1], PDO::PARAM_STR);
                $sth->bindParam(':file_link2', $image_result_check[$j][2], PDO::PARAM_STR);
                $sth->bindParam(':file_link3', $image_result_check[$j][3], PDO::PARAM_STR);
                $sth->bindParam(':file_link4', $image_result_check[$j][6], PDO::PARAM_STR);
                $sth->bindParam(':file_link5', $image_result_check[$j][8], PDO::PARAM_STR);
                $sth->bindParam(':file_id', $_POST['file_id'], PDO::PARAM_INT);
                $sth->execute();

                error_log("Image Edit Debug - Updated file_id: " . $_POST['file_id'] . " with new image");
                $_SESSION["change_image"] = 1;
            }
        }else{
            $updateSQL = "UPDATE file_set SET file_title=:file_title, file_content=:file_content WHERE file_id=:file_id";

            $sth = $conn->prepare($updateSQL);
            $sth->bindParam(':file_title', $_POST['file_title'], PDO::PARAM_STR);
            $sth->bindParam(':file_content', $_POST['file_content'], PDO::PARAM_STR);
            $sth->bindParam(':file_id', $_POST['file_id'], PDO::PARAM_INT);
            $sth->execute();
            
            error_log("Image Edit Debug - Updated file_id: " . $_POST['file_id'] . " (text only, no image)");
        }
    }

    
    //----------插入圖片資料到資料庫end----------

    // 簡化的重定向邏輯 - 使用 HTTP_REFERER 返回來源頁面
    $updateGoTo = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    
    // 確保 URL 中有 #imageEdit 錨點
    if (strpos($updateGoTo, '#') === false) {
        $updateGoTo .= '#imageEdit';
    }

    if (isset($image_result_check) && $image_result_check[0][0] == 1) {
        echo "<script type=\"text/javascript\">call_alert('" . $updateGoTo . "');</script>";
    } else {
        header(sprintf("Location: %s", $updateGoTo));
    }
}

$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
    $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

$colname_RecImage = "-1";
if (isset($_GET['file_id'])) {
    $colname_RecImage = $_GET['file_id'];
}

// 調試信息
error_log("Image Edit Debug - file_id: " . $colname_RecImage . ", fileType: " . $fileType . ", type: " . $type);

$query_RecImage = "SELECT * FROM file_set WHERE file_type = :file_type AND file_id = :file_id";
$RecImage = $conn->prepare($query_RecImage);
$RecImage->bindParam(':file_type', $fileType, PDO::PARAM_STR);
$RecImage->bindParam(':file_id', $colname_RecImage, PDO::PARAM_INT);
$RecImage->execute();
$row_RecImage = $RecImage->fetch();
$totalRows_RecImage = $RecImage->rowCount();

// 如果沒有找到資料，嘗試不使用 file_type 條件再查一次
if (!$row_RecImage) {
    error_log("Image Edit Debug - No data found with file_type, trying without it");
    $query_RecImage2 = "SELECT * FROM file_set WHERE file_id = :file_id";
    $RecImage2 = $conn->prepare($query_RecImage2);
    $RecImage2->bindParam(':file_id', $colname_RecImage, PDO::PARAM_INT);
    $RecImage2->execute();
    $row_RecImage = $RecImage2->fetch();
    $totalRows_RecImage = $RecImage2->rowCount();
    
    if ($row_RecImage) {
        error_log("Image Edit Debug - Found image with file_type: " . $row_RecImage['file_type']);
        // 更新 fileType 為實際的類型
        $fileType = $row_RecImage['file_type'];
        // 更新配置
        if (isset($imagesSize[$fileType])) {
            $not = $imagesSize[$fileType]['note'] ?? '';
            $IWidth = $imagesSize[$fileType]['IW'] ?? 800;
            $IHeight = $imagesSize[$fileType]['IH'] ?? 600;
        }
    }
}

// 如果還是沒有資料，顯示錯誤
if (!$row_RecImage) {
    die("錯誤：找不到圖片資料 (file_id: {$colname_RecImage}, file_type: {$fileType})");
}

$pageTitle = "圖片";
if(($_SESSION['nowMenu']=='news' || $_SESSION['nowMenu']=='games') && $_REQUEST['type'] == 'videoCode' ){
    $pageTitle = "YouTube 影片碼";
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>修改<?= $pageTitle ?></title>

    <?php require_once('head.php');?>
</head>
<body>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td width="30%" class="list_title">修改<?= $pageTitle ?></td>
            <td width="70%">&nbsp;</td>
        </tr>
    </table>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td><img src="image/spacer.gif" width="1" height="1"></td>
        </tr>
    </table>
    <form action="<?php echo $editFormAction; ?>" method="POST" enctype="multipart/form-data" name="form1" id="form1">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <table width="100%" border="0" cellspacing="3" cellpadding="5">
                        <tr>
                            <td width="200" align="center" bgcolor="#e5ecf6" class="table_col_title">
                                <span class="table_data">
                                    <?php 
                                    if($_SESSION['nowMenu'] == 'latest'){ 
                                        echo "圖片說明(影片連結)";
                                    } elseif(($_SESSION['nowMenu']=='news' || $_SESSION['nowMenu']=='games') && $_REQUEST['type'] == 'videoCode' ){
                                        echo "影片標題";
                                    } else {
                                        echo "圖片說明";
                                    }
                                    ?>
                                </span>
                            </td>
                            <td width="532"><input name="file_title" type="text" class="table_data" id="file_title" value="<?php echo $row_RecImage['file_title']; ?>" size="50">
                                <input name="file_id" type="hidden" id="file_id" value="<?php echo $row_RecImage['file_id']; ?>" />
                                <input name="file_d_id" type="hidden" id="file_d_id" value="<?php echo $row_RecImage['file_d_id']; ?>" /></td>
                                <td width="250" bgcolor="#e5ecf6"></td>
                        </tr>

                            <?php if(($_SESSION['nowMenu']=='news' || $_SESSION['nowMenu']=='games') && $_REQUEST['type'] == 'videoCode' ): ?>

                                <tr>
                                    <td width="200" align="center" bgcolor="#e5ecf6" class="table_col_title">
                                        <span class="table_data">
                                            YouTube 影片碼
                                        </span>
                                    </td>
                                    <td width="532">
                                        <input name="file_youtube_code" type="text" class="table_data" id="file_youtube_code" value="<?php echo $row_RecImage['file_youtube_code']; ?>" size="50">
                                    </td>
                                        <td width="250" bgcolor="#e5ecf6"></td>
                                </tr>

                            <?php else: ?>
                            <?php if($_SESSION['nowMenu']=='progress' || $_SESSION['nowMenu'] == 'latest'){ ?>
                            <tr>
                                <td align="center" bgcolor="#e5ecf6" class="table_col_title"><span class="table_data">圖片內容</span></td>
                                <td><textarea name="file_content" cols="80" rows="5" class="table_data" id="file_content"><?php echo $row_RecImage['file_content']; ?></textarea></td>
                                <td bgcolor="#e5ecf6"></td>
                            </tr>
                            <?php } ?>
                            <tr>
                                <td align="center" bgcolor="#e5ecf6" class="table_col_title">目前圖片</td>
                                <td><img src="../<?php echo $row_RecImage['file_link2'].'?'.(mt_rand(1,100000)/100000); ?>" alt="" class="image_frame" /></td>
                                <td bgcolor="#e5ecf6" class="table_col_title"><p>&nbsp;</p></td>
                            </tr>

                            <!--     newsCover  satart -->
                            <?php if($type=='internationalCover' || $type=='workCover' || $type=='reportingCover'){ ?>
                            <tr>
                                <td align="center" bgcolor="#e5ecf6" class="table_col_title">寬度格式</td>
                                <td>
                                    <label>
                                        <select name="file_width" class="table_data" id="file_width">
                                            <option value="1" <?php if (!(strcmp(1, $row_RecImage['file_width']))) {echo "selected=\"selected\"";} ?>>1個單位</option>
                                            <option value="2" <?php if (!(strcmp(2, $row_RecImage['file_width']))) {echo "selected=\"selected\"";} ?>>2個單位</option>
                                            <option value="3" <?php if (!(strcmp(3, $row_RecImage['file_width']))) {echo "selected=\"selected\"";} ?>>3個單位</option>
                                        </select>
                                    </label>
                                </td>
                                <td bgcolor="#e5ecf6" class="table_col_title">&nbsp;</td>
                            </tr>
                            <tr>
                                <td align="center" bgcolor="#e5ecf6" class="table_col_title">長度格式</td>
                                <td>
                                    <label>
                                        <select name="file_height" class="table_data" id="file_height">
                                            <option value="1" <?php if (!(strcmp(1, $row_RecImage['file_height']))) {echo "selected=\"selected\"";} ?>>1個單位</option>
                                            <option value="2" <?php if (!(strcmp(2, $row_RecImage['file_height']))) {echo "selected=\"selected\"";} ?>>2個單位</option>
                                            <option value="3" <?php if (!(strcmp(3, $row_RecImage['file_height']))) {echo "selected=\"selected\"";} ?>>3個單位</option>
                                        </select>
                                    </label>
                                </td>
                                <td bgcolor="#e5ecf6" class="table_col_title">&nbsp;</td>
                            </tr>
                            <?php } ?>
                            <!--  newsCover  end -->

                            <!-- <tr>
                                <td align="center" bgcolor="#e5ecf6" class="table_col_title">
                                    <p>更改圖片</p>
                                </td>
                                <td>
                                    <input name="image[]" type="file" class="table_data" id="image[]" size="50" >
                                </td>
                                <td bgcolor="#e5ecf6" class="table_col_title">
                                    <p>
                                        <span class="red_letter">*<?php echo $not; ?></span>
                                    </p>
                                </td>
                            </tr> -->
                            <tr>
                                <td align="center" bgcolor="#e5ecf6" class="table_col_title">
                                    <p>更改圖片</p>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: flex-start;">
                                        <div style="width:100px; height: 100px; margin-right: 10px;">
                                            <img id="croppedImagePreviewMain" class="preview-img" src="crop/demo.jpg">
                                        </div>

                                        <div>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <input type="file" id="image_Main" name="image[]" class="hidden-file-input" accept="image/*" style="display:none;">
                                                
                                                <button type="button" class="trigger-crop-btn" data-target="image_Main">選擇檔案</button>
                                            </div>

                                            <div style="margin-top: 5px;">
                                                <button type="button" id="removeBtnMain" class="remove-btn" style="display:none; color:red; border:none; background:none; cursor:pointer;">❌ 移除</button>
                                                <p id="fileNameDisplayMain" class="file-name-display" style="display:none; font-size:0.9rem; color:#555;margin: 0;"></p>
                                                <p id="uploadStatusMain" class="status-msg" style="font-size:0.9rem; color:blue; margin:5px 0 0 0;"></p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" id="imageUrlMain" class="url-input">
                                    
                                    <div id="row_container_Main" style="display:none;"></div>
                                </td>
                                <td bgcolor="#e5ecf6" class="table_col_title">
                                    <p><span class="red_letter"><?php echo $not; ?></span></p>
                                </td>
                            </tr>
                            <?php endif; ?>
                    </table>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td align="center"><input name="submitBtn" type="button" class="btnType" id="submitBtn" value="送出" /></td>
            </tr>
        </table>
        <input type="hidden" name="MM_update" value="form1" />
        <input type="hidden" name="type" value="<?php echo $type; ?>" />
    </form>
    <table width="100%" height="1" border="0" align="center" cellpadding="0" cellspacing="0" class="buttom_dot_line">
        <tr>
            <td>&nbsp;</td>
        </tr>
    </table>
</body>
</html>

<script type="text/javascript" src="jquery/jquery-1.7.2.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(".btnType").hover(function(){ $(this).addClass('btnTypeClass'); $(this).css('cursor', 'pointer'); }, function(){ $(this).removeClass('btnTypeClass'); });
    });
</script>

<?php require_once('crop/edit_modal.php');?>