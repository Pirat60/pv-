<?php
/**
 * Schreibt ?? Werte in die Datenbank
 *
 * Mark Schäfer
*/

require_once '../pvp_v1/incl/config.php';
require '../pvp_v1/incl/functions.php';

$conn     = connect_to_database();
$first_ws = "db__pv_ws_"; //Tabellename Anfang

$currentTime = g4nTimeCET();
$now      = date("Y-m-d H:i", $currentTime);
$daystart = date("Y-m-d 00:00", $currentTime);

$dbws     = showeignerdb_namews();
$dbist    = showeignerdb_nameist();

// Suche alle Anlagen
$sqlAnlagen    = "SELECT id, anl_name, anl_dbase, anl_leistung, anl_grupe, anl_intnr, anl_zeitzone, anl_db_ws, anl_same_ws FROM db_anlage";
$resultAnlagen = $conn->query($sqlAnlagen);

if ($resultAnlagen->num_rows > 0) {
    while ($rowAnlage = $resultAnlagen->fetch_assoc()) {
        $radiagwout  = "0.00";
        $anid        = $rowAnlage["id"];
        $anname      = $rowAnlage["anl_name"];
        $anintnr     = $rowAnlage["anl_intnr"];
        $andbase     = $rowAnlage["anl_dbase"];
        $anintzz     = $rowAnlage["anl_zeitzone"];
        $ankwp       = $rowAnlage["anl_leistung"];
        $anlhatgrupe = $rowAnlage["anl_grupe"];
        $ansamews    = $rowAnlage["anl_same_ws"];
        $andbws      = $rowAnlage["anl_db_ws"];

        $dbnameist = showdbname($dbist, $anintnr); //Datenbankname ermitteln (Anlage)
        $dbnamews  = showdbname($dbws, $anintnr); //Datenbankname ermitteln (Wetterstation)

        $actout = actViewDatajet($andbase, $dbnameist, $daystart, $now, $anid); // AC Leitung berechnen für

        if ($ansamews == "Yes") {
            $sql22    = "SELECT `anl_dbase`,`anl_intnr` FROM `db_anlage` WHERE `anl_intnr` = '$andbws'";
            $result22 = $conn->query($sql22);
            while ($row22 = $result22->fetch_assoc()) {
                $andbase  = $row22["anl_dbase"];
                $andnr    = $row22["anl_intnr"];
                $dbnamews = "$first_ws$andnr"; # wenn ws die selbe ist sonderfall
            }
        }

        $expout = expViewDatajet($andbase, "1", $dbnamews, $dbnameist, $anid, $daystart, $now);

        $sql_sel_temp = "SELECT `id` FROM `db_anl_view_temp` WHERE `anl_id` = '$anid' ORDER BY `db_anl_view_temp`.`stamp` ASC limit 1";
        echo "$sql_sel_temp<br>";
        $resulta      = $conn->query($sql_sel_temp);
        if ($resulta->num_rows > 0) {
            while ($rowa = $resulta->fetch_assoc()) {
                $id               = $rowa["id"];
                $sql_sel_temp_upd = "UPDATE `db_anl_view_temp` SET `anl_id` = '$anid', `stamp` = '$now', `act_data` = '$actout', `exp_data` = '$expout' WHERE `id` = '$id'";
                $conn->query($sql_sel_temp_upd);
            }
        } else {
            $sql_sel_temp_ins = "INSERT INTO `db_anl_view_temp` (`id`, `anl_id`, `stamp`, `act_data`, `exp_data`) VALUES ('','$anid', '$now', '$actout', '$expout')";
            $conn->query($sql_sel_temp_ins);
        }
    }
}
$resultAnlagen->free();
$conn->close();