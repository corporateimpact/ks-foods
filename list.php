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
  <title>養殖日誌</title>
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

    function onEdit(n) {
      aForm.action = "edit.php";
      document.forms.aForm.line.value = n;
      aForm.submit();
    }
  </script>
  <style>
    /* 年プルダウンの変更 */
    select.ui-datepicker-year {
      height: 2em !important;
      /* 高さ調整 */
      margin-right: 5px !important;
      /* 「年」との余白設定 */
      width: 70px !important;
      /* 幅調整 */
    }

    /* 月プルダウンの変更 */
    select.ui-datepicker-month {
      height: 2em !important;
      /* 高さ調整 */
      margin-left: 5px !important;
      /* 「年」との余白設定 */
      width: 70px !important;
      /* 幅調整 */
    }

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
          <input type="hidden" name="line" value="">
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
    <table width="100%" class="table1">
      <tr>
        <td rowspan="2" valign="middle" align="center" width="100">日付</td>
        <td rowspan="2" valign="middle" align="center" width="50">AMPM</td>
        <td rowspan="2" valign="middle" align="center" width="70">天候</td>
        <td rowspan="2" valign="middle" align="center" width="50">気温</td>
        <td rowspan="2" valign="middle" align="center" width="50">海水温</td>
        <td rowspan="2" valign="middle" align="center" width="50">DO</td>
        <td colspan="7" valign="middle" align="center">給餌量</td>
        <td colspan="7" valign="middle" align="center">斃死尾数</td>
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
      <?php
      if ($flag == 1) {
        for ($i = 1; $i < $lastDate * 2 + 1; $i++) {
          if ($i % 2 != 0) {
      ?>
            <tr>
              <td rowspan="2"><?php echo substr($dArray[$i - 1][0], 0, 2) . "月" . substr($dArray[$i - 1][0], 2, 2) . "日"; ?></td>
            <?php } else { ?>
            <tr style="background-color:#ccc;">
            <?php } ?>
            <td align="center"><?php echo $dArray[$i - 1][1]; ?></td>
            <?php
            $wather = "";
            switch ($dArray[$i - 1][2]) {
              case 1:
                $wather = "快晴";
                break;
              case 2:
                $wather = "晴れ";
                break;
              case 3:
                $wather = "薄曇り";
                break;
              case 4:
                $wather = "曇り";
                break;
              case 5:
                $wather = "煙霧";
                break;
              case 6:
                $wather = "砂じんあらし";
                break;
              case 7:
                $wather = "地ふぶき";
                break;
              case 8:
                $wather = "霧";
                break;
              case 9:
                $wather = "霧雨";
                break;
              case 10:
                $wather = "雨";
                break;
              case 11:
                $wather = "みぞれ";
                break;
              case 12:
                $wather = "雪";
                break;
              case 13:
                $wather = "あられ";
                break;
              case 14:
                $wather = "ひょう";
                break;
              case 15:
                $wather = "雷";
                break;
            }
            ?>
            <td align="center"><?php echo $wather; ?></td>
            <td align="center"><?php echo $dArray[$i - 1][3]; ?>℃</td>
            <td align="center"><?php echo $dArray[$i - 1][4]; ?>℃</td>
            <td align="center"><?php echo $dArray[$i - 1][5]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][6]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][7]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][8]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][9]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][10]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][11]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][12]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][13]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][14]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][15]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][16]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][17]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][18]; ?></td>
            <td align="right"><?php echo $dArray[$i - 1][19]; ?></td>
            <td><input type="button" value="　編集　" onClick="onEdit(<?php echo $i; ?>);"></td>
            </tr>
            <?php
          }
        } else {
          $writedata;
          for ($i = 1; $i < $lastDate * 2 + 1; $i++) {
            if ($i % 2 != 0) {
              $writedata .= $Monthstr . sprintf("%'.02d", (ceil($i / 2))) . ",AM,,,,,,,,,,,,,,,,,\n";
            ?>
              <tr>
                <td rowspan="2"><?php echo $Monthstr . "月" . sprintf("%'.02d", (ceil($i / 2))) . "日"; ?></td>
                <td align="center">AM</td>
              <?php
            } else {
              $writedata .= $Monthstr . sprintf("%'.02d", (ceil($i / 2))) . ",PM,,,,,,,,,,,,,,,,,\n";
              ?>
              <tr style="background-color:#ccc;">
                <td align="center">PM</td>
              <?php } ?>
              <td></td>
              <td align="right">℃</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td><input type="button" value="　編集　" onClick="onEdit(<?php echo $i; ?>);"></td>
              </tr>
          <?php
          }
          file_put_contents("/var/www/html/ks-foods/list/" . $filename, $writedata);
        }
          ?>
    </table>
  </div>
</body>

</html>