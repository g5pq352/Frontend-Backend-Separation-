<?php require_once('../Connections/connect2data.php'); ?>

<?php
$editFormAction = $_SERVER['PHP_SELF'];
if (isset($_SERVER['QUERY_STRING'])) {
  $editFormAction .= "?" . htmlentities($_SERVER['QUERY_STRING']);
}

$colname_RecFile = "-1";
if (isset($_GET['file_id'])) {
  $colname_RecFile = $_GET['file_id'];
}

$query_RecFile = "SELECT * FROM file_set WHERE file_id = :file_id";
$RecFile = $conn->prepare($query_RecFile);
$RecFile->bindParam(':file_id', $colname_RecFile, PDO::PARAM_INT);
$RecFile->execute();
$row_RecFile = $RecFile->fetch();
$totalRows_RecFile = $RecFile->rowCount();

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>刪除圖片</title>
</head>
<body>
    <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td width="18%" class="list_title">刪除檔案</td>
            <td width="82%"><span class="no_data">您確定要刪除此筆檔案?</span></td>
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
                            <td width="200" align="center" bgcolor="#e5ecf6" class="table_col_title">檔案說明</td>
                            <td width="532" class="table_data">
                                <?php echo $row_RecFile['file_title']; ?>
                            </td>
                            <td width="250" bgcolor="#e5ecf6">&nbsp;</td>
                        </tr>
                        <tr>
                            <td align="center" bgcolor="#e5ecf6" class="table_col_title">目前檔案</td>
                            <td class="table_data">
                                <?php echo $row_RecFile['file_name']; ?>
                            </td>
                            <td bgcolor="#e5ecf6" class="table_col_title">
                                <p>&nbsp;</p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td align="center">
                    <input name="file_id" type="hidden" id="file_id" value="<?php echo $row_RecFile['file_id']; ?>" />
                    <input name="delsure" type="hidden" id="delsure" value="1" />
                    <input name="submitBtn" type="submit" class="btnType" id="submitBtn" value="送出" />
                </td>
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
        $(".btnType").hover(function(){
            $(this).addClass('btnTypeClass');
            $(this).css('cursor', 'pointer');
        }, function(){
            $(this).removeClass('btnTypeClass');
        });
    });
</script>

<?php
if ((isset($_POST['file_id'])) && ($_POST['file_id'] != "") && (isset($_POST['delsure']))) {

    //刪除真實檔案begin----

    $sql = "SELECT file_link1 FROM file_set WHERE file_id=:file_id";
    $sth = $conn->prepare($sql);
    $sth->bindParam(':file_id', $_POST['file_id'], PDO::PARAM_INT);
    $sth->execute();

    while ($row = $sth->fetch()) {
        if (file_exists("../" . $row[0])) {
            unlink("../" . $row[0]); //刪除檔案
        }
    }

    //刪除真實檔案end----

    $deleteSQL = "DELETE FROM file_set WHERE file_id=:file_id";
    $sth = $conn->prepare($deleteSQL);
    $sth->bindParam(':file_id', $_POST['file_id'], PDO::PARAM_INT);
    $sth->execute();

    $deleteGoTo = $_SESSION['nowPage'] . "?d_id=" . $row_RecFile['file_d_id'] . "";

    header(sprintf("Location: %s", $deleteGoTo));
}
?>