<?php
// -- Mark Schäfer -- Nachtragen WS Daten
# Aufruf -> updatasqlsingle.php?aid=22&from=2018-08-27
//
require_once __DIR__ . '/../pvp_v1/incl/config.php';
require_once __DIR__ . '/../pvp_v1/incl/functions.php';


$conn = connect_to_database();  // DB Connection herstellen

//$suchdatum = $_GET['from'];
//$aid = $_GET['aid'];

$aid = $_POST['aid'];
$suchdatum = $_POST['from'];

$aid = 39;
$suchdatum = '2020-09-07';

echo "->START <br>$aid";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} //Error Handling

// Variablen festlegen bzw. generieren aus der db
$timestamp = strtotime($suchdatum);
$datum = date("ymd", $timestamp);
#$dateminus = date('Y-m-d',strtotime($datum." -1 days"));
$verzeichnis = "http://upgmbh-logstar.de/daten/180927-upls-58/"; //Verzeichnis der Daten bei UP
$first = "db__pv_ws_"; //Tabellename Anfang
$slash = "/";
$dateianfang = "TD";   //Dateianfang Tagesdaten
$dateiendung = ".dat"; //Dateiendung
$trenner = " ";        //Trennzeichen Leer=Tabstop
$sync_massage_ws = "Synchronisation der Messstation Daten";
//Abfrage der Anlage aus SQL
if ($aid == 0) {
    $sql_anlage = "SELECT id, anl_dbase, anl_intnr, anl_data_go_ws, anl_data_wsst, anl_db_ws FROM " . DB_SYSTEM_DATA . ".db_anlage WHERE anl_data_go_ws = 'Yes' ORDER BY anl_intnr ASC";
} else {
    $sql_anlage = "SELECT id, anl_dbase, anl_intnr, anl_data_go_ws, anl_data_wsst, anl_db_ws FROM " . DB_SYSTEM_DATA . ".db_anlage WHERE id = $aid";
}
$res = $conn->query($sql_anlage);
echo "ANZAHL DATENSätze: ".$res->num_rows;
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        #
        $anlid = $row['id'];
        $andbase = $row['anl_dbase'];
        $anlageWeatherStationDb = ($row['anl_db_ws'] != '') ? $row['anl_db_ws']: $row['anl_intnr']; //Hier sollte jetzt die zugehörige Wetter Datenbank drin stehen.

        $projectname = $row['anl_intnr'];
        $wsstatus = $row['anl_data_wsst']; #"old/new"
        #
        $dbtable = "$first$anlageWeatherStationDb"; //generierte Table
        $dateiname = "$dateianfang$datum$dateiendung"; //generierte Dateiname#
        $urlfile = "$verzeichnis$anlageWeatherStationDb$slash$dateiname";
        echo "->ID $anlid <br>->URL $urlfile <br>";
#
        $i = "1";
        $d = "1";
        $e = "1";

        $csvInhalt = file($urlfile, FILE_SKIP_EMPTY_LINES);

        //echo  "$csvInhalt<br>";
        $check = array_empty($csvInhalt);
#
        if (!$check) {
            foreach ($csvInhalt as $inhalt) {
// Die Anzahl der Spalten aus Csv Datei ermitteln
                $spalte[$i] = explode($trenner, $inhalt);
                $i++;
            }

            $last = count($spalte);
// Zuordnung der cvs daten in Variablen.
            if ($wsstatus == "old") {
                foreach ($spalte as $out) {
                    $zeit = $spalte[$d][1];
                    $date = $spalte[$d][2];
                    $sqlstamp = '20' . substr($date,6,2) . '-' . substr($date, 3,2) . '-' . substr($date, 0,2) . " $zeit";
                    $at_avg = $spalte[$d][4];
                    $pt_avg = $spalte[$d][7];
                    $gi_avg = $spalte[$d][10];
                    $gmod_avg = $spalte[$d][13];
                    $wind = @$spalte[$d][17];
                    if ($gi_avg < 0) {
                        $gi_avg = "0";
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = "0";
                    }
                    echo "->Format OLD ->DATA $sqlstamp - $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>";
                    $sql_array[$d] = array("anl_id" => $anlid, "anl_intnr" => $projectname, "stamp" => $sqlstamp, "at_avg" => $at_avg, "pt_avg" => $pt_avg, "gi_avg" => $gi_avg, "gmod_avg" => $gmod_avg, "wind_speed" => $wind);
                    #
                    if ($d == $last) {
                        $sql_sync .= " VALUES ('$anlid', '$projectname', '$sync_massage_ws');";
                    }
                    $d++;
                }
            } else {
                foreach ($spalte as $out) {
                    $zeit = $spalte[$e][1];
                    $date = $spalte[$e][2];
                    $sqlstamp = '20' . substr($date,6,2) . '-' . substr($date, 3,2) . '-' . substr($date, 0,2) . " $zeit";
                    $at_avg = $spalte[$e][4];
                    $pt_avg = $spalte[$e][7];
                    $gi_avg = $spalte[$e][13];
                    $gmod_avg = $spalte[$e][10];
                    $wind = @$spalte[$e][17];
                    if ($gi_avg < 0) {
                        $gi_avg = "0";
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = "0";
                    }
                    echo "->Format NEW ->DATA $sqlstamp - $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>";
                    $sql_array[$e] = array("anl_id" => $anlid, "anl_intnr" => $projectname, "stamp" => $sqlstamp, "at_avg" => $at_avg, "pt_avg" => $pt_avg, "gi_avg" => $gi_avg, "gmod_avg" => $gmod_avg, "wind_speed" => $wind);
                    #
                    if ($e == $last) {
                        //$sql_sync .= " VALUES ('$anlid', '$projectname', '$sync_massage_ws');";
                    }
                    $e++;
                }
            }

            $spalte = array(); #ARRAY zurück
            foreach($sql_array as $row) {
                $anlId = $row['anl_id'];
                $anlIntNr = $row['anl_intnr'];
                $stamp = $row['stamp'];
                $at_avg = str_replace(',', '.', $row['at_avg']);
                $pt_avg = str_replace(',', '.', $row['pt_avg']);
                $gi_avg = str_replace(',', '.', $row['gi_avg']);
                $gmod_avg = str_replace(',', '.', $row['gmod_avg']);
                $wind_speed = str_replace(',', '.', $row['wind_speed']);
                $sql_insert =  "INSERT INTO $andbase.$dbtable 
                                SET 
                                    anl_id = '$anlId', anl_intnr = '$anlIntNr', stamp = '$stamp', 
                                    at_avg = '$at_avg', pt_avg = '$pt_avg', gi_avg = '$gi_avg', gmod_avg = '$gmod_avg', wind_speed = '$wind_speed' 
                                ON DUPLICATE KEY UPDATE  
                                    at_avg = '$at_avg', pt_avg = '$pt_avg', gi_avg = '$gi_avg', gmod_avg = '$gmod_avg', wind_speed = '$wind_speed' ";

                $conn->query($sql_insert);
            }

           
            $sql_array = array();
            $projectname = "";
            $anlid = "";
            $csvInhalt = "";
            echo "-> Runde ENDE <br>";
        } else {
            echo "->NO DATA";
        }
    }
}
$conn->close();
echo "-> Routine ENDE";
#
#Prüfen ob ARRAY LEER IST
function array_empty($arr)
{
    if (empty($arr)) return false;
    foreach($arr as $val) {
        if ($val != '')
            return false;
    }
    return true;
}
