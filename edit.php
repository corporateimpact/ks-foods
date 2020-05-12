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
$org_date = date("Ymd");
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
    $writeline = $writeline - 1;
  }
}
if (isset($_GET['line'])) {
  if ($_GET['line'] != "") {
    $writeline = $_GET['line'];
    $writeline = $writeline - 1;
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
?>
<!DOCTYPE html>
<html>

<head>
  <title>養殖日誌編集</title>
  <meta name="viewport" content="width=device-width">
  <link rel="stylesheet" href="css/jquery-ui.min.css" />

  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="js/chart.js"></script>
  <script src="js/jquery.ui.core.min.js"></script>
  <script src="js/jquery.ui.datepicker.min.js"></script>
  <script src="js/jquery.ui.datepicker-ja.min.js"></script>
  <!--単体フォーム用-->
  <script type="text/javascript">
    $(function() {
      $("#xxdate").datepicker({
        changeYear: true, // 年選択をプルダウン化
        changeMonth: true // 月選択をプルダウン化
      });

      // 日本語化
      $.datepicker.regional['ja'] = {
        closeText: '閉じる',
        prevText: '<前',
        nextText: '次>',
        currentText: '今日',
        monthNames: ['1月', '2月', '3月', '4月', '5月', '6月',
          '7月', '8月', '9月', '10月', '11月', '12月'
        ],
        monthNamesShort: ['1月', '2月', '3月', '4月', '5月', '6月',
          '7月', '8月', '9月', '10月', '11月', '12月'
        ],
        dayNames: ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'],
        dayNamesShort: ['日', '月', '火', '水', '木', '金', '土'],
        dayNamesMin: ['日', '月', '火', '水', '木', '金', '土'],
        weekHeader: '週',
        dateFormat: 'yy/mm/dd',
        firstDay: 0,
        isRTL: false,
        showMonthAfterYear: true,
        yearSuffix: '年'
      };
      $.datepicker.setDefaults($.datepicker.regional['ja']);
    });

    function goMovie() {
      aForm.action = "main.php";
      aForm.submit();
    }

    function onGraph() {
      aForm.action = "graph.php";
      aForm.submit();
    }

    function onList() {
      aForm.action = "list.php";
      aForm.submit();
    }

    function onWrite() {
      document.forms.eForm.line.value = <?php echo $writeline; ?>;
      eForm.action = "write.php";
      eForm.submit();
    }
  </script>
  <style>
    /* 表の罫線 */
    .table1 {
      border-collapse: collapse;
      border: 1px solid;
    }

    .table1 th,
    .table1 td {
      border: 1px solid;
    }
  </style>
</head>

<body>
  <div style="position: absolute;background-color:#FFF;">
    <table border=0 width="100%">
      <td>
        <form action="list.php" method="post" name="aForm">
          <input type="text" name="date" id="xxdate" readonly="readonly" value="<?php echo $org_date; ?>">
          <input type="button" value="　撮影画像　" onClick="goMovie();">
          <input type="button" value="　グラフ　" onClick="onGraph();">
          <input type="button" value="　養殖日誌　" onClick="onList();">
        </form>
      </td>
      <td>
      </td>
      <td align="right">
        <form method="post" action="top.php" target="_top">
          <input type="submit" name="logout" value="ログアウト">
        </form>
    </table>
    <hr>
    <form action="edit.php" method="post" name="eForm">
      <input type="hidden" name="line" value="">
      <input type="hidden" name="date" id="xxdate" readonly="readonly" value="<?php echo $org_date; ?>">
      <table width="100%" class="table1">
        <tr>
          <td rowspan="2" valign="middle" align="center" width="100">日付</td>
          <td rowspan="2" valign="middle" align="center" width="50">AMPM</td>
          <td rowspan="2" valign="middle" align="center" width="100">天候</td>
          <td rowspan="2" valign="middle" align="center" width="50">気温</td>
          <td rowspan="2" valign="middle" align="center" width="50">海水温</td>
          <td rowspan="2" valign="middle" align="center" width="50">DO</td>
          <td colspan="7" valign="middle" align="center">給餌量</td>
          <td colspan="7" valign="middle" align="center">斃死尾量</td>
          <td rowspan="2" valign="middle" align="center">編集ボタン</td>
        </tr>
        <tr>
          <td align="center" width="50">1</td>
          <td align="center" width="50">2</td>
          <td align="center" width="50">3</td>
          <td align="center" width="50">4</td>
          <td align="center" width="50">5</td>
          <td align="center" width="50">6</td>
          <td align="center" width="50">合計</td>
          <td align="center" width="50">1</td>
          <td align="center" width="50">2</td>
          <td align="center" width="50">3</td>
          <td align="center" width="50">4</td>
          <td align="center" width="50">5</td>
          <td align="center" width="50">6</td>
          <td align="center" width="50">合計</td>
        </tr>
        <tr>
          <td rowspan="2"><?php echo substr($dArray[$writeline][0], 0, 2) . "月" . substr($dArray[$writeline][0], 2, 2) . "日"; ?><input type="hidden" name="txt0" value="<?php echo $dArray[$writeline][0]; ?>"></td>
          <td width="20" align="center"><?php echo $dArray[$writeline][1]; ?><input type="hidden" name="txt1" value="<?php echo $dArray[$writeline][1]; ?>"></td>
          <td width="20" align="center"><select name="txt2">
              <?php
              $wetherlist = array("", "快晴", "晴れ", "薄曇り", "曇り", "煙霧", "砂じんあらし", "地ふぶき", "霧", "霧雨", "雨", "みぞれ", "雪", "あられ", "ひょう", "雷");
              for ($i = 0; $i < count($wetherlist); $i++) {
                $attr = $i == $dArray[$writeline][2] ? ' selected' : '';
                echo "<option value=" . $i . $attr . ">" . $wetherlist[$i] . "</option>";
              }
              ?>
            </select></td>
          <?php
          if ($dArray[$writeline][3] != "") {
          ?>
            <td width="20" align="center"><?php echo $dArray[$writeline][3]; ?>℃<input type="hidden" name="txt3" value="<?php echo $dArray[$writeline][3]; ?>"></td>
          <?php
          } else {
            if (file_exists("/var/www/html/jma/" . $Yearstr . substr($dArray[$writeline][0], 0, 2) . substr($dArray[$writeline][0], 2, 2) . ".dat")) {
              $datas = File("/var/www/html/jma/" . $Yearstr . substr($dArray[$writeline][0], 0, 2) . substr($dArray[$writeline][0], 2, 2) . ".dat");
              //$label = $data[0];
              $tmp = explode(",", $datas[1]);
              $temperature = "";
              foreach ($tmp as $row) {
                $temperature_temp .= $row . ",";
              }
            }
            $temperature_Array = explode(",", $temperature_temp);

            if ($dArray[$writeline][1] == "AM") {
              $temperature = $temperature_Array[6];
            } else {
              $temperature = $temperature_Array[13];
            }
          ?>
            <td width="20" align="center"><?php echo $temperature; ?>℃<input type="hidden" name="txt3" value="<?php echo $temperature; ?>"></td>
          <?php
          }
          ?>
          <?php
          if ($dArray[$writeline][4] != "") {
          ?>
            <td width="20" align="center"><?php echo $dArray[$writeline][4]; ?>℃<input type="hidden" name="txt4" value="<?php echo $dArray[$writeline][4]; ?>"></td>
          <?php
          } else {
            if (file_exists("/var/www/html/infos/" . $Yearstr . substr($dArray[$writeline][0], 0, 2) . substr($dArray[$writeline][0], 2, 2) . ".dat")) {
              $data = File("/var/www/html/infos/" . $Yearstr . substr($dArray[$writeline][0], 0, 2) . substr($dArray[$writeline][0], 2, 2) . ".dat");
              foreach ($data as $row) {
                $tmp = explode(",", $row);

                if ($dArray[$writeline][1] == "AM") {
                  $Target_Temp = "07:00:00";
                } else {
                  $Target_Temp = "14:00:00";
                }
                if ($tmp[0] == $Target_Temp) {
                  $water_Temp = $tmp[5];
                  $Do = $tmp[6];
                }
              }
            }
          ?>
            <td width="20" align="center"><?php echo $water_Temp; ?>℃<input type="hidden" name="txt4" value="<?php echo $water_Temp; ?>"></td>
          <?php
          }
          ?>
          <?php
          if ($dArray[$writeline][5] != "") {
          ?>
            <td width="20" align="center"><?php echo $dArray[$writeline][5]; ?><input type="hidden" name="txt5" value="<?php echo $dArray[$writeline][5]; ?>"></td>
          <?php
          } else {
            if (file_exists("/var/www/html/infos/" . $Yearstr . substr($dArray[$writeline][0], 0, 2) . substr($dArray[$writeline][0], 2, 2) . ".dat")) {
              $data = File("/var/www/html/infos/" . $Yearstr . substr($dArray[$writeline][0], 0, 2) . substr($dArray[$writeline][0], 2, 2) . ".dat");
              foreach ($data as $row) {
                $tmp = explode(",", $row);

                if ($dArray[$writeline][1] == "AM") {
                  $Target_Temp = "07:00:00";
                } else {
                  $Target_Temp = "14:00:00";
                }
                if ($tmp[0] == $Target_Temp) {
                  $water_Temp = $tmp[5];
                  $Do = $tmp[6];
                }
              }
            }
          ?>
            <td width="20" align="center"><?php echo $Do; ?><input type="hidden" name="txt5" value="<?php echo $Do; ?>"></td>
          <?php
          }
          ?>
          <td width="20" align="right"><input type="text" name="txt6" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][6]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt7" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][7]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt8" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][8]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt9" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][9]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt10" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][10]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt11" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][11]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt12" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][12]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt13" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][13]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt14" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][14]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt15" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][15]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt16" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][16]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt17" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][17]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt18" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][18]; ?>"></td>
          <td width="20" align="right"><input type="text" name="txt19" size="3" style="ime-mode: disabled;" value="<?php echo $dArray[$writeline][19]; ?>"></td>
          <td><input type="button" value="　登録　" onClick="onWrite();"></td>
        </tr>
      </table>
      <p align="center"><input type="button" value="　戻る　" onClick="onList();"></p>
  </div>
</body>

</html>