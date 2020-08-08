<?php
ini_set("max_execution_time", 180);
//date_default_timezone_set('Asia/Tokyo');

// session_start();
// if (!isset($_SESSION['USER'])) {
//     header('Location: http://160.16.239.88/index.php');
//     exit;
// }

$dateStr = date("Ymd");
$timeStr = date("Hi00");
$org_date = date("Y/m/d");
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

$org_date2 = $dateStr;
if (isset($_POST['date_from'])) {
    if ($_POST['date_from'] != "") {
        $dateStr = str_replace("/", "", $_POST['date_from']);
        $org_date2 = $_POST['date_from'];
        $timeStr = "000000";
    }
}

if (isset($_GET['date_from'])) {
    if ($_GET['date_from'] != "") {
        $dateStr = str_replace("/", "", $_GET['date_from']);
        $org_date2 = $_GET['date_from'];
        $timeStr = "000000";
    }
}

$org_date3 = $dateStr;
if (isset($_POST['date_to'])) {
    if ($_POST['date_to'] != "") {
        $dateStr = str_replace("/", "", $_POST['date_to']);
        $org_date3 = $_POST['date_to'];
        $timeStr = "000000";
    }
}
if (isset($_GET['date_to'])) {
    if ($_GET['date_to'] != "") {
        $dateStr = str_replace("/", "", $_GET['date_to']);
        $org_date3 = $_GET['date_to'];
        $timeStr = "000000";
    }
}

$dArray;

$max = array_fill(1, 10, -999);     //$maxを[1]～[10]まで-999で埋める
$min = array_fill(1, 10, 999);    //$minを[1]～[10]まで 999で埋める
$data = array();                     //$dataを配列として指定
for ($i = 0; $i < 1440; $i++) {
    $h = str_pad(floor($i / 60), 2, 0, STR_PAD_LEFT);
    $m = str_pad(floor($i % 60), 2, 0, STR_PAD_LEFT);
        if ($m % 10 == 0) {
            if ($m == "00") {
                $label .= "'" . $h . "時',";
            } else {
                $label .= "'',";
            }
    if (isset($dArray{
        $h . $m . "00"})) {
        for ($j = 1; $j < 10; $j++) {
            if (isset($dArray{
                $h . $m . "00"}[$j]) && $dArray{
                $h . $m . "00"}[$j] != "") {
                $data[$j] .= "'" . $dArray{
                    $h . $m . "00"}[$j] . "',";
                if ($max[$j] < $dArray{
                    $h . $m . "00"}[$j]) {
                        $max[$j] = ceil($dArray{
                            $h . $m . "00"}[$j]);
                }
                if ($min[$j] > $dArray{
                    $h . $m . "00"}[$j]) {
                        $min[$j] = floor($dArray{
                            $h . $m . "00"}[$j]);
                }
        } else {
            $data[$j] .= ",";
        }
        }
    } else {
        for ($j = 1; $j < 10; $j++) {
        $data[$j] .= ",";
        }
    }
    }
}


//$set_labels = '00時', '01時', '02時', '03時', '04時', '05時', '06時', '07時', '08時', '09時', '10時', '11時', '12時', '13時', '14時',    '15時', '16時', '17時', '18時', '19時', '20時', '21時', '22時', '23時';
// MySQLより該当日の測定値(平均)を取得（グラフ表示で使用）
$mysqli = new mysqli('localhost', 'root', 'pm#corporate1', 'ksfoods');
$sql = "select substring(date_format(time,'%H:%i:%s'),1,8) AS JIKAN, water_temp, salinity, do from ksfoods.data where day = '";
$sql = $sql . str_replace("/", "-", $org_date);
$sql = $sql . "' group by substring(date_format(time,'%H:%i:%s'),1,8) order by JIKAN";
$res = $mysqli->query($sql);
$water_temp = "";     //水温
$salinity = "";     //塩分濃度
$do = "";             //溶存酸素濃度

