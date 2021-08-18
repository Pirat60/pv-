<?php
/**
 *  PHP Datei für CronJob zur täglichen Berechnung des PR
 *  Routine zur berechnung ist ausgelagert in 'include' Datei
 */

require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';
require_once 'include/writePrToDatabaseLib.php';

$conn     = connect_to_database();
$yesterdayDateTime  = g4nTimeCET() - (86400); // 86400 = 1 Tag (24*60*60)
$dbws  = showeignerdb_namews();
$dbist = showeignerdb_nameist();

$sqlAnlagen    = "SELECT id, anl_name, anl_dbase, anl_leistung, anl_grupe, anl_intnr, anl_zeitzone, anl_db_unit, anl_ir_change, anl_input_daily FROM db_anlage";
calcPrAndStoreToDatabse($sqlAnlagen, $yesterdayDateTime, $dbws, $dbist);

$sql_cron = "INSERT INTO `pvp_cronlog` (`cron_modul`, `cron_massage`) VALUES ('writePrToDatabase', 'ok')";
$conn->query("$sql_cron");

$conn->close();