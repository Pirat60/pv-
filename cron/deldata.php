<?php
// -- Mark Schäfer --
#deldata.php?pwd=12345&db=ws/ist/soll&from=2018-12-04&to=2018-12-04
require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';
$conn = connect_to_database();  // DB Connection herstellen
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error);} //Error Handling
$sql_anlage = "SELECT `anl_id`,`anl_dbase`,`anl_intnr`, `anl_data_go_ws`,`anl_data_wsst` FROM `db_anlage` WHERE anl_data_go_ws = 'Yes'";

$addminstart = " 00:00";
$addminend = " 23:59";
$from = $_GET['from'];
$to = $_GET['to'];
$pass = $_GET['pwd'];
$dbb = $_GET['db'];
$start = "$from$addminstart";
$ende = "$to$addminend";

$res = $conn->query($sql_anlage);
$first_ist = "db__pv_ist_";   //Tabellename Anfang
$first_soll = "db__pv_soll_"; //Tabellename Anfang
$first_ws = "db__pv_ws_";    //Tabellename Anfang

if ($pass == "12345"){
if ($res->num_rows > 0){	
while($row = $res->fetch_assoc()) {
#
$anlid = $row['anl_id'];
$andbase = $row['anl_dbase'];
$anintnr = $row['anl_intnr'];
$wsstatus = $row['anl_data_wsst']; #"old/new"
#
$dbnamews = "$first_ws$anintnr"; 
$dbnameist="$first_ist$anintnr";
$dbnamesoll="$first_soll$anintnr";
#
  if ($dbb == "ws"){
  $sqldelwsdata = "DELETE FROM $andbase.`$dbnamews` WHERE `stamp` BETWEEN \"$start\" and \"$ende\"";
  $do = $conn->query($sqldelwsdata);
  echo "Del from $andbase.`$dbnamews` $start - $ende<br>";
  }
  if ($dbb == "ist"){
  $sqldelwsdata = "DELETE FROM $andbase.`$dbnameist` WHERE `stamp` BETWEEN \"$start\" and \"$ende\"";
  $do = $conn->query($sqldelwsdata);
  echo "Del from $andbase.`$dbnameist` $start - $ende<br>";
  }
  if ($dbb == "soll"){
  $sqldelwsdata = "DELETE FROM $andbase.`$dbnamesoll` WHERE `stamp` BETWEEN \"$start\" and \"$ende\"";
  $do = $conn->query($sqldelwsdata);
  echo "Del from $andbase.`$dbnamesoll` $start - $ende<br>";
  }
  }
 }
}
#ENDE
?>