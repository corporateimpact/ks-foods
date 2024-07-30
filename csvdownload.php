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

//テスト用日付
//$dl_date_from ="2024-07-29";
//$dl_date_to = "2024-07-30";


//ＣＳＶ出力
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=" . str_replace("/", "", $dl_date_from) . "-" . str_replace("/", "", $dl_date_to) . ".csv");

$mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'ksfoods');
$mysqli->set_charset('utf8');

//測定値テーブル抽出クエリ
//$sql = "SELECT data.day, data.time, data.water_temp, data.salinity, data.do, data_ginzake.water_temp,  data_ginzake.do, area_info.temp, area_info.rain_hour FROM data LEFT JOIN area_info ON data.fact_id = area_info.factory_id AND data.day = area_info.day AND data.time = area_info.time LEFT JOIN data_ginzake ON data.day = data_ginzake.day AND data.time = data_ginzake.time WHERE data.day BETWEEN '" . $dl_date_from . "' AND '" . $dl_date_to . "' ORDER BY data.day, data.time";
$sql = "SELECT data.day, data.time, data.water_temp, data.salinity, data.do, data_ginzake.water_temp,  data_ginzake.do, data_ginzake2.water_temp,  data_ginzake2.do, data_poultry.air_temp,  data_poultry.humidity, area_info.temp, area_info.rain_hour FROM data LEFT JOIN area_info ON data.fact_id = area_info.factory_id AND data.day = area_info.day AND data.time = area_info.time LEFT JOIN data_ginzake ON data.day = data_ginzake.day AND data.time = data_ginzake.time LEFT JOIN data_ginzake2 ON data.day = data_ginzake2.day AND data.time = data_ginzake2.time LEFT JOIN data_poultry ON data.day = data_poultry.day AND data.time = data_poultry.time WHERE data.day BETWEEN '" . $dl_date_from . "' AND '" . $dl_date_to . "' ORDER BY data.day, data.time";
$res = $mysqli->query($sql);

// bomをつける
$bom = "\xEF\xBB\xBF";

// ヘッダー作成
$header_str = "\"日付\",\"時刻\",\"うに水温\",\"塩分濃度\",\"うに溶存酸素\",\"銀鮭水温\",\"銀鮭溶存酸素\",\"銀鮭水温2\",\"銀鮭溶存酸素2\",\"養鶏場室温\",\"養鶏場湿度\",\"志津川気温\",\"時間降水量\"\r\n";

// ヘッダにbomを付与して出力
echo $bom . $header_str;

// または↓でSJISエンコード
// echo mb_convert_encoding($header_str, "SJIS", "UTF-8");


while ($row = $res->fetch_array()) {
  print("\"" . $row[0] . "\",\""  //日付
    . $row[1] . "\",\""  //時刻
    . $row[2] . "\",\""  //うに水温
    . $row[3] . "\",\""  //塩分濃度
    . $row[4] . "\",\""  //溶存酸素
    . $row[5] . "\",\""  //銀鮭水温
    . $row[6] . "\",\""  //銀鮭溶存酸素
    . $row[7] . "\",\""  //銀鮭水温2
    . $row[8] . "\",\""  //銀鮭溶存酸素2
    . $row[9] . "\",\""  //養鶏場水温
    . $row[10] . "\",\""  //養鶏場湿度
    . $row[11] . "\",\""  //志津川気温
    . $row[12] . "\"\r\n"); //時間降水量
}



$mysqli->close();
