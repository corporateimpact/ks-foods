<?php

// タイムアウト時間を変更する
ini_set("max_execution_time", 600);

$dateStr = date("Y/m/d");
$dl_date_from = $dateStr;
if (isset($_POST['date_from'])) {
    if ($_POST['date_from'] != "") {
        $dateStr = str_replace("/", "", $_POST['date_from']);
        $dl_date_from = $_POST['date_from'];
        $timeStr = "000000";
    }
}
if (isset($_GET['date_from'])) {
    if ($_GET['date_from'] != "") {
        $dateStr = str_replace("/", "", $_GET['date_from']);
        $dl_date_from = $_GET['date_from'];
        $timeStr = "000000";
    }
}

$dateStr = date("Y/m/d");
$dl_date_to = $dateStr;
if (isset($_POST['date_to'])) {
    if ($_POST['date_to'] != "") {
        $dateStr = str_replace("/", "", $_POST['date_to']);
        $dl_date_to = $_POST['date_to'];
        $timeStr = "000000";
    }
}
if (isset($_GET['date_to'])) {
    if ($_GET['date_to'] != "") {
        $dateStr = str_replace("/", "", $_GET['date_to']);
        $dl_date_to = $_GET['date_to'];
        $timeStr = "000000";
    }
}


//送信されたfromとtoの日付をチェック
if ($dl_date_to < $dl_date_from) {
    $dummy_date = $dl_date_from;
    $dl_date_from = $dl_date_to;
    $dl_date_to = $dummy_date;
}


//ＣＳＶ出力
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . str_replace("/", "", $dl_date_from) . "-" . str_replace("/", "", $dl_date_to) . ".csv");


$mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'ksfoods');
$mysqli->set_charset('utf8');

//測定値テーブル抽出クエリ
$sql = "SELECT day, am_pm, weather, temp, do, feeding_amount, die_count FROM t_aqua_diary WHERE day BETWEEN '" . $dl_date_from . "' AND '" . $dl_date_to . "' ORDER BY day,time";


$res = $mysqli->query($sql);

// ヘッダー作成
echo "\"日付\",\"AMPM\",\"天候\",\"気温\",\"海水温\",\"溶存酸素(DO),\"給餌量,\"斃死尾数\r\n";


while ($row = $res->fetch_array()) {
    print("\"" . $row[0] . "\",\""  //日付
        . $row[1] . "\",\""  //AMPM
        . $row[2] . "\",\""  //天候
        . $row[3] . "\",\""  //気温
        . $row[4] . "\",\""  //海水温
        . $row[5] . "\",\""  //溶存酸素(DO)
        . $row[6] . "\",\""  //給餌量
        . $row[7] . "\"\r\n"); //斃死尾数
}


$mysqli->close();
