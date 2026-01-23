<?php
require_once '../Connections/connect2data.php';

function image_process(PDO $pdo, $FILES_A, $file_title, $file_name, $deal_type, $image_width, $image_height, $multiple = 0) {
    ////echo count($FILES_A['name']);//上傳物件數量
    ////echo count($_REQUEST[image_title]);//上傳物件的說明之數量
    //echo "imageH = ".$image_height."<br/>";
    $all_image_name = array(); //建立回傳的資料陣列
    $no_image = 0;

    //******如果是插入記錄的上傳圖片begin*******/
    if ($deal_type == "add") {

        // pdo 已經是不同的了所以 lastInsertId 不能用只好用這樣
        $sql_max_pic = "SELECT MAX(file_id) FROM file_set";
        $sth = $pdo->query($sql_max_pic)->fetch();
        $new_pic_num = $sth[0] + 1;

    }
    //******如果是插入記錄的上傳圖片end*******/

    //******如果是更新記錄的上傳圖片begin*******/
    if ($deal_type == "edit") {

        $new_pic_num = $_POST['file_id'] ?? 0;

        ////echo $new_pic_num;

    }
    //******如果是更新記錄的上傳圖片end*******/

    //******如果是更新記錄的上傳圖片begin*******/
    if ($deal_type == "multiple") {

        // pdo 已經是不同的了所以 lastInsertId 不能用只好用這樣
        $sql_max_pic = "SELECT MAX(file_id) FROM file_set";
        $sth = $pdo->query($sql_max_pic)->fetch();
        $new_pic_num = $sth[0] + 1;

    }
    //******如果是更新記錄的上傳圖片end*******/

    for ($j = 0; $j < count($FILES_A['name']); $j++) {
        $tmp_name = $FILES_A['name'][$j];

        if ($tmp_name != '') //如果有上傳檔案
        {

            //******產生相對應的資料夾begin*******//
            $image_path = "upload_image";
            check_path($image_path); //如果沒有資料夾，產生資料夾

            $image_path .= "/" . $file_name;
            //echo "image_path = ".$image_path."<br>";
            check_path($image_path); //如果沒有資料夾，產生資料夾

            //******產生相對應的資料夾end*******//

            ////echo  $FILES_A['name'][$j]."<br>";
            $file_parts = explode(".", $FILES_A['name'][$j]);
            $image_type = strtolower(end($file_parts)); //將檔案已"."分開，放到陣列呼叫array最後一個資料,為檔案副檔名
            ////echo $image_type."<br>";//

            //確定檔案是圖片檔案
            if ($image_type == "jpg" || $image_type == "gif" || $image_type == "bmp" || $image_type == "png" || $image_type == "tif") {

                // $photo_name = $new_pic_num + $j; //將新id轉成檔案名，已上傳的數量來增加
                $photo_name = md5($file_name . ($new_pic_num + $j) . time());
                ////echo $photo_name."<br>";

                /*-------從檔案暫存區複製到指定的資料夾，並縮圖begin--------*/
                $FILES_A['name'][$j] = str_replace(" ", "", $FILES_A['name'][$j]); //將檔案名內有空白的除掉

                // $size = getimagesize($FILES_A['tmp_name'][$j]);
                // $orginal_width = $size[0]; //寬
                // $orginal_height = $size[1]; //長

                // if ($image_width == 0) //如果是0使用圖片原來的尺寸:注意、注意
                // {
                //     $image_width = $orginal_width;
                //     $image_height = $orginal_height;
                // }

                $size = getimagesize($FILES_A['tmp_name'][$j]);
                
                $orginal_width = $size[0]; //寬
                $orginal_height = $size[1]; //長

                // 定義最大寬度限制 (與前端 JS 保持一致)
                $MAX_ALLOWED_WIDTH = 2560;

                if ($image_width == 0) 
                {
                    // 如果原本設定就是 0 (不限制)，則直接使用原圖尺寸
                    $image_width = $orginal_width;
                    $image_height = $orginal_height;
                } 
                else 
                {
                    // $image_width 在這裡是 "後台設定的最小寬度" (例如 420)
                    if ($orginal_width > $MAX_ALLOWED_WIDTH) {
                        // [情況 A] 實際上傳寬度 > 2560 -> 縮小至 2560
                        // 計算縮放比例
                        $ratio = $MAX_ALLOWED_WIDTH / $orginal_width;
                        
                        $image_width = $MAX_ALLOWED_WIDTH;
                        $image_height = round($orginal_height * $ratio); // 高度依比例計算
                    } 
                    elseif ($orginal_width < $image_width) {
                        // [情況 B] 實際上傳寬度 < 設定寬度 (例如 420) -> 強制放大至 420
                        // 這裡維持原本傳入的 $image_width 和 $image_height 不變
                        // 程式後續會執行 imagesResize_5 強制放大
                    } 
                    else {
                        // [情況 C] 介於中間 (420 ~ 2560) -> 保留原圖高畫質
                        // 將目標尺寸改為目前圖片的實際尺寸，這樣後續就不會執行縮小
                        $image_width = $orginal_width;
                        $image_height = $orginal_height;
                    }
                }

                /*if($orginal_width<$image_width){
                $image_width=$orginal_width;
                $image_height=$orginal_height;
                }*/

                $image_path_new = "../" . $image_path;
                $this_path1 = $image_path . "/" . $file_name . "_" . $photo_name . "." . $image_type;
                $this_image_path1 = "../" . $this_path1;
                ////echo $this_path1."<br>";

                //if(($orginal_width/$orginal_height)>($image_width/$image_height))//如果大於比例，以分子為主(在此為不改變圖片比例)
                /*if(($orginal_width/$orginal_height)>($image_width/$image_height))//如果大於比例，以分子為主(在此為不改變圖片比例)
                {
                $exec_str="convert -resize 9999x".$image_height." ".$_FILES[image][tmp_name][$j]." ../".$this_path1;
                imagesResize($this_path1,$image_path,$destW,$destH);

                }else//如果小於比例，以分母為主
                {
                $exec_str="convert -resize ".$image_width."x9999 ".$_FILES[image][tmp_name][$j]." ../".$this_path1;
                imagesResize($src,$src,$destW,$destH);
                }*/
                //exec($exec_str);
                //複製暫存檔
                copy($FILES_A['tmp_name'][$j], $this_image_path1);
                /*if($orginal_width>$image_width){
                imagesResize($this_image_path1,$this_image_path1,$image_width,$image_height);
                //echo "orginal_width = ".$orginal_width;"<br>";
                //echo "orginal_height = ".$orginal_height;"<br>";
                //echo "image_width = ".$image_width;"<br>";
                //echo "image_height = ".$image_height;"<br>";
                }else if($orginal_height>$image_height){
                imagesResize($this_image_path1,$this_image_path1,$image_width,$image_height);
                //echo "orginal_width = ".$orginal_width;"<br>";
                //echo "orginal_height = ".$orginal_height;"<br>";
                //echo "image_width = ".$image_width;"<br>";
                //echo "image_height = ".$image_height;"<br>";
                }else{
                //imagesResize_3($this_image_path1,$this_image_path1,$orginal_width,$orginal_height);
                imagesResize($this_image_path1,$this_image_path1,$image_width,$image_height);
                }*/
                //取得檔案資訊
                $srcSize = getimagesize($this_image_path1);

                if ($srcSize[0] > $image_width) {
                    imagesResize_5($this_image_path1, $this_image_path1, $image_width, $image_height);
                }

                //echo 'image_width = '.$image_width.'<br>';
                //echo 'image_height = '.$image_height.'<br>';
                /*$exec_str="convert ../".$this_path1." -quality 100% -gravity center -crop ".$image_width."x".$image_height."+0+0 +repage ../".$this_path1;//切圖
                exec($exec_str);*/
                /*
                 * 這裡$srcSize為一個數組類型
                 * $srcSize[0] 為圖像的寬度
                 * $srcSize[1] 為圖像的高度
                 * $srcSize[2] 為圖像的格式，包括jpg、gif和png等
                 * $srcSize[3] 為圖像的寬度和高度，內容為 width="xxx" height="yyy"
                 */

                //判斷橫式或直式圖
                $format = 0;
                if ($srcSize[0] >= $srcSize[1]) {
                    //横式圖或正方形圖
                    $format = 1;
                } elseif ($srcSize[0] < $srcSize[1]) {
                    //直式圖
                    $format = 0;
                }

                //縮中圖detail用
                /*$this_path2=$image_path."/".$file_name."_".$photo_name."_m596.".$image_type;
                $this_image_path2 = "../".$this_path2;
                copy($FILES_A['tmp_name'][$j] , $this_image_path2 );
                $destW = 596;
                $destH = 390;
                imagesResize_5($this_image_path2,$this_image_path2,$destW,$destH);*/

                //縮中圖index用
                /*$this_path2=$image_path."/".$file_name."_".$photo_name."_m272.".$image_type;
                $this_image_path2 = "../".$this_path2;
                copy($FILES_A['tmp_name'][$j] , $this_image_path2 );
                $destW = 272;
                $destH = 130;
                imagesResize_5($this_image_path2,$this_image_path2,$destW,$destH);*/

                //縮小圖後台list用
                $this_path2 = $image_path . "/" . $file_name . "_" . $photo_name . "_s100." . $image_type;
                $this_image_path2 = "../" . $this_path2;
                copy($FILES_A['tmp_name'][$j], $this_image_path2);
                $destW = 100;
                $destH = 66;
                //imagesResize_2($this_image_path3,$this_image_path3,$destW,$destH);
                imagesResize_4($this_image_path2, $this_image_path2, $destW, $destH);

                //縮小圖list用-正方形
                //$destW = $image_width/2;
                //$destH = $image_height/2;
                $destW = 460;
                $destH = 265;

                $this_path3 = $image_path . "/" . $file_name . "_" . $photo_name . "_s" . intval($destW) . "." . $image_type;
                $this_image_path3 = "../" . $this_path3;
                copy($FILES_A['tmp_name'][$j], $this_image_path3);
                //imagesResize_2($this_image_path3,$this_image_path3,$destW,$destH);
                imagesResize_4($this_image_path3, $this_image_path3, $destW, $destH);

                /*$exec_str="convert -resize 130x88 ".$_FILES[image][tmp_name][$j]." ../".$this_path2;
                exec($exec_str);
                $exec_str="convert ../".$this_path2." -quality 80% -gravity center -crop 130x88+0+0 +repage ../".$this_path2;//切圖
                //$exec_str="convert ../".$this_path1." -quality 80% -gravity center -crop ".$image_width."x".$image_height."+0+0 +repage ../".$this_path1;//切圖
                exec($exec_str);*/
                /*-------從檔案暫存區複製到指定的資料夾，並縮圖end--------*/

                ////echo $FILES_A['name'][$j].":name<br>";
                ////echo $_REQUEST[image_title][$j].":title<br>";
                $db_file_name = $file_name . "_" . $photo_name . "." . $image_type; //儲存到資料庫的檔案名稱

                $all_image_name[$j + 1][0] = $db_file_name; //儲存檔案名
                $all_image_name[$j + 1][1] = $this_path1; //大圖檔案位置
                $all_image_name[$j + 1][2] = $this_path2; //小圖檔案位置後台list用//中圖檔案位置index用
                //$all_image_name[$j+1][3]=$this_path3;//小圖檔案位置後台list用
                //$all_image_name[$j+1][4]=$this_path4;//小圖檔案位置list用
                /*echo 'j = '.$j.'<br>';
                echo 'file_title = '.$file_title[$j].'<br>';*/
                $all_image_name[$j + 1][3] = $this_path3; //小圖檔案位置list用
                //$all_image_name[$j+1][4]=$this_path4;//小圖檔案位置list用
                $all_image_name[$j + 1][5] = $format; //判斷橫式或直式圖
                //echo 'j = '.$j.'<br>';
                //echo 'file_title = '.$file_title[$j].'<br>';
                $all_image_name[$j + 1][4] = (isset($file_title[$j])) ? $file_title[$j] : ''; //檔案title(說明)

            } else //如果不是圖片，提出警告
            {
                $no_image = 1;
                ////echo "不是圖片";
            }
        } else //沒上傳檔案
        {
            ////echo "沒上傳檔案";

        }

    }

    if ($no_image == 1) {
        $all_image_name[0][0] = 1;
    } else {
        $all_image_name[0][0] = 0;
    }

    //print_r($all_image_name);//列出陣列
    return $all_image_name;
}

