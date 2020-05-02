<?php
session_start();
if (!isset($_SESSION['USER'])) {
  header('Location: http://160.16.239.88/index.php');
  exit;
}

if (isset($_POST['logout'])) {
  session_destroy();
  header('Location: http://160.16.239.88/index.php');
  exit;
}
$org_date = "";
$dateStr = date("Ymd");
$timeStr = date("Hi00");
if (isset($_POST['date'])) {
  if ($_POST['date'] != "") {
    $dateStr = str_replace("/", "", $_POST['date']);
    $org_date = $_POST['date'];
    $timeStr = "000000";
  }
}
if (isset($_GET['date'])) {
  if ($_GET['date'] != "") {
    $dateStr = str_replace("/", "", $_GET['date']);
    $org_date = $_GET['date'];
    $timeStr = "000000";
  }
}
if (isset($_POST['time'])) {
  if ($_POST['time'] != "") {
    $timeStr = str_replace(":", "", $_POST['time']);
  }
}
if (isset($_GET['time'])) {
  if ($_GET['time'] != "") {
    $timeStr = str_replace(":", "", $_GET['time']);
  }
}
if (isset($_POST['line'])) {
  if ($_POST['line'] != "") {
    $writeline = $_POST['line'];
    $writeline = $writeline + 1;
  }
}
if (isset($_GET['line'])) {
  if ($_GET['line'] != "") {
    $writeline = $_GET['line'];
  }
}
if (isset($_POST['txt0'])) {
  if ($_POST['txt0'] != "") {
    $txt0 = $_POST['txt0'];
  }
}
if (isset($_POST['txt1'])) {
  if ($_POST['txt1'] != "") {
    $txt1 = $_POST['txt1'];
  }
}
if (isset($_POST['txt2'])) {
  if ($_POST['txt2'] != "") {
    $txt2 = $_POST['txt2'];
  }
}
if (isset($_POST['txt3'])) {
  if ($_POST['txt3'] != "") {
    $txt3 = $_POST['txt3'];
  }
}
if (isset($_POST['txt4'])) {
  if ($_POST['txt4'] != "") {
    $txt4 = $_POST['txt4'];
  }
}
if (isset($_POST['txt5'])) {
  if ($_POST['txt5'] != "") {
    $txt5 = $_POST['txt5'];
  }
}
if (isset($_POST['txt6'])) {
  if ($_POST['txt6'] != "") {
    $txt6 = $_POST['txt6'];
  }
}
if (isset($_POST['txt7'])) {
  if ($_POST['txt7'] != "") {
    $txt7 = $_POST['txt7'];
  }
}
if (isset($_POST['txt8'])) {
  if ($_POST['txt8'] != "") {
    $txt8 = $_POST['txt8'];
  }
}
if (isset($_POST['txt9'])) {
  if ($_POST['txt9'] != "") {
    $txt9 = $_POST['txt9'];
  }
}
if (isset($_POST['txt10'])) {
  if ($_POST['txt10'] != "") {
    $txt10 = $_POST['txt10'];
  }
}
if (isset($_POST['txt11'])) {
  if ($_POST['txt11'] != "") {
    $txt11 = $_POST['txt11'];
  }
}
if (isset($_POST['txt12'])) {
  if ($_POST['txt12'] != "") {
    $txt12 = $_POST['txt12'];
  }
}
if (isset($_POST['txt13'])) {
  if ($_POST['txt13'] != "") {
    $txt13 = $_POST['txt13'];
  }
}
if (isset($_POST['txt14'])) {
  if ($_POST['txt14'] != "") {
    $txt14 = $_POST['txt14'];
  }
}
if (isset($_POST['txt15'])) {
  if ($_POST['txt15'] != "") {
    $txt15 = $_POST['txt15'];
  }
}
if (isset($_POST['txt16'])) {
  if ($_POST['txt16'] != "") {
    $txt16 = $_POST['txt16'];
  }
}
if (isset($_POST['txt17'])) {
  if ($_POST['txt17'] != "") {
    $txt17 = $_POST['txt17'];
  }
}
if (isset($_POST['txt18'])) {
  if ($_POST['txt18'] != "") {
    $txt18 = $_POST['txt18'];
  }
}
if (isset($_POST['txt19'])) {
  if ($_POST['txt19'] != "") {
    $txt19 = $_POST['txt19'];
  }
}

//該当年
$Yearstr = substr($dateStr, 0, 4);
//該当月
$Monthstr = substr($dateStr, 4, 2);
//該当月末
$lastDate = substr(date('Ymd', strtotime('last day of ' . $Yearstr . $Monthstr . "01")), 6, 2);
$filename = $Yearstr . $Monthstr . ".dat";
$flag = 0;
$dArray;
if (file_exists("/var/www/html/ks-foods/list/" . $filename)) {
  //該当月のデータがあれば
  $data = File("/var/www/html/ks-foods/list/" . $filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $num = 0;
  foreach ($data as $row) {
    $tmp = explode(",", $row);
    $dArray{
      $num} = $tmp;
    $num = $num + 1;
  }
  $flag = 1;
}
$data = array();
$writedata;
for ($i = 1; $i < $lastDate * 2 + 1; $i++) {
  if ($writeline == $i) {
    $writedata .= $txt0 . "," . $txt1 . "," . $txt2 . "," . $txt3 . "," . $txt4 . "," . $txt5 . "," . $txt6 . "," . $txt7 . "," . $txt8 . "," . $txt9 . "," . $txt10 . "," . $txt11 . "," . $txt12 . "," . $txt13 . "," . $txt14 . "," . $txt15 . "," . $txt16 . "," . $txt17 . "," . $txt18 . "," . $txt19 . "\n";
  } else {
    $writedata .= $dArray[$i - 1][0] . "," . $dArray[$i - 1][1] . "," . $dArray[$i - 1][2] . "," . $dArray[$i - 1][3] . "," . $dArray[$i - 1][4] . "," . $dArray[$i - 1][5] . "," . $dArray[$i - 1][6] . "," . $dArray[$i - 1][7] . "," . $dArray[$i - 1][8] . "," . $dArray[$i - 1][9] . "," . $dArray[$i - 1][10] . "," . $dArray[$i - 1][11] . "," . $dArray[$i - 1][12] . "," . $dArray[$i - 1][13] . "," . $dArray[$i - 1][14] . "," . $dArray[$i - 1][15] . "," . $dArray[$i - 1][16] . "," . $dArray[$i - 1][17] . "," . $dArray[$i - 1][18] . "," . $dArray[$i - 1][19] . "\n";
  }
}
file_put_contents("/var/www/html/ks-foods/list/" . $filename, $writedata);
header('Location: ./list.php?date=' . $org_date);
exit();
