<?php
//スクレイピング関数群のインクルード
include( "scrape_func.php" );

//情報
$host_name = "127.0.0.1";
$user_name = "root";
$db_password = "pm#corporate1";
$db_name = "ksfoods";
$table_name = "miyagi_navi_watertemp";
$area_url = "http://www.miyagi-suisan-navi.jp/index.html";

//情報取得
$raw_data = getURL($area_url);                                  //URL指定
$clean_data = cleanString($raw_data);                           //整理

//範囲指定（Start,End)
$clean_data = getBlock('<table class="suion">', '</table>', $clean_data, false);
$clean_data = explode("</tr>", $clean_data);                    //一行ごとに分割

//データ整理
$array_data = $clean_data[2];
$delete_word_1 = array("<tr><td>", "</td>");                    //タグ1
$delete_word_2 = array("<td>", '<td class="num">');             //タグ2
$array_data = str_replace($delete_word_1, "", $array_data);     //タグ1を削除
$array_data = str_replace($delete_word_2, ",", $array_data);    //タグ2をカンマに
$array_data = str_replace("―", "", $array_data);                //ハイフンがあったら削除する
$array_data = explode(",", $array_data);                        //カンマで区切る　[0]日付情報　[3]歌津水温10時　[4]歌津水温15時



//接続処理
$mysqli = new mysqli ($host_name, $user_name, $db_password, $db_name);
    if ($mysqli->connect_error) {
        echo $mysqli->connect_error;
        exit();
    } else {
        $mysqli->set_charset("utf8");
    }

//mysql構文　insertとon duplicateで新規と更新を一文で行う
#$sql = 'update miyagi_navi_watertemp set day=,'. water_temp_10, water_temp_15) values ("'. $array_data[0] . '", "'. $array_data[3] . '", "'. $array_data[4] . '")';
#$sql = 'update miyagi_navi_watertemp set day="'. $array_data[0] . '", water_temp_10="'. $array_data[3] . '", water_temp_15="'. $array_data[4] . '" ';

#$sql = $sql . '") Replace Into update day = '. $array_data[0] . ', water_temp_10 ="' . $array_data[3];
#$sql = $sql . '", water_temp_15 = "'. $array_data[4] . '";';
#$sql=$sql. " where day BETWEEN '".date( "Y/m/d", time())."' and '". date( "Y/m/d", time())."';";
$sql = 'Replace Into miyagi_navi_watertemp (day, water_temp_10, water_temp_15) values ("'. $array_data[0] . '", "'. $array_data[3] . '", "'. $array_data[4] . '");';
#$sql=$sql. ' where day="' . $array_data[0]. '";';

echo $sql;
echo "\n". $sql  . "\n";
$mysqli_result = $mysqli->query($sql);
    if (!$mysqli_result) {
        die('insert fault'.mysql_error() . "\n");
    }

//接続終了
$mysqli->close();
?>