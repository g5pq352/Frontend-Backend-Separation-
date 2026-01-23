<?php
require_once('../Connections/connect2data.php');
require_once 'auth.php';
?>

<?php
$editFormAction = $_SERVER['PHP_SELF'];

if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

// 簡化的類型檢測 - type 參數不影響 SQL 查詢
// type 只用於判斷圖片尺寸，實際 file_type 通常是 'image'
$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'image';

$colname_RecImage = "-1";
if (isset($_GET['file_id'])) {
  $colname_RecImage = $_GET['file_id'];
}

$query_RecImage = "SELECT * FROM file_set WHERE file_id = :file_id";
$RecImage = $conn->prepare($query_RecImage);
$RecImage->bindParam(':file_id', $colname_RecImage, PDO::PARAM_INT);
$RecImage->execute();
$row_RecImage = $RecImage->fetch();
$totalRows_RecImage = $RecImage->rowCount();

$pageTitle = "圖片";
if(($_SESSION['nowMenu']=='news' || $_SESSION['nowMenu']=='games') && $_REQUEST['type'] == 'videoCode' ){
    $pageTitle = "YouTube 影片碼";
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>   
        刪除<?= $pageTitle ?>
    </title>

    <?php require_once('head.php');?>
</head>

<body>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td width="18%" class="list_title">刪除<?= $pageTitle ?></td>
            <td width="82%"><span class="no_data">您確定要刪除此筆<?= $pageTitle ?>?</span></td>
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
                        <?php 
                        if($_SESSION['nowMenu'] == 'latest'){ 
                            echo "圖片說明(影片連結)";
                        } elseif(($_SESSION['nowMenu']=='news' || $_SESSION['nowMenu']=='games') && $_REQUEST['type'] == 'videoCode' ){
                            echo "影片標題";
                        } else {
                            echo "圖片說明";
                        }
                        ?>
                    </td>
                    <td width="532" class="table_data"><?php echo $row_RecImage['file_title']; ?></td>
                    <td width="250" bgcolor="#e5ecf6">&nbsp;</td>
                </tr>
            <?php if(($_SESSION['nowMenu']=='news' || $_SESSION['nowMenu']=='games') && $_REQUEST['type'] == 'videoCode' ): ?>
                <tr>
                    <td align="center" bgcolor="#e5ecf6" class="table_col_title"><span class="table_data">YouTube 影片碼</span></td>
                    <td class="table_data"><?php echo escape_with_br($row_RecImage['file_youtube_code']); ?></td>
                    <td bgcolor="#e5ecf6"></td>
                </tr>
            <?php else: ?>
                <?php if($_SESSION['nowMenu']=='collection'){ ?>
                <tr>
                  <td align="center" bgcolor="#e5ecf6" class="table_col_title"><span class="table_data">圖片說明</span></td>
                  <td class="table_data"><?php echo escape_with_br($row_RecImage['file_content']); ?></td>
                  <td bgcolor="#e5ecf6"></td>
              </tr>
              <?php } ?>
                <tr>
                    <td align="center" bgcolor="#e5ecf6" class="table_col_title">目前圖片</td>
                    <td><img src="../<?php echo $row_RecImage['file_link2'].'?'.(mt_rand(1,100000)/100000); ?>" alt="" class="image_frame" /></td>
                    <td bgcolor="#e5ecf6" class="table_col_title"><p>&nbsp;</p></td>
                </tr>
            <?php endif; ?>
            </table>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
         <tr>
            <td align="center"><input name="file_id" type="hidden" id="file_id" value="<?php echo $row_RecImage['file_id']; ?>" />
            <input name="delsure" type="hidden" id="delsure" value="1" />
            <input name="submitBtn" type="submit" class="btnType" id="submitBtn" value="送出" /></td>
         </tr>
    </table>
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
        $(".btnType").hover(function() {
            $(this).addClass('btnTypeClass');
            $(this).css('cursor', 'pointer');
        }, function() {
            $(this).removeClass('btnTypeClass');
        });
    });
</script>

<?php
if ((isset($_POST['file_id'])) && ($_POST['file_id'] != "") && (isset($_POST['delsure']))) {

    //刪除圖片真實檔案begin----

    $sql = "SELECT * FROM file_set WHERE file_id=:file_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam(':file_id', $_POST['file_id'], PDO::PARAM_INT);
    $sth->execute();

    while ($row = $sth->fetch()) {
        if ((isset($row['file_link1'])) && file_exists("../" . $row['file_link1'])) {
            unlink("../" . $row['file_link1']); //刪除檔案
        }
        if ((isset($row['file_link2'])) && file_exists("../" . $row['file_link2'])) {
            unlink("../" . $row['file_link2']); //刪除檔案
        }
        if ((isset($row['file_link3'])) && file_exists("../" . $row['file_link3'])) {
            unlink("../" . $row['file_link3']); //刪除檔案
        }
        if ((isset($row['file_link4'])) && file_exists("../" . $row['file_link4'])) {
            unlink("../" . $row['file_link4']); //刪除檔案
        }
        if ((isset($row['file_link5'])) && file_exists("../" . $row['file_link5'])) {
            unlink("../" . $row['file_link5']); //刪除檔案
        }
    }

    //刪除圖片真實檔案end----

    $deleteSQL = "DELETE FROM file_set WHERE file_id=:file_id";

    $sth = $conn->prepare($deleteSQL);
    $sth->bindParam(':file_id', $_POST['file_id'], PDO::PARAM_INT);
    $sth->execute();

    // 簡化的重定向邏輯 - 使用 HTTP_REFERER 返回來源頁面
    $deleteGoTo = $_SERVER['HTTP_REFERER'] ?? 'index.php';
    
    // 確保 URL 中有 #imageEdit 錨點
    if (strpos($deleteGoTo, '#') === false) {
        $deleteGoTo .= '#imageEdit';
    }

    header(sprintf("Location: %s", $deleteGoTo));
}
?>