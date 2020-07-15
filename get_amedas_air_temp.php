<?php
/* スクレーピング関数群のインクルード */
include( "scrape_func.php" );

/* 変数制限まとめて　使いまわし考慮の為出来るだけここを直すだけにする */
$host_name = "127.0.0.1";
$user_name = "root";
$db_password = "pm#corporate1";
$db_name = "ksfoods";
$table_name = "area_info";
$area_url = "http://www.jma.go.jp/jp/amedas_h/today-34186.html?areaCode=000&groupCode=22";
$area_no = "1";




/* getURL()関数を使用して、ページの生データを取得する。 */
$_rawData = getURL($area_url);



/* 解析しやすいよう、生データを整理する。 */
$_rawData = cleanString( $_rawData );
//echo $_rawData;	//スクレイピングした内容を表示

/* 次は若干ややこしい。　必要な項目の開始部分と終了部分は、事前に
   HTMLから確認してある。　こういったものを利用して必要なデータを取得
   する。 */
/* 要素説明　"<td class=\"time left\">"　時間を特定するのに利用

*/
$_rawData = getBlock( "<td class=\"time left\">","</tr> </table>", $_rawData,false );
// echo "getblockした後" . $_rawData;

/* これで箇条書きに必要な特定データが入手できた。
   ここでは項目を配列化した後、繰り返しで処理を行っている。 */
$_rawData = explode( "</tr>", $_rawData );    //1時間毎に分割




//取得するデータの時刻を求める。
//$what=date( "H", time()+32400 );
// タイムゾーンがずれているので修正:20200207 伊藤
$what=date( "H", time() );
$which=(int)$what;
if($which==0)$which=24;



//echo "<hr>";
$now=0;            //時刻ではない
/* 繰り返しを行いながら、個々の項目を解析する。 */
foreach( $_rawData as $_rawBlock ) {
    //初期化
    //$time=null;
    $temp=null;	//気温
   
    //now=0:$which=1        //ひとつずれてる
    if($now==($which)){
        $_rawBlock = explode( "</td>", $_rawBlock );//2つのデータを配列に格納...余計に1つ要素が出る..
        $str="";
        $null_count=0;
        $num_element=count($_rawBlock)-1;
        for($j=0;$j<$num_element;$j++){
            $_rawBlock[$j]=strip_tags($_rawBlock[$j]);    //余計なタグを除去
            $_rawBlock[$j] = trim( $_rawBlock[$j] );    //空白を除去
            //$_rawBlock[$j] = str_replace('x', '', $_rawBlock[$j])


        //echo $_rawBlock[$j];


    if(!strcmp($_rawBlock[$j],"&nbsp;")){        //"&nbsp;"=空白
       $_rawBlock[$j]=0;        //"&nbsp;"のとき、0を代入
       $null_count++;
    }
    if($j==0){
        //時間
        //$time=$_rawBlock[$j];
        //if($time==24)$time=0;    //24時 => 0時
    }else if($j==1){
        //気温（℃）
        $temp=$_rawBlock[$j];
        $str.=$temp;
    }
    }
    if($null_count==$num_element-1){
        //echo "null!!";
    }
    $sc_time = $_rawBlock[0];
    $sc_temp = $_rawBlock[1];
    $sc_rain = $_rawBlock[2];
    $sc_wind = $_rawBlock[3];
    $sc_wspd = $_rawBlock[4];
    $sc_sun  = $_rawBlock[5];
    //24時→0時として登録する
    if ($sc_time === "24:00:00") {
        $sc_time = "00:00:00";
    }
    $sc_time = $sc_time . ":00:00";
    $sc_date = date('Y-m-d');


    //ＭｙＳＱＬへ接続(DB_HOST, DB_USER, DB_PASS)
    $mysqli = new mysqli ($host_name, $user_name, $db_password, $db_name);
    if ($mysqli->connect_error) {
        echo $mysqli->connect_error;
        exit();
    } else {
        $mysqli->set_charset("utf8");
    }


    // mysql構文1　最新の総雨量データを取得
    $sql = 'select rain_total from ksfoods.area_info where time <> "' . $sc_time . '" order by day desc, time desc limit 1;';
    $rain_total_row = $mysqli->query($sql);
    if (!$rain_total_row) {
        die('select fault'.mysql_error());
    }
    $rain_total_row = $rain_total_row->fetch_array();
    $rain_total_before = $rain_total_row[0];
    echo "直前の総雨量:". $rain_total_before . "\n";                              // 直前の総雨量

    // mysql構文2　当日の降水量データを取得
    $sql = 'select sum(rain_hour) from ksfoods.area_info where day=date(now()) and time <> "' . $sc_time . '";';
    $rain_todayall_row = $mysqli->query($sql);
    if (!$rain_todayall_row) {
        die('select fault'.mysql_error());
    }
    $rain_todayall_row = $rain_todayall_row->fetch_array();
    $rain_todayall = $rain_todayall_row[0];
    $rain_todayall = (float)$rain_todayall + (float)$sc_rain;    //最新の雨量を加算
    echo "当日降水量:". $rain_todayall . "\n";                                  // 当日降水量


    // mysql構文3　総雨量確認と計算リセット用(直前4時間分の降雨量データを取得)
    $sql = 'select sum(rain_hour) from (select rain_hour from ksfoods.area_info order by day desc, time desc limit 4) as subt;';
    $rain_total_row = $mysqli->query($sql);
    if (!$rain_total_row) {
        die('select fault'.mysql_error());
    }
    $rain_total_row = $rain_total_row->fetch_array();
    $rain_total_check = $rain_total_row[0];
    $rain_total = (float)$rain_total_before + (float)$sc_rain;   //総雨量に最新データを加算
    if ((float)$rain_total_check == 0.0) {                       //取得した雨量がゼロなら総雨量リセット
        $rain_total = $sc_rain;
    }
    echo "登録される総雨量:". $rain_total. "\n";


    //登録データ確認
    $sc_date = '"' . $sc_date . '"';
    $sc_time = '"' . $sc_time . '"';

    //mysql構文4 データ登録用
    $sql = "replace into area_info values ( " . $area_no . ", " . $sc_date . ", " . $sc_time . ", " . (float)$sc_temp . ", " . (float)$sc_rain . ", " . (float)$rain_todayall . ", " . (float)$rain_total . ");";

    $mysqli_result = $mysqli->query($sql);
    if (!$mysqli_result) {
        die('insert fault'.mysql_error() . "\n");
    }

    $mysqli->close();        //DB.close();
    }
    $now++;
}
?>