function check_path($image_path) {

    if (!is_dir("../" . $image_path)) //如果沒有資料夾
    {
        mkdir("../" . $image_path); //產生資料夾
    } else {
        //dont do thing
    }
}

//ini_set("memory_limit","100M");
//依設定尺寸裁切
function imagesResize($src, $dest, $destW, $destH) {
    if (file_exists($src) && isset($dest)) {
        //取得檔案資訊
        $srcSize = getimagesize($src);

        // 檢查圖片是否有效
        if (!$srcSize || $srcSize[0] == 0 || $srcSize[1] == 0) {
            return false;
        }

        /*
         * 這裡$srcSize為一個數組類型
         * $srcSize[0] 為圖像的寬度
         * $srcSize[1] 為圖像的高度
         * $srcSize[2] 為圖像的格式，包括jpg、gif和png等
         * $srcSize[3] 為圖像的寬度和高度，內容為 width="xxx" height="yyy"
         */

        //判斷橫式或直式圖
        $format = 0;
        if ($srcSize[0] >= $srcSize[1]) {
            //横式圖或正方形圖
            $format = 1;
        } elseif ($srcSize[0] < $srcSize[1]) {
            //直式圖
            $format = 0;
        }

        if ($format) {

            //依width為主 W /H
            $srcRatio = $srcSize[0] / $srcSize[1];

            //echo "横式圖或正方形圖<br>";
            if (($srcSize[0] > $destW) && ($srcSize[1] > $destH)) {

                //echo "W > , H > | ";

                //依長寬比判斷長寬像素
                $destH_2 = $destW / $srcRatio;
                $destRatio = $srcSize[0] / $destW;
                ////echo "destW = ".$destW."<br/>";
                ////echo "destH = ".$destH."<br/>";
                //echo "destH_2 = ".$destH_2."<br/>";
                if ($destH_2 > $destH) {
                    //依hight為主 H/W
                    $srcRatio = $srcSize[1] / $srcSize[0];
                    $destW_2 = $destH / $srcRatio;
                    $destRatio = $srcSize[1] / $destH;
                    $destW = $destW_2;
                    //$destH = $destH_2;
                    //echo "大於<br/>";
                    //$disX = ($destW_2 - $destW)/2;
                    //$disY = ($destH - $destH)/2;
                } else {
                    $destH = $destH_2;
                    //echo "小於<br/>";
                    //$disX = ($destW - $destW)/2;
                    //$disY = ($destH_2 - $destH)/2;
                }

                $srcW = $destW * $destRatio;
                $srcH = $destH * $destRatio;
                $srcX = abs(($srcSize[0] - $srcW)) / 2;
                $srcY = abs(($srcSize[1] - $srcH)) / 2;

            } elseif (($srcSize[0] > $destW) && ($srcSize[1] <= $destH)) {

                //echo "W > , H <= | ";
                //依長寬比判斷長寬像素
                $destH = $destW / $srcRatio;
                $destRatio = $srcSize[0] / $destW;

                $srcW = $destW * $destRatio;
                $srcH = $destH * $destRatio;
                $srcX = abs(($srcSize[0] - $srcW)) / 2;
                $srcY = abs(($srcSize[1] - $srcH)) / 2;

            } elseif (($srcSize[0] <= $destW) && ($srcSize[1] > $destH)) {

                //echo "W <= , H > | ";
                $srcRatio = $srcSize[1] / $srcSize[0];
                $destW_2 = $destH / $srcRatio;
                $destRatio = $srcSize[1] / $destH;
                $destW = $destW_2;

                $srcW = $destW * $destRatio;
                $srcH = $destH * $destRatio;
                $srcX = abs(($srcSize[0] - $srcW)) / 2;
                $srcY = abs(($srcSize[1] - $srcH)) / 2;

            } elseif (($srcSize[0] <= $destW) && ($srcSize[1] <= $destH)) {

                //echo "W <= , H <= | ";
                //$srcRatio = $destRatio = $srcSize[1] / $srcSize[0];
                $destW = $srcSize[0];
                $destH = $srcSize[1];

                $srcW = $destW;
                $srcH = $destH;
                $srcX = 0;
                $srcY = 0;

            }

        } else {

            //echo "直式圖<br>";
            //依hight為主 H/W
            $srcRatio = $srcSize[1] / $srcSize[0];
            $destW = $destH;

            if (($srcSize[1] > $destH) && ($srcSize[0] > $destW)) {

                $destW_2 = $destH / $srcRatio;
                $destRatio = $srcSize[1] / $destH;
                $destW = $destW_2;

                $srcW = $destW * $destRatio;
                $srcH = $destH * $destRatio;
                $srcX = abs(($srcSize[0] - $srcW)) / 2;
                $srcY = abs(($srcSize[1] - $srcH)) / 2;

            } elseif (($srcSize[1] > $destH) && ($srcSize[0] <= $destW)) {

                $destW_2 = $destH / $srcRatio;
                $destRatio = $srcSize[1] / $destH;
                $destW = $destW_2;

                $srcW = $destW * $destRatio;
                $srcH = $destH * $destRatio;
                $srcX = abs(($srcSize[0] - $srcW)) / 2;
                $srcY = abs(($srcSize[1] - $srcH)) / 2;

            }if (($srcSize[1] <= $destH) && ($srcSize[0] <= $destW)) {

                //$srcRatio = $destRatio = $srcSize[1] / $srcSize[0];
                $destW = $srcSize[0];
                $destH = $srcSize[1];

                $srcW = $destW;
                $srcH = $destH;
                $srcX = 0;
                $srcY = 0;

            }

        }

        $srcExtension = $srcSize[2]; //格式

        /*$srcRatio  = $srcSize[0] / $srcSize[1]; //依width為主 W /H
        //依長寬比判斷長寬像素

        $destH_2 = $destW / $srcRatio;
        $destRatio = $srcSize[0] / $destW;
        ////echo "destW = ".$destW."<br/>";
        ////echo "destH = ".$destH."<br/>";
        ////echo "destH_2 = ".$destH_2."<br/>";
        if($destH_2<$destH){//依hight為主
        $srcRatio  = $srcSize[1] / $srcSize[0];
        $destW_2 = $destH / $srcRatio;
        $destRatio = $srcSize[1] / $destH;

        ////echo "大於<br/>";
        //$disX = ($destW_2 - $destW)/2;
        //$disY = ($destH - $destH)/2;
        }else{
        //$destH = $destH_2;
        ////echo "小於<br/>";
        //$disX = ($destW - $destW)/2;
        //$disY = ($destH_2 - $destH)/2;
        }*/

        //$srcX = $destW - $disX;
        //$srcY = $destH - $disY;
        //$srcX = ($destW*$srcRatio_B)/2;
        //$srcY = ($destH*$srcRatio_B)/2;

        /*$srcW = $destW*$destRatio;
    $srcH = $destH*$destRatio;
    $srcX = abs(($srcSize[0] - $srcW))/2;
    $srcY = abs(($srcSize[1] - $srcH))/2;*/
    }
    //echo "<br/>srcRatio = $srcRatio<br/>";
    //echo "destRatio = $destRatio<br>";
    //echo "destW = ".$destW."<br/>";
    //echo "destH = ".$destH."<br/>";
    /*echo "srcW = ".$srcW."<br/>";
    echo "srcH = ".$srcH."<br/>";
    echo "srcX = ".$srcX."<br/>";
    echo "srcY = ".$srcY."<br/><br/>";*/
    //建立影像
    $destImage = imagecreatetruecolor($destW, $destH);
    ////echo "destImage = ".$destImage."<br/>";
    //根據檔案格式讀取圖檔
    switch ($srcExtension) {
    case 1:$srcImage = imagecreatefromgif($src);
        break;
    case 2:$srcImage = imagecreatefromjpeg($src);
        break;
    case 3:$srcImage = imagecreatefrompng($src);
        break;
    }

    //有處理透明背景
    if (($srcExtension == 1) || ($srcExtension == 3)) {
        $transparency = imagecolortransparent($srcImage);

        // If we have a specific transparent color
        if ($transparency >= 0) {
            // Get the original image's transparent color's RGB values
            $transparent_color = imagecolorsforindex($srcImage, $trnprt_indx);
            // Allocate the same color in the new image resource
            $transparency = imagecolorallocate($destImage, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
            // Completely fill the background of the new image with allocated color.
            imagefill($destImage, 0, 0, $transparency);
            // Set the background color for new image to transparent
            imagecolortransparent($destImage, $transparency);
        }
        // Always make a transparent background color for PNGs that don't have one allocated already
        elseif ($srcExtension == 3) {

            // Turn off transparency blending (temporarily)
            imagealphablending($destImage, false);
            // Create a new transparent color for image
            $color = imagecolorallocatealpha($destImage, 0, 0, 0, 127);
            // Completely fill the background of the new image with allocated color.
            imagefill($destImage, 0, 0, $color);
            // Restore transparency blending
            imagesavealpha($destImage, true);
        }
    }

    //取樣縮圖
    /*bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
    $dst_image：新建的圖片
    $src_image：需要載入的圖片
    $dst_x：設定需要載入的圖片在新圖中的x坐標
    $dst_y： 設定需要載入的圖片在新圖中的y坐標
    $src_x：設定載入圖片要載入的 區域x坐標
    $src_y ：設定載入圖片要載入的 區域y坐標
    $dst_w：設定載入 的原圖 的寬度（ 在此設置縮放 ）
    $dst_h ：設定載入的原圖的高度（在此設置縮放）
    $src_w：原圖要載入的寬度
    $src_h： 原圖要載入的高度*/
    //imagecopyresampled( 輸出目標檔案, 來源檔案, 目標檔案開始點的x座標, 目標檔案開始點的y座標, 來源檔案開始點的x座標, 來源檔案開始點的y座標, 目標檔案的長度, 目標檔案的高度, 來源檔案的長度, 來源檔案的高度)
    imagecopyresampled($destImage, $srcImage, 0, 0, $srcX, $srcY, $destW, $destH, $srcW, $srcH);
    //imagefilledrectangle($destImage, 0, 0, $destW, $destH, $white_color);

    //輸出圖檔
    switch ($srcExtension) {
    case 1:imagegif($destImage, $dest);
        break;
    case 2:imagejpeg($destImage, $dest, 100);
        break;
    case 3:imagepng($destImage, $dest);
        break;

        //釋放資源
        imagedestroy($destImage);
    }
}

//縮小圖專用,依設定尺寸
function imagesResize_4($src, $dest, $destW, $destH) {
    if (file_exists($src) && isset($dest)) {
        //取得檔案資訊
        $srcSize = getimagesize($src);

        // 檢查圖片是否有效
        if (!$srcSize || $srcSize[0] == 0 || $srcSize[1] == 0) {
            return false;
        }

        $srcExtension = $srcSize[2];
        $srcRatio = $srcSize[0] / $srcSize[1]; 

        $destH_2 = $destW / $srcRatio;
        $destRatio = $srcSize[0] / $destW;

        if ($destH_2 < $destH) {
            $srcRatio = $srcSize[1] / $srcSize[0];
            $destW_2 = $destH / $srcRatio;
            $destRatio = $srcSize[1] / $destH;
        }

        $srcW = $destW * $destRatio;
        $srcH = $destH * $destRatio;
        $srcX = ($srcSize[0] - $srcW) / 2;
        $srcY = ($srcSize[1] - $srcH) / 2;

        //建立影像
        $destImage = imagecreatetruecolor($destW, $destH);

        //根據檔案格式讀取圖檔
        switch ($srcExtension) {
            case 1: $srcImage = imagecreatefromgif($src); break;
            case 2: $srcImage = imagecreatefromjpeg($src); break;
            case 3: $srcImage = imagecreatefrompng($src); break;
        }

        // --- 修正開始：PNG/GIF 透明背景處理 ---
        if ($srcExtension == 3 || $srcExtension == 1) {
            // 關閉混合模式，以便直接拷貝 Alpha 通道
            imagealphablending($destImage, false);
            // 開啟儲存 Alpha 通道資訊
            imagesavealpha($destImage, true);
            // 建立一個完全透明的顏色 (R, G, B, Alpha: 127為全透明)
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            // 將新畫布填滿透明色
            imagefilledrectangle($destImage, 0, 0, $destW, $destH, $transparent);
        }
        // --- 修正結束 ---

        //取樣縮圖
        imagecopyresampled($destImage, $srcImage, 0, 0, (int)$srcX, (int)$srcY, (int)$destW, (int)$destH, (int)$srcW, (int)$srcH);

        //輸出圖檔 (修正 Switch 結構與參數)
        switch ($srcExtension) {
            case 1:
                imagegif($destImage, $dest);
                break;
            case 2:
                imagejpeg($destImage, $dest, 90); // JPG 品質設為 90
                break;
            case 3:
                // PNG 壓縮級別 0-9 (不需設100)
                imagepng($destImage, $dest, 9); 
                break;
        }

        //釋放資源 (移到 Switch 外面確保執行)
        imagedestroy($destImage);
        if(isset($srcImage)) imagedestroy($srcImage);
    }
}

//取正方形
function imagesResize_2($src, $dest, $destW, $destH) {
    if (file_exists($src) && isset($dest)) {

        $imgSetW = $destW;
        $imgSetH = $destH;

        //取得檔案資訊
        $srcSize = getimagesize($src);

        // 檢查圖片是否有效
        if (!$srcSize || $srcSize[0] == 0 || $srcSize[1] == 0) {
            return false;
        }

        $srcExtension = $srcSize[2];
        $srcRatio = $srcSize[0] / $srcSize[1];
        //依長寬比判斷長寬像素

        $destH_2 = $destW / $srcRatio;
        ////echo "destW = ".$destW."<br/>";
        ////echo "destH = ".$destH."<br/>";
        ////echo "destH_2 = ".$destH_2."<br/>";
        if ($destH_2 < $destH) {
            $srcRatio = $srcSize[1] / $srcSize[0];
            $destW = $destH / $srcRatio;
            //$destH = $destH_2;
            ////echo "大於<br/>";
        } else {
            $destH = $destH_2;
            ////echo "小於<br/>";
        }
        $srcX = ($destW - $imgSetW) / 2;
        $srcY = ($destH - $imgSetH) / 2;

    }
    ////echo "小destW = ".$destW."<br/>";
    ////echo "小destH = ".$destH."<br/>";
    ////echo "小srcX = ".$srcX."<br/>";
    ////echo "小srcY = ".$srcY."<br/>";
    //建立影像
    $destImage = imagecreatetruecolor($imgSetW, $imgSetH);
    //$destImage = imagecreatetruecolor($destW,$destH);
    ////echo "小destImage = ".$destImage."<br/>";
    //根據檔案格式讀取圖檔
    switch ($srcExtension) {
    case 1:$srcImage = imagecreatefromgif($src);
        break;
    case 2:$srcImage = imagecreatefromjpeg($src);
        break;
    case 3:$srcImage = imagecreatefrompng($src);
        break;
    }

    //$tgXS = $imgSetW*(imagesx($srcImage)/$destW);
    //$tgYS = $imgSetH*(imagesy($srcImage)/$destH);
    //$tgX = ((imagesx($srcImage)-$tgXS)/2)+$tgXS;
    //$tgY = ((imagesy($srcImage)-$tgXS)/2)+$tgYS;
    if (imagesx($srcImage) < imagesy($srcImage)) {
        $tgY = ((imagesy($srcImage) - imagesx($srcImage)) / 2);
        $tgX = 0;
        $tgS = imagesx($srcImage);
    } else if (imagesx($srcImage) > imagesy($srcImage)) {
        $tgX = ((imagesx($srcImage) - imagesy($srcImage)) / 2);
        $tgY = 0;
        $tgS = imagesy($srcImage);
    } else {
        $tgX = 0;
        $tgY = 0;
        $tgS = imagesx($srcImage);
    }

    //取樣縮圖
    imagecopyresampled($destImage, $srcImage, 0, 0, $tgX, $tgY, $imgSetW, $imgSetH,
        $tgS, $tgS);
    ////echo "imagesx = ".imagesx($srcImage)."<br/>";
    ////echo "imagesy = ".imagesy($srcImage)."<br/>";
    ////echo "tgS = ".$tgS."<br/>";
    ////echo "tgX = ".$tgX."<br/>";
    ////echo "tgY = ".$tgY."<br/>";
    //輸出圖檔
    switch ($srcExtension) {
    case 1:imagegif($destImage, $dest);
        break;
    case 2:imagejpeg($destImage, $dest, 100);
        break;
    case 3:imagepng($destImage, $dest);
        break;

        //釋放資源
        imagedestroy($destImage);
    }
}
//強制大小
function imagesResize_3($src, $dest, $destW, $destH) {
    if (file_exists($src) && isset($dest)) {
        //取得檔案資訊
        $srcSize = getimagesize($src);

        // 檢查圖片是否有效
        if (!$srcSize || $srcSize[0] == 0 || $srcSize[1] == 0) {
            return false;
        }

        $srcExtension = $srcSize[2];
        $srcRatio = $srcSize[0] / $srcSize[1]; //依原圖width為主算出比例

        $destRatio = $destW / $destH;
        $dest_W2 = $srcSize[0] * $destRatio;
        $destH_2 = $srcSize[1] * $destRatio;

        //依長寬比判斷長寬像素
        //if( $srcSize[0]<$destW && $srcSize[1]<$destH ){            //原圖的寬高小於720x480

        $destRatio = $destW / $srcSize[0];
        //echo "destW = $destW<br>";
        $dest_W2 = $destW;
        $destH_2 = $srcSize[1] * $destRatio;
        /*echo "destRatio = $destRatio<br>";
        echo "dest_W2 = $dest_W2<br>";
        echo "destH_2 = $destH_2<br>";*/
        if ($destH_2 < $destH) {
//依hight為主
            $destRatio = $destH / $srcSize[1];
            $destH_2 = $destH;
            $dest_W2 = $srcSize[0] * $destRatio;
        }
        /*echo "destRatio = $destRatio<br>";
        echo "dest_W2 = $dest_W2<br>";
        echo "destH_2 = $destH_2<br>";*/
        //}elseif( $srcSize[0]<$destW && $srcSize[1]>$destH ){    //原圖的寬小於720 高大於480

        //}elseif( $srcSize[0]>$destW && $srcSize[1]<$destH ){    //原圖的寬小於720 高大於480

        //}elseif( $srcSize[0]>$destW && $srcSize[1]>$destH ){    //原圖的寬高大於720x480

        //}

        //$destH_2 = $destW / $srcRatio;            //依比例算出height
        //$destRatio = $srcSize[0] / $destW;        //依設定的width算出原圖比例
        /*if($destH_2<$destH){//依hight為主
        $srcRatio  = $srcSize[1] / $srcSize[0];
        $destW_2 = $destH / $srcRatio;
        $destRatio = $srcSize[1] / $destH;
        //$destH = $destH_2;
        ////echo "大於<br/>";
        //$disX = ($destW_2 - $destW)/2;
        //$disY = ($destH - $destH)/2;
        }else{
        //$destH = $destH_2;
        ////echo "小於<br/>";
        //$disX = ($destW - $destW)/2;
        //$disY = ($destH_2 - $destH)/2;
        }*/

        ////echo "destW = ".$destW."<br/>";
        ////echo "destH = ".$destH."<br/>";
        ////echo "destH_2 = ".$destH_2."<br/>";

        //$srcX = $destW - $disX;
        //$srcY = $destH - $disY;
        //$srcX = ($destW*$srcRatio_B)/2;
        //$srcY = ($destH*$srcRatio_B)/2;

        //$srcW = $dest_W2*$destRatio;
        //$srcH = $destH_2*$destRatio;

        $srcW = $dest_W2;
        $srcH = $destH_2;
        //echo "srcW = ".$srcW."<br>";
        //echo "srcH = ".$srcH."<br>";
        $srcX = ($srcSize[0] - $srcW) / 2;
        $srcY = ($srcSize[1] - $srcH) / 2;
    }
    //echo "<br/>srcRatio = $srcRatio<br/>";
    //echo "destRatio = $destRatio<br>";
    //echo "destW = ".$destW."<br/>";
    //echo "destH = ".$destH."<br/>";
    //echo "srcW = ".$srcW."<br/>";
    //echo "srcH = ".$srcH."<br/>";
    //echo "srcX = ".$srcX."<br/>";
    //echo "srcY = ".$srcY."<br/><br/>";
    //建立影像
    $destImage = imagecreatetruecolor($destW, $destH);
    $destImage = imagecolorallocate($destImage, 233, 14, 91);
    ////echo "destImage = ".$destImage."<br/>";
    //根據檔案格式讀取圖檔
    switch ($srcExtension) {
    case 1:$srcImage = imagecreatefromgif($src);
        break;
    case 2:$srcImage = imagecreatefromjpeg($src);
        break;
    case 3:$srcImage = imagecreatefrompng($src);
        break;
    }

    //取樣縮圖
    /*bool imagecopyresampled ( resource $dst_image , resource $src_image , int $dst_x , int $dst_y , int $src_x , int $src_y , int $dst_w , int $dst_h , int $src_w , int $src_h )
    $dst_image：新建的圖片
    $src_image：需要載入的圖片
    $dst_x：設定需要載入的圖片在新圖中的x坐標
    $dst_y： 設定需要載入的圖片在新圖中的y坐標
    $src_x：設定載入圖片要載入的 區域x坐標
    $src_y ：設定載入圖片要載入的 區域y坐標
    $dst_w：設定載入 的原圖 的寬度（ 在此設置縮放 ）
    $dst_h ：設定載入的原圖的高度（在此設置縮放）
    $src_w：原圖要載入的寬度
    $src_h： 原圖要載入的高度*/
    //imagecopyresampled( 輸出目標檔案, 來源檔案, 目標檔案開始點的x座標, 目標檔案開始點的y座標, 來源檔案開始點的x座標, 來源檔案開始點的y座標, 目標檔案的長度, 目標檔案的高度, 來源檔案的長度, 來源檔案的高度)
    imagecopyresampled($destImage, $srcImage, 0, 0, $srcX, $srcY, $destW, $destH, $srcW, $srcH);

    //輸出圖檔
    switch ($srcExtension) {
    case 1:imagegif($destImage, $dest);
        break;
    case 2:imagejpeg($destImage, $dest, 100);
        break;
    case 3:imagepng($destImage, $dest);
        break;

        //釋放資源
        imagedestroy($destImage);
    }
}

function imagesResize_5($src, $dest, $destW, $destH) {
    if (file_exists($src) && isset($dest)) {
        //取得檔案資訊
        $srcSize = getimagesize($src);

        // 檢查圖片是否有效
        if (!$srcSize || $srcSize[0] == 0 || $srcSize[1] == 0) {
            return false;
        }

        $srcExtension = $srcSize[2];
        $srcRatio = $srcSize[0] / $srcSize[1]; 

        $destH_2 = $destW / $srcRatio;
        $destRatio = $srcSize[0] / $destW;

        if ($destH_2 < $destH) {
            $srcRatio = $srcSize[1] / $srcSize[0];
            $destW_2 = $destH / $srcRatio;
            $destRatio = $srcSize[1] / $destH;
        }

        $srcW = $destW * $destRatio;
        $srcH = $destH * $destRatio;
        $srcX = ($srcSize[0] - $srcW) / 2;
        $srcY = ($srcSize[1] - $srcH) / 2;

        //建立影像
        $destImage = imagecreatetruecolor($destW, $destH);

        //根據檔案格式讀取圖檔
        switch ($srcExtension) {
            case 1: $srcImage = imagecreatefromgif($src); break;
            case 2: $srcImage = imagecreatefromjpeg($src); break;
            case 3: $srcImage = imagecreatefrompng($src); break;
        }

        // --- 修正開始：加入 imagesResize_5 缺少的透明處理 ---
        if ($srcExtension == 3 || $srcExtension == 1) {
            // 關閉混合模式
            imagealphablending($destImage, false);
            // 開啟儲存透明資訊
            imagesavealpha($destImage, true);
            // 建立透明色
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            // 填滿背景
            imagefilledrectangle($destImage, 0, 0, $destW, $destH, $transparent);
        }
        // --- 修正結束 ---

        //取樣縮圖
        imagecopyresampled($destImage, $srcImage, 0, 0, (int)$srcX, (int)$srcY, (int)$destW, (int)$destH, (int)$srcW, (int)$srcH);

        //輸出圖檔
        switch ($srcExtension) {
            case 1:
                imagegif($destImage, $dest);
                break;
            case 2:
                imagejpeg($destImage, $dest, 90);
                break;
            case 3:
                imagepng($destImage, $dest, 9);
                break;
        }

        //釋放資源
        imagedestroy($destImage);
        if(isset($srcImage)) imagedestroy($srcImage);
    }
}
?>