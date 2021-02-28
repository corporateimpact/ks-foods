<?php
//date_default_timezone_set('Asia/Tokyo');

/* 気象庁HPからのスクレイピングが困難になってしまった為JSONから取得する形へ変更 */
#https://www.jma.go.jp/bosai/amedas/data/point/拠点番号/日付_時間帯番号(00, 03, 06, 12, 15, 18, 21).json#
/* 変数制限まとめて　使いまわし考慮の為出来るだけここを直すだけにする */
$host_name = "127.0.0.1";
$user_name = "root";
$db_password = "pm#corporate1";
$db_name = "ksfoods";
$table_name = "area_info";
$area_no = "34186";
$tank_no = "1";
//$what=date( "H", time()+32400 );
// タイムゾーンがずれているので修正:20200207 伊藤
$what = date( "H", time() );
$which = (int)$what;

//参照用日付格納‘
$set_json_date = date("Ymd");
$set_sql_date = '"'. date("Y-m-d"). '"';
$set_sql_time = '"'. $which. ':00:00"';

// 参照するjsonファイル番号を設定(毎日3時間ごとに番号を振られて作成される)
if(0 <= $which && $which <= 3) {
    $json_no = "00";
} elseif (3 <= $which && $which < 3) {
    $json_no = "03";
} elseif (6 <= $which && $which < 9) {
    $json_no = "06";
} elseif (9 <= $which && $which < 12) {
    $json_no = "09";
} elseif (12 <= $which && $which < 15) {
    $json_no = "12";
} elseif (15 <= $which && $which < 18) {
    $json_no = "15";
} elseif (18 <= $which && $which < 21) {
    $json_no = "18";
} elseif (21 <= $which) {
    $json_no = "21";
} else {
    $json_no = "00";
    echo "jsonfile set error!";
}

$url = "https://www.jma.go.jp/bosai/amedas/data/point/". $area_no. "/". $set_json_date. "_". $json_no. ".json";
$json_get = file_get_contents($url);
$json_data = json_decode($json_get, TRUE);
$set = $json_data[$set_json_date. $what. "0000"];
$temp = $set["temp"];
$rain_hour = $set["precipitation1h"];
echo $set_sql_date. "\n";
echo $set_sql_time. "\n";
echo $temp[0]. "\n";
echo $rain_hour[0]. "\n";


//MySQLへ接続（DB_HOST, DB_USER, DB_PASS）
$mysqli = new mysqli ($host_name, $user_name, $db_password, $db_name);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit();
}
else {
    $mysqli->set_charset("utf8");
}


// mysql構文1　最新の積算降水量データを取得（Replese時に追加計算されないように、現在時間は省くようにする）
$sql = 'select day, time, rain_total from ksfoods.area_info where time <> ' . $set_sql_time . ' order by day desc, time desc limit 1;';
$rain_total_row = $mysqli->query($sql);
if (!$rain_total_row) {
    die('select fault'.mysql_error());
}
$rain_total_row = $rain_total_row->fetch_array();
$rain_total_before = $rain_total_row[2];
echo "\n直前の積算降水量:". $rain_total_before . "\n"; // 直前の積算降水量


// mysql構文2　当日の降水量データを取得（Replese時に追加計算されないように、現在時間は省くようにする）
$sql = 'select day, sum(rain_hour) from ksfoods.area_info where day=date(now()) and time <> ' . $set_sql_time . ';';
$rain_todayall_row = $mysqli->query($sql);
if (!$rain_todayall_row) {
    die('select fault'.mysql_error());
}
$rain_todayall_row = $rain_todayall_row->fetch_array();
$rain_todayall = $rain_todayall_row[1];
$rain_todayall = (float)$rain_todayall + (float)$rain_hour[0];    //最新の降水量を加算
echo "当日降水量:". $rain_todayall . "\n";   // 当日降水量


// mysql構文3　積算降水量確認と計算リセット用（直前4時間分の積算降水量データを取得）
$sql = 'select sum(rain_hour) from (select rain_hour from ksfoods.area_info order by day desc, time desc limit 4) as subt;';
$rain_total_row = $mysqli->query($sql);
if (!$rain_total_row) {
    die('select fault'.mysql_error());
}
$rain_total_row = $rain_total_row->fetch_array();
$rain_total_check = $rain_total_row[0];      //前4時間分の時間降水量データ


//積算降水量の更新判定と計算
$rain_total = (float)$rain_total_before + (float)$rain_hour[0];   //直前の積算降水量に最新データを加算
if ((float)$rain_total_check == 0.0) {       //前4時間分の降水量が0の場合、加算せずに新しく取得した時間降水量のみを代入する
    $rain_total = $rain_hour[0];
}
echo "登録される積算降水量:". $rain_total. "\n";


//mysql構文4 データ登録用
$sql = "replace into area_info values ( " . $tank_no . ", " . $set_sql_date . ", " . $set_sql_time . ", " . (float)$temp[0] . ", " . (float)$rain_hour[0] . ", " . (float)$rain_todayall . ", " . (float)$rain_total . ");";
$mysqli_result = $mysqli->query($sql);
if (!$mysqli_result) {
    die('insert fault'.mysql_error() . "\n");
}
$mysqli->close();//DB.close();


?>