$i_next = 0;    //時間　MAX24
$j_next = 0;    //10分毎　MAX5回分（50分）
while ($row = $res->fetch_array()) {
    for ($i = $i_next; $i < 25; $i++) {   //24時まで　
    for ($j = $j_next; $j < 6; $j++) {    //50分まで
        if (substr($row[0], 0, 2) == $i and substr($row[0], 3, 1) == $j) {
        $water_temp = $water_temp . $row[1] . ",";
        $salinity = $salinity . $row[2] . ",";
        $do = $do . $row[3] . ",";
        if ($j == 5) {                    //50分まで来たらゼロにする
            $j_next = 0;
            $i_next = $i + 1;
        } else {
            $j_next = $j + 1;
            $i_next = $i;
        }
        break 2;
        } elseif (substr($row[0], 0, 2) > $i) {
        $water_temp = $water_temp . ",";
        $salinity = $salinity . ",";
        $do = $do . ",";
        if ($j == 5) {                    //50分まで来たらゼロにする
            $j_next = 0;
        }
        } elseif (substr($row[0], 0, 2) >= $i and substr($row[0], 3, 1) > $j) {
        $water_temp = $water_temp . ",";
        $salinity = $salinity . ",";
        $do = $do . ",";
        if ($j == 5) {                    //50分まで来たらゼロにする
            $j_next = 0;
        }
        }
    }
    }
}

// MySQLより最新の測定値情報を取得
$sql = "select * from ksfoods.data order by day desc, time desc limit 1";
$res3 = $mysqli->query($sql);
$row3 = $res3->fetch_array();

// ここで取得した値はグラフ上側の現在値の表示に利用します
$fact_id = $row3[0];
$tank_no = $row3[1];
$day_now = $row3[2];
$time_now = $row3[3];
$water_temp_now = $row3[4];
$salinity_now = $row3[5];
$do_now = $row3[6];


// AMeDASからのデータ処理
$sql2 = "select substring(date_format(time,'%H:%i'),1,4) AS JIKAN, round(temp, 1) as temp, rain_hour, rain_days, rain_total from ksfoods.area_info where day = '";
$sql2 = $sql2 . str_replace("/", "-", $org_date);
$sql2 = $sql2 . "' group by substring(date_format(time,'%H:%i'),1,4) order by JIKAN;";
$res2 = $mysqli->query($sql2);
$air_temp = "";            // 志津川気温
$rain_hour_graph = "";     // 時間降水量
$rain_today_graph = "";    // 日降水量
$rain_total_graph = "";    // 積算降水量

$i_next = 0;
$j_next = 0;
while ($row2 = $res2->fetch_array()) {
    for ($i = $i_next; $i < 25; $i++) {
    for ($j = $j_next; $j < 6; $j++) {
        if (substr($row2[0], 0, 2) == $i and substr($row2[0], 3, 1) == $j) {
        $air_temp = $air_temp . $row2[1] . ",";
        $rain_hour_graph = $rain_hour_graph . $row2[2] . ",";
        $rain_today_graph = $rain_today_graph . $row2[3] . ",";
        $rain_total_graph = $rain_total_graph . $row2[4] . ",";
        if ($j == 5) {
            $j_next = 0;
            $i_next = $i + 1;
        } else {
            $j_next = $j + 1;
            $i_next = $i;
        }
        break 2;
        } elseif (substr($row2[0], 0, 2) > $i) {
        $air_temp = $air_temp . ",";
        $rain_hour_graph = $rain_hour_graph . ",";
        $rain_today_graph = $rain_today_graph . ",";
        $rain_total_graph = $rain_total_graph . ",";
        if ($j == 5) {
            $j_next = 0;
        }
        } elseif (substr($row2[0], 0, 2) >= $i and substr($row2[0], 3, 1) > $j) {
        $air_temp = $air_temp . ",";
        $rain_hour_graph = $rain_hour_graph . ",";
        $rain_today_graph = $rain_today_graph . ",";
        $rain_total_graph = $rain_total_graph . ",";
        if ($j == 5) {
            $j_next = 0;
        }
        }
    }
    }
}

// AMeDASからのデータ回収
$sql2 = "select * from ksfoods.area_info order by day desc, time desc limit 1;";
$res2 = $mysqli->query($sql2);
$row2 = $res2->fetch_array();
$air_temp_now = $row2[3];
$rain_hour_now = $row2[4];
$rain_today_now = $row2[5];
$rain_total_now = $row2[6];


// みやぎ水産naviからのデータ回収
$sql3 = "select * from ksfoods.miyagi_navi_watertemp order by day desc,day desc limit 4;";
$res3 = $mysqli->query($sql3);
$uta_temp_10 = array();
while ($row3 = $res3->fetch_array() ){
    $uta_temp_10[] = $row3[1];
}

// 三日間平均値　表示用の日付処理
$oneday_ago = date('Y-m-d', strtotime('-1 day'));
$twoday_ago = date('Y-m-d', strtotime('-2 day'));
$threeday_ago = date('Y-m-d', strtotime('-3 day'));


