<?php
/**
 * Lade nochmal alle Wetter Daten von UP, des vergangenen Tages nach
 * (zum schließen von Lücken in den Wetterdaten)
 *
 * Wird um 4 Ihr Nachts per cronjob aufgerufen
 */

require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';

$conn     = connect_to_database();  // DB Connection herstellen
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} //Error Handling
$sql_anlage = "SELECT `anl_id`,`anl_dbase`,`anl_intnr`, `anl_data_go_ws`,`anl_data_wsst` FROM `db_anlage` WHERE `anl_data_go_ws`=\"Yes\"";
#
$datum           = date('ymd', time() - (60 * 60 * 24));
$verzeichnis     = "https://upgmbh-logstar.de/daten/180927-upls-58/"; //Verzeichnis der Daten bei UP
$first           = "db__pv_ws_"; //Tabellename Anfang
$slash           = "/";
$dateianfang     = "TD";   //Dateianfang Tagesdaten
$dateiendung     = ".dat"; //Dateiendung
$trenner         = " ";        //Trennzeichen Leer
$sync_massage_ws = "Synchronisation der Messstation Daten";
//Abfrage der Analge aus SQL
$res = $conn->query($sql_anlage);
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
#
        $anlid       = $row['anl_id'];
        $andbase     = $row['anl_dbase'];
        $projectname = $row['anl_intnr'];
        $wsstatus    = $row['anl_data_wsst']; #"old/new"
        $dbtable     = "$first$projectname"; //generierte Table
        $dateiname   = "$dateianfang$datum$dateiendung"; //generierte Dateiname
        $urlfile     = "$verzeichnis$projectname$slash$dateiname";
        $i           = "1";
        $d           = "1";
        $e           = "1";
#
        $csvInhalt = file("$urlfile", FILE_SKIP_EMPTY_LINES);
        $check     = array_empty($csvInhalt);
#
        if ( ! $check) {
            $sql_sync   = "INSERT INTO `pvp_synclog` (`anl_id`, `anl_intnr`, `sync_massage`)";
            $sql_last   = "SELECT `stamp` FROM $andbase.`$dbtable` ORDER BY db_id DESC";
            $sql_tbl    = "SHOW TABLES FROM $andbase LIKE '$dbtable'";
            $sql_insert = "INSERT IGNORE INTO $andbase.`$dbtable` (anl_id, anl_intnr, stamp,at_avg, pt_avg, gi_avg, gmod_avg, wind_speed) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
#
// Beginn der Tabellen Abfrage
            $result = $conn->query($sql_tbl);
            if ($result->num_rows > 0) {
                $istdb = "true";
            }
#
#
            foreach ($csvInhalt as $inhalt) {
// Die Anzahl der Spalten aus Csv Datei ermitteln
                $spalte[$i] = explode($trenner, $inhalt);
                $i++;
            }
#  
            $last = count($spalte);
#
            if ($wsstatus == "old") {
                //g4nLog("$projectname - $anlid -> $urlfile\r\n");
                foreach ($spalte as $out) {
                    $zeit     = $spalte[$d][1];
                    $date     = $spalte[$d][2];
                    $sqldate  = date('Y-m-d', time() - (60 * 60 * 24));
                    $sqlstamp = "$sqldate $zeit";
                    $at_avg   = $spalte[$d][4];
                    $pt_avg   = $spalte[$d][7];
                    $gi_avg   = $spalte[$d][10];
                    $gmod_avg = $spalte[$d][13];
                    $wind     = $spalte[$d][17];
                    if ($gi_avg < 0) {
                        $gi_avg = "0";
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = "0";
                    }
                    echo "OLD -> $sqlstamp -- $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>";
                    $sql_array[$d] = array("anl_id"     => $anlid,
                                           "anl_intnr"  => $projectname,
                                           "stamp"      => $sqlstamp,
                                           "at_avg"     => $at_avg,
                                           "pt_avg"     => $pt_avg,
                                           "gi_avg"     => $gi_avg,
                                           "gmod_avg"   => $gmod_avg,
                                           "wind_speed" => $wind
                    );
                    #
                    if ($d == $last) {
                        $sql_sync .= " VALUES ('$anlid', '$projectname', '$sync_massage_ws');";
                    }
                    $d++;
                }
            } else {
                foreach ($spalte as $out) {
                    $zeit     = $spalte[$e][1];
                    $date     = $spalte[$e][2];
                    $sqldate  = date('Y-m-d', time() - (60 * 60 * 24));
                    $sqlstamp = "$sqldate $zeit";
                    $at_avg   = $spalte[$e][4];
                    $pt_avg   = $spalte[$e][7];
                    $gi_avg   = $spalte[$e][13];
                    $gmod_avg = $spalte[$e][10];
                    $wind     = $spalte[$e][17];
                    if ($gi_avg < 0) {
                        $gi_avg = "0";
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = "0";
                    }
                    //g4nLog("NEW ->  $sqlstamp -- $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>");
                    $sql_array[$e] = array("anl_id"     => $anlid,
                                           "anl_intnr"  => $projectname,
                                           "stamp"      => $sqlstamp,
                                           "at_avg"     => $at_avg,
                                           "pt_avg"     => $pt_avg,
                                           "gi_avg"     => $gi_avg,
                                           "gmod_avg"   => $gmod_avg,
                                           "wind_speed" => $wind
                    );
                    #
                    if ($e == $last) {
                        $sql_sync .= " VALUES ('$anlid', '$projectname', '$sync_massage_ws');";
                    }
                    $e++;
                }
            }
#
            $spalte   = array();
            $stmt     = $conn->prepare($sql_insert);
            $logdaten .= "$sql_insert \r\n";
            foreach ($sql_array as $row) {
                $f1a = str_replace(',', '.', $row['at_avg']);
                $f1  = str_replace(',', '.', $row['pt_avg']);
                $f2  = str_replace(',', '.', $row['gi_avg']);
                $f3  = str_replace(',', '.', $row['gmod_avg']);
                $f4  = str_replace(',', '.', $row['wind_speed']);
                $stmt->bind_param('ssssssss', $row['anl_id'], $row['anl_intnr'], $row['stamp'], $f1a, $f1, $f2, $f3,
                    $f4);
                $stmt->execute();
            }
            $statement   = $conn->query("$sql_sync");
            $sql_array   = array();
            $projectname = "";
            $anlid       = "";
            $csvInhalt   = "";
        }
    }
    $result->free();
}
$conn->close();


#Prüfen ob ARRAY LEER IST
function array_empty($arr)
{
    foreach ($arr as $val) {
        if ($val != '') {
            return false;
        }
    }

    return true;
}

#ENDE
?>