<?php

// ^CAEgΤπΟX·ι
ini_set("max_execution_time",600);

$dateStr = date("Y/m/d");
$dl_date_from = $dateStr;
if(isset($_POST['date_from'])){
	if($_POST['date_from'] != ""){
		$dateStr = str_replace("/","",$_POST['date_from']);
		$dl_date_from = $_POST['date_from'];
		$timeStr = "000000";
	}
}
if(isset($_GET['date_from'])){
	if($_GET['date_from'] != ""){
		$dateStr = str_replace("/","",$_GET['date_from']);
		$dl_date_from = $_GET['date_from'];
		$timeStr = "000000";
	}
}

$dateStr = date("Y/m/d");
$dl_date_to = $dateStr;
if(isset($_POST['date_to'])){
	if($_POST['date_to'] != ""){
		$dateStr = str_replace("/","",$_POST['date_to']);
		$dl_date_to = $_POST['date_to'];
		$timeStr = "000000";
	}
}
if(isset($_GET['date_to'])){
	if($_GET['date_to'] != ""){
		$dateStr = str_replace("/","",$_GET['date_to']);
		$dl_date_to = $_GET['date_to'];
		$timeStr = "000000";
	}
}


//M³κ½fromΖtoΜϊtπ`FbN
if($dl_date_to < $dl_date_from) {
	$dummy_date = $dl_date_from;
	$dl_date_from = $dl_date_to;
	$dl_date_to = $dummy_date;
}


//bruoΝ
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=". str_replace("/","",$dl_date_from) . "-". str_replace("/","",$dl_date_to) . ".csv");


$mysqli = new mysqli ('localhost', 'root', 'pm#corporate1', 'FARM_IoT');
$mysqli->set_charset('utf8');

//ͺθle[uoNG
$sql = "SELECT * FROM farm WHERE DAY BETWEEN '" . $dl_date_from. "' AND '". $dl_date_to. "' ORDER BY day,time";


$res = $mysqli->query($sql);

// wb_[μ¬
echo "\"ϊt\",\"\",\"yλ·x\",\"yλΌx\",\"dC`±x\",\"C·\",\"Όx\"\r\n";


while($row = $res->fetch_array()) {
  print("\"" . $row[0]. "\",\""	//ϊt
             . $row[1]. "\",\""	//
             . $row[2]. "\",\""	//yλ·x
             . $row[3]. "\",\""	//yλΌx
             . $row[4]. "\",\""	//dC`±x
             . $row[5]. "\",\""	//C·
             . $row[6]. "\"\r\n");//Όx
}


$mysqli->close();

?>