// 平均値データの抽出　志津川気温
$sql4 = "select round(avg(temp), 1) as days from ksfoods.area_info group by day order by day desc, time desc limit 4;";
$res4 = $mysqli->query($sql4);
$oldtemp = array();

while ($row4 = $res4->fetch_array() ){
    $oldtemp[] = $row4[0];
}


// 平均値データの抽出 水槽データ
$sql5 = "select round(avg(water_temp), 1) as water_temp, round(avg(salinity), 2) as salinity, round(avg(do), 1) as do from ksfoods.data group by day order by day desc, time desc limit 4;";
$res5 = $mysqli->query($sql5);
$oldwatertemp = array();
$oldsalinity = array();
$olddo = array();
while ($row5 = $res5->fetch_array() ){
    $oldwatertemp[] = $row5[0];
    $oldsalinity[] = $row5[1];
    $olddo[] = $row5[2];
}


// 三日間データの作成　降水量各種（最大値）
$sql6 = "select round(max(rain_hour), 1) as rain_hour, round(max(rain_days), 1) as rain_days, round(max(rain_total), 1) as rain_total from ksfoods.area_info group by day order by day desc, time desc limit 4;";
$res6 = $mysqli->query($sql6);
$oldrain_hour = array();
$oldrain_days = array();
$oldrain_total = array();
while ($row6 = $res6->fetch_array() ){
    $oldrain_hour[] = $row6[0];
    $oldrain_days[] = $row6[1];
    $oldrain_total[] = $row6[2];
}




// 接続終了
$mysqli->close();

// ここまで処理用
?>

