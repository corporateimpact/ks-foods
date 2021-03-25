<?php

//date_default_timezone_set('Asia/Tokyo');
/* 前日分の気温データを一覧で再取得し、データベースへ再登録する処理 */
#https://www.jma.go.jp/bosai/amedas/data/point/拠点番号/日付_時間帯番号(00, 03, 06, 12, 15, 18, 21).json#
/* 変数制限まとめて　使いまわし考慮の為出来るだけここを直すだけにする */

$host_name = "127.0.0.1";
$user_name = "root";
$db_password = "pm#corporate1";
$db_name = "ksfoods";
$table_name = "area_info";
$area_no = "34186";
$what = date("H", time());
$which = array("00", "01", "02", "03",
               "04", "05", "06", "07",
               "08", "09", "10", "11",
               "12", "13", "14", "15",
               "16", "17", "18", "19",
               "20", "21", "22", "23");

$set_sql_time = array("00:00:00", "01:00:00", "02:00:00", "03:00:00",
                      "04:00:00", "05:00:00", "06:00:00", "07:00:00",
                      "08:00:00", "09:00:00", "10:00:00", "11:00:00",
                      "12:00:00", "13:00:00", "14:00:00", "15:00:00",
                      "16:00:00", "17:00:00", "18:00:00", "19:00:00",
                      "20:00:00", "21:00:00", "22:00:00", "23:00:00");

$set_temp_all = array();
//参照用日付格納
$set_json_date = date("Ymd", strtotime('-1 day', time()));              #アクセスURL用日付(前日)
$set_sql_date = '"'. date("Y-m-d", strtotime('-1 day', time())). '"';   #データ登録用日付(前日)
// 抽出先時刻格納
// 参照する時間を作成して配列に格納する
// 参照するjsonファイル番号を設定,毎日3時間ごとに番号を振られて作成される
for($i = 0; $i < 24; $i++) {
    if(0 <= $which[$i] && $which[$i] < 3) {
        $json_no = "00";
    } elseif (3 <= $which[$i] && $which[$i] < 6) {
        $json_no = "03";
    } elseif (6 <= $which[$i] && $which[$i] < 9) {
        $json_no = "06";
    } elseif (9 <= $which[$i] && $which[$i] < 12) {
        $json_no = "09";
    } elseif (12 <= $which[$i] && $which[$i] < 15) {
        $json_no = "12";
    } elseif (15 <= $which[$i] && $which[$i] < 18) {
        $json_no = "15";
    } elseif (18 <= $which[$i] && $which[$i] < 21) {
        $json_no = "18";
    } elseif (21 <= $which[$i]) {
        $json_no = "21";
    } else {
        $json_no = "00";
        echo "json file set error!";
    }
    $url = "https://www.jma.go.jp/bosai/amedas/data/point/". $area_no. "/". $set_json_date. "_". $json_no. ".json";
    $json_get = file_get_contents($url);
    $json_data = json_decode($json_get, TRUE);
    $set = $json_data[$set_json_date. $which[$i]. "0000"];
    $temp = $set["temp"];
    array_push($set_temp_all, $temp[0]);
    echo $set_sql_date. "\t". $set_sql_time[$i]. "\t". $temp[0]. "\n";
}

//MySQLへ接続（DB_HOST, DB_USER, DB_PASS）
$mysqli = new mysqli ($host_name, $user_name, $db_password, $db_name);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit();
}
else {
    $mysqli->set_charset("utf8");
}

//mysql構文 データ登録用
$i = 0;
for($i = 0; $i < 24; $i++) {
    $sql = 'replace into ' . $table_name . ' values (' .$set_sql_date . ', "' . $set_sql_time[$i] . '", "' . (float)$set_temp_all[$i] . '");';
    $mysqli_result = $mysqli->query($sql);
    if (!$mysqli_result) {
        die('insert fault'.mysql_error() . "\n");
    }
    sleep(0.5);
}

$mysqli->close(); //DB.close();
echo "\n"
?>
