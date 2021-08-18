<?php
/**
 * Schreibt die Soll Daten (DC und AC) (EXPECTED) in die jeweiligen Datenbanken (pv_soll / pv_dcsoll)
 * wir alle 10 Miuten per cron aufgerufen
 */

require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';
require_once 'include/makeDatabaseUpdateLib.php';

$first_soll   = "db__pv_soll_";     //Tabellename Anfang
$first_solldc = "db__pv_dcsoll_";   //Tabellename Anfang
$first_ws     = "db__pv_ws_";       //Tabellename Anfang
$nowt         = g4nTimeCET();
$tsto         = date("Y-m-d H:i:00", $nowt);
$tsfrom       = date("Y-m-d H:i:00", $nowt - (6 * 3600));

// Nur wenn ich mehrer Tage für alle Anlagen nachrechnen möchte
//$tsfrom = date("Y-m-d H:i:00", time() - (3 * 24 * 3600));;
//$tsto = date("Y-m-d H:i:00", time());;

$conn = connect_to_database();

// Abfrage der G4NET InternNr
$sql_check = "SELECT id, anl_name, anl_dbase, anl_grupe, anl_intnr, anl_view, anl_grupe_dc FROM db_anlage";
$resulta   = $conn->query($sql_check);


// Array mit den Datenbanken der Wetterstationen anlegen
$arrayWetterStationen = showeignerdb_namews();
#
$resultb = $conn->query($sql_check);
if ($resultb->num_rows > 0) {
    while ($rowb = $resultb->fetch_assoc()) {
        $aid          = $rowb["id"];
        if ($aid != 84 and $aid != 83 and $aid != 81 and $aid != 80) {
            $andbase      = $rowb["anl_dbase"];
            $anlname = $rowb["anl_intnr"];
            foreach ($arrayWetterStationen as $wetterStation) {
                $intnrws = $wetterStation['anl_intnr'];
                if ($anlname == $intnrws) {
                    $dbnameo   = $wetterStation['db_name'];
                    $andbasews = $wetterStation['db_bank'];
                    $dbnamews  = $dbnameo;
                }
            }

            $dbnamesoll   = "$first_soll$anlname";
            $dbnamesolldc = "$first_solldc$anlname";
            writedata_ac($andbase, $andbasews, $dbnamews, $dbnamesoll, $aid, $anlname, $tsfrom, $tsto);
            //sleep(3);
            writedata_dc($andbase, $andbasews, $dbnamews, $dbnamesolldc, $aid, $anlname, $tsfrom, $tsto);
            //sleep(3);
        }
    }
}
$conn->close();
