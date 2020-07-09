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
$sql = "SELECT data.day, data.time, data.water_temp, data.salinity, data.do, area_info.temp, area_info.rain_hour FROM data LEFT JOIN area_info ON data.fact_id = area_info.factory_id AND data.day = area_info.day AND data.time = area_info.time WHERE data.day BETWEEN '" . $dl_date_from . "' AND '" . $dl_date_to . "' ORDER BY data.day, data.time";

$res = $mysqli->query($sql);

// bomをつける
$bom = "\xEF\xBB\xBF";

// ヘッダー作成
$header_str = "\"日付\",\"時刻\",\"水温\",\"塩分濃度\",\"溶存酸素\",\"志津川気温\",\"時間降水量\"\r\n";

// ヘッダにbomを付与して出力
echo $bom . $header_str;

// または↓でSJISエンコード
// echo mb_convert_encoding($header_str, "SJIS", "UTF-8");


while ($row = $res->fetch_array()) {
  print("\"" . $row[0] . "\",\""  //日付
    . $row[1] . "\",\""  //時刻
    . $row[2] . "\",\""  //水温
    . $row[3] . "\",\""  //塩分濃度
    . $row[4] . "\",\""  //溶存酸素
    . $row[5] . "\",\""  //志津川気温
    . $row[6] . "\"\r\n"); //時間降水量
}



$mysqli->close();