<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Refresh" content="60">
    <title>グラフ</title>
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

        $("#xxdate2").datepicker({
        changeYear: true, // 年選択をプルダウン化
        changeMonth: true // 月選択をプルダウン化
        });

        $("#xxdate3").datepicker({
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

    /**
     * メイン画面へ遷移する処理
     */
    function goMovie() {
        aForm.action = "main.php";
        aForm.submit();
    }

    /**
     * グラフ画面に遷移する処理
     */
    function onGraph() {
        aForm.action = "graph.php";
        aForm.submit();
    }

    /**
     * CSVダウンロード処理
     */
    function onDownload() {
        aForm.action = "csvdownload.php";
        aForm.submit();
    }

    /**
     * 養殖日誌画面に遷移する処理
     */
    function onList() {
        aForm.action = "list.php";
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
    </style>

</head>

<body>
    <form action="main.php" method="post" name="aForm">
    <input type="text" name="date" id="xxdate" readonly="readonly" value="<?php echo $org_date; ?>">
    <input type="button" value="　撮影画像　" onClick="goMovie();">
    <input type="button" value="　グラフ　" onClick="onGraph();">
    <!-- <input type="button" value="　養殖日誌　" onClick="onList();"> -->
    <hr>
    <input type="button" value="グラフデータダウンロード" onclick="onDownload();"> <input type="text" name="date_from" id="xxdate2" readonly="readonly" value="<?php echo $org_date; ?>"> ～ <input type="text" name="date_to" id="xxdate3" readonly="readonly" value="<?php echo $org_date; ?>">
    </form>

    <?php echo $org_date; ?>

    <style type="text/css">
    span.abc {
        display: inline-block;
    }
    table {
        text-align: center;
        font-size: 12pt;
        font-weight: bold;
    }
    table th{
        padding : 5px 5px;
    }
    table td{
        padding : 5px 5px;
    }
    th,td{
        border:solid 1px #aaaaaa;
    }
    table tr:nth-child(2){
        background: #fff5e5;
        }
    </style>


    <div align="center">
    <table>
        <tbody>
            <tr>
                <th></th>
                <th>志津川気温</th>
                <th>時間降水量</th>
                <th>日降水量</th>
                <th>積算降水量</th>
                <th>歌津水温<br>(10時)</th>
                <th>水槽水温</th>
                <th>塩分濃度</th>
                <th>溶存酸素濃度</th>
            </tr>
            <tr>
                <td><?php echo $day_now . " " . substr($time, 0, 5) . " 最新"; ?></td>
                <td><?php echo $air_temp_now . "℃"; ?></td>
                <td><?php echo $rain_hour_now . "mm"; ?></td>
                <td><?php echo $rain_today_now . "mm"; ?></td>
                <td><?php echo $rain_total_now . "mm"; ?></td>
                <td><?php echo $uta_temp_10[0] . "℃"; ?></td>
                <td><?php echo $water_temp_now . "℃"; ?></td>
                <td><?php echo $salinity_now . "％"; ?></td>
                <td><?php echo $do_now . "mg/L"; ?></td>
            </tr>
            <tr>
                <td><?php echo $oneday_ago . " 日平均" ?></td>
                <td><?php echo $oldtemp[1] . "℃"; ?></td>
                <td><?php echo $oldrain_hour[1] . "mm"; ?></td>
                <td><?php echo $oldrain_days[1] . "mm"; ?></td>
                <td><?php echo $oldrain_total[1] . "mm"; ?></td>
                <td><?php echo $uta_temp_10[1] . "℃"; ?></td>
                <td><?php echo $oldwatertemp[1] . "℃"; ?></td>
                <td><?php echo $oldsalinity[1] . "％"; ?></td>
                <td><?php echo $olddo[1] . "mg/L"; ?></td>
            </tr>
            <tr>
                <td><?php echo $twoday_ago . " 日平均" ?></td>
                <td><?php echo $oldtemp[2] . "℃"; ?></td>
                <td><?php echo $oldrain_hour[2] . "mm"; ?></td>
                <td><?php echo $oldrain_days[2] . "mm"; ?></td>
                <td><?php echo $oldrain_total[2] . "mm"; ?></td>
                <td><?php echo $uta_temp_10[2] . "℃"; ?></td>
                <td><?php echo $oldwatertemp[2] . "℃"; ?></td>
                <td><?php echo $oldsalinity[2] . "％"; ?></td>
                <td><?php echo $olddo[2] . "mg/L"; ?></td>
            </tr>
            <tr>
                <td><?php echo $threeday_ago . " 日平均" ?></td>
                <td><?php echo $oldtemp[3] . "℃"; ?></td>
                <td><?php echo $oldrain_hour[3] . "mm"; ?></td>
                <td><?php echo $oldrain_days[3] . "mm"; ?></td>
                <td><?php echo $oldrain_total[3] . "mm"; ?></td>
                <td><?php echo $uta_temp_10[3] . "℃"; ?></td>
                <td><?php echo $oldwatertemp[3] . "℃"; ?></td>
                <td><?php echo $oldsalinity[3] . "％"; ?></td>
                <td><?php echo $olddo[3] . "mg/L"; ?></td>
            </tr>
        </tbody>
        </table>
    </div>
    <br>
    <canvas id="myChart1"></canvas>
    <canvas id="myChart2"></canvas>
    <canvas id="myChart3"></canvas>

</body>

</html>
<script>
    var complexChartOption1 = {    // 上側グラフの設定　
    responsive: false,
    maintainAspectRatio: false,
    scales: {
        xAxes: [ // Ｘ軸設定
        {
            display: true,
            barPercentage: 0.8,
            //categoryPercentage: 1.8,
            gridLines: {
            display: false
            },
            ticks: {
            //max: 144,
            //min: 0,
            //stepSize: 60
            },
        }
        ],
        yAxes: [{
            id: "y-axis-1",
            type: "linear",
            position: "left",
            scaleLabel: {
                display: true,
                labelString: "気温（℃）"
            },
            ticks: {
                max: 35,
                min: 0,
                stepSize: 5
            },
            gridLines: {
            drawOnChartArea: true,
            }
        }, {
            id: "y-axis-2",
            type: "linear",
            position: "right",
            scaleLabel: {
                display: true,
                labelString: "水温"
            },
            ticks: {
                max: 25.0,
                min: 5.0,
                stepSize: 5.0
            },
            gridLines: {
                drawOnChartArea: false,
            }
        }],
    }
    };
    var complexChartOption2 = {    //下側グラフの設定
    responsive: false,
    maintainAspectRatio: false,
    scales: {
        xAxes: [ // Ｘ軸設定
        {
            display: true,
            barPercentage: 0.8,
            //categoryPercentage: 1.8,
            gridLines: {
            display: false
            },
        }
        ],
        yAxes: [{
        id: "y-axis-1",
        type: "linear",
        position: "left",
        scaleLabel: {
            display: true,
            labelString: "塩分濃度（％）"
        },
        ticks: {
            max: 5,
            min: 0,
            stepSize: 1
        }
        }, {
        id: "y-axis-2",
        type: "linear",
        position: "right",
        scaleLabel: {
            display: true,
            labelString: "溶存酸素濃度（mg/L）"
        },
        ticks: {
            max: 15.0,
            min: 5.0,
            stepSize: 1.0
        },
        gridLines: {
            drawOnChartArea: false,
        }
        }],
    }
    };
    var complexChartOption3 = {    //降水量グラフの設定
    responsive: false,
    maintainAspectRatio: false,
    scales: {
        xAxes: [ // Ｘ軸設定
        {
            display: true,
            barPercentage: 1.8,
            //categoryPercentage: 1.8,
            gridLines: {
            display: false
            },
        }
        ],
        yAxes: [
            {
            id: "y-axis-1",
            type: "linear",
            position: "left",
            scaleLabel: {
                display: true,
                labelString: "時間降水量（mm）"
            },
            ticks: {
                max: 20,
                min: 0,
                stepSize: 2
            },
            gridLines: {
            drawOnChartArea: true,
            }

            }, {
            id: "y-axis-2",
            type: "linear",
            position: "right",
            scaleLabel: {
                display: true,
                labelString: "日降水量・積算降水量（mm）"
            },
        ticks: {
            max: 40,
            min: 0,
            stepSize: 4
        },
        gridLines: {
            drawOnChartArea: false,
        }
        }],
    }
    };


</script>

<script>
    var ctx = document.getElementById("myChart1").getContext("2d");
    ctx.canvas.width = window.innerWidth - 20;
    ctx.canvas.height = 250;
    var myChart = new Chart(ctx, {
    type: "bar",
    data: {
        labels: [<?php echo $label; ?>],
        datasets: [{
            type: "line",
            label: "水温（℃）",
            data: [<?php echo $water_temp; ?>],
            borderColor: "rgba(0, 255, 255,0.4)",
            backgroundColor: "rgba(0, 255, 255,0.4)",
            fill: false, // 中の色を抜く
            yAxisID: "y-axis-2",
        },
        {
            type: "line",
            label: "志津川気温（℃）",
            data: [<?php echo $air_temp; ?>],
            borderColor: "rgba(255,150,0,0.4)",
            backgroundColor: "rgba(255,150,0,0.4)",
            spanGaps: true,
            fill: false, // 中の色を抜く
            yAxisID: "y-axis-1",
        }
        ]
    },
    options: complexChartOption1
    });

    var ctx = document.getElementById("myChart2").getContext("2d");
    ctx.canvas.width = window.innerWidth - 20;
    ctx.canvas.height = 250;
    var myChart = new Chart(ctx, {
    type: "bar",
    data: {
        labels: [<?php echo $label; ?>],
        datasets: [{
            type: "line",
            label: "塩分濃度（%）",
            data: [<?php echo $salinity; ?>],
            borderColor: "rgba(0, 255, 0,0.4)",
            backgroundColor: "rgba(0, 255, 0,0.4)",
            fill: false, // 中の色を抜く
            yAxisID: "y-axis-1",
        },
        {
            type: "bar",
            label: "溶存酸素濃度（mg/L）",
            data: [<?php echo $do; ?>],
            borderColor: "rgba(128,128,0,0.4)",
            backgroundColor: "rgba(128,128,0,0.4)",
            fill: false, // 中の色を抜く
            yAxisID: "y-axis-2",
        }
        ]
    },
    options: complexChartOption2
    });

    var ctx = document.getElementById("myChart3").getContext("2d");
    ctx.canvas.width = window.innerWidth - 20;
    ctx.canvas.height = 250;
    var myChart = new Chart(ctx, {
    type: "bar",
    data: {
        labels: [<?php echo $label; ?>],
        datasets: [{
            type: "bar",
            label: "時間降水量",
            lineTension: 0,
            data: [<?php echo $rain_hour_graph; ?>],
            borderColor: "rgba(38,0,255,0.9)",
            backgroundColor: "rgba(38,0,255,0.9)",
            fill: false, // 中の色を抜く
            spanGaps: true,
            yAxisID: "y-axis-1",
        },
        {
            type: "line",
            label: "日降水量",
            lineTension: 0,
            data: [<?php echo $rain_today_graph; ?>],
            borderColor: "rgba(38,0,255,0.5)",
            backgroundColor: "rgba(38,0,255,0.2)",
            //fill: false, // 中の色を抜く
            spanGaps: true,
            yAxisID: "y-axis-2",
        },
        {
            type: "line",
            label: "積算降水量",
            lineTension: 0,
            data: [<?php echo $rain_total_graph; ?>],
            borderColor: "rgba(247,153,243,0.8)",
            backgroundColor: "rgba(247,153,243,0.8)",
            //fill: false, // 中の色を抜く
            spanGaps: true,
            yAxisID: "y-axis-2",
        }]
    },
    options: complexChartOption3
    });

</script>