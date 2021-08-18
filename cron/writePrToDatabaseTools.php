<?php
/*
 *   PHP Datei Berechnung des PR aus dem 'Tools' Menü
 *  Routine zur Berechnung ist ausgelagert in 'include' Datei
 *  Aufruf writePrToDatabaseTools.php?aid=22&from=2018-08-27&to=2018-08-30  <- bsp
 */
require_once __DIR__ . '/../pvp_v1/incl/config.php';
require_once __DIR__ . '/../pvp_v1/incl/functions.php';
require_once 'include/writePrToDatabaseLib.php';

echo "-> START<br>";

$anid = $_POST['aid'];
$from = date("U", strtotime($_POST['from']));
if (isset($_POST['to'])) {
    $to   = date("U", strtotime($_POST['to']));
} else {
    $to = $from;
}

if ( ! $to or ! $from) {
    exit;
}
$conn = connect_to_database();

$dbws  = showeignerdb_namews();
$dbist = showeignerdb_nameist();

for ($dayTimeStamp = $from; $dayTimeStamp <= $to; $dayTimeStamp += 86400) {
    $sqlAnlagen    = "SELECT `id`,`anl_name`,`anl_dbase`,`anl_leistung`,`anl_grupe`,`anl_intnr`,`anl_zeitzone`,`anl_db_unit`,`anl_ir_change` FROM `db_anlage` WHERE `id` = '$anid'";
    calcPrAndStoreToDatabse($sqlAnlagen, $dayTimeStamp, $dbws, $dbist);
}
$conn->close();
echo "-> Routine ENDE";
