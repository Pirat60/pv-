<?php
/*
 * Routine um die Wetter Daten von der UP Gmbh abzufragen
 * soll alle 15 Minuten per cron Job gestartet werden
 */

require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';

$conn = connect_to_database();  // DB Connection herstellen

$sql_anlage = "SELECT id, anl_dbase, anl_intnr, anl_data_go_ws, anl_data_wsst, anl_db_ws FROM " . DB_SYSTEM_DATA . ".db_anlage WHERE anl_data_go_ws = 'Yes' ORDER BY anl_intnr ASC";
//echo "$sql_anlage<br>";die;
// Variablen festlegen bzw. generieren aus der db
$timestamp = g4nTimeCET();
$datum = date("ymd", $timestamp);
$dateminus = date('Y-m-d', strtotime($datum . " -1 days"));
$verzeichnis = "http://upgmbh-logstar.de/daten/180927-upls-58/"; //Verzeichnis der Daten bei UP
$first = "db__pv_ws_"; //Tabellename Anfang
$slash = "/";
$dateianfang = "TD";   //Dateianfang Tagesdaten
$dateiendung = ".dat"; //Dateiendung
$trenner = " ";        //Trennzeichen Leer=Tabstop
$sync_massage_ws = "Synchronisation der Messstation Daten";
//Abfrage der Analge aus SQL
$res = $conn->query($sql_anlage);
#
if ($res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $anlid = $row['id'];
        $andbase = $row['anl_dbase'];
        ($row['anl_db_ws'] != '') ? $projectname = $row['anl_db_ws'] : $projectname = $row['anl_intnr'];
        $wsstatus = $row['anl_data_wsst']; #"old/new"

        echo "Anlage: $projectname / $anlid<br>";
        $dbtable = "$first$projectname"; //generierte Table
        $dateiname = "$dateianfang$datum$dateiendung"; //generierte Dateiname

        $urlfile = "$verzeichnis$projectname$slash$dateiname";
        $i = 1;
        $d = 1;
        $e = "1";
        $spalte = [];

        $csvInhalt = file("$urlfile", FILE_SKIP_EMPTY_LINES);

        $check = array_empty($csvInhalt);

        if (!$check) {
            // TODO: erstezen durch 'str_getcsv'
            foreach ($csvInhalt as $inhalt) { // Die Anzahl der Spalten aus Csv Datei ermitteln
                $spalte[$i] = explode($trenner, $inhalt);
                $i++;
            }
            $last = count($spalte);
            $faktor = 1.014;
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
                    $wind = $spalte[$d][17];
                    if ($gi_avg < 0) {
                        $gi_avg = "0";
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = "0";
                    }
                    echo "OLD -> $zeit $date -- $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>";

                    // wenn ein Strahlungswert 0 ist und der andere kleienr als 50 dann setzte beide auf 0
                    // soll positive Strahlungswerte mitten in der nacht verhindern
                    if($gmod_avg == 0 && $gi_avg <= 50) $gi_avg = 0;
                    if($gi_avg == 0 && $gmod_avg <= 50) $gmod_avg = 0;

                    $sql_array[$d] = [
                        "anl_id" => $anlid,
                        "anl_intnr" => $projectname,
                        "stamp" => $sqlstamp,
                        "at_avg" => $at_avg,
                        "pt_avg" => $pt_avg,
                        "gi_avg" => $gi_avg,
                        "gmod_avg" => $gmod_avg,
                        "wind_speed" => $wind
                    ];
                    if ($d == $last) {
                        $sql_sync = "INSERT INTO pvp_synclog (anl_id, anl_intnr, sync_massage) VALUES ('$anlid', '$projectname', '$sync_massage_ws');";
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
                    ($projectname = 'G4NET_25') ? $gi_avg   = round($spalte[$e][13] * $faktor,2) : $gi_avg   = $spalte[$e][13];
                    ($projectname = 'G4NET_25') ? $gmod_avg = round($spalte[$e][10] * $faktor,2) : $gmod_avg = $spalte[$e][10];
                    $wind = $spalte[$e][17];
                    if ($gi_avg < 0) {
                        $gi_avg = "0";
                    }
                    if ($gmod_avg < 0) {
                        $gmod_avg = "0";
                    }
                    echo "NEW -> $zeit $date / $sqlstamp -- $at_avg | $pt_avg | $gi_avg | $gmod_avg | $wind <br>";

                    // wenn ein Strahlungswert 0 ist und der andere kleienr als 50 dann setzte beide auf 0
                    // soll positive Strahlungswerte mitten in der nacht verhindern
                    if($gmod_avg == 0 && $gi_avg <= 50) $gi_avg = 0;
                    if($gi_avg == 0 && $gmod_avg <= 50) $gmod_avg = 0;

                    $sql_array[$e] = [
                        "anl_id" => $anlid,
                        "anl_intnr" => $projectname,
                        "stamp" => $sqlstamp,
                        "at_avg" => $at_avg ,
                        "pt_avg" => $pt_avg,
                        "gi_avg" => $gi_avg,
                        "gmod_avg" => $gmod_avg,
                        "wind_speed" => $wind
                    ];

                    if ($e == $last) {
                        $sql_sync = "INSERT INTO pvp_synclog (anl_id, anl_intnr, sync_massage) VALUES ('$anlid', '$projectname', '$sync_massage_ws');";
                    }
                    $e++;
                }
            }
            $spalte = [];

            foreach ($sql_array as $row) {
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

            $statement = $conn->query($sql_sync);
            $sql_array = [];
            $projectname = "";
            $anlid = "";
            $csvInhalt = "";
        }
        else {
            g4nLog("Import Fehler -- Anlage ID $anlid ($projectname) - $urlfile ", "weather");
        }
    }
    echo "<h3>END Weather import</h3>";
    $res->free();
}

// Starten von weiteren Routinen umd die Datenbanken zu aktualisieren

// Update Dummy DBs
echo "<h1>Start Dummy Data</h1>";
$currentTime = g4nTimeCET();
$start = $currentTime - ($currentTime % 900) - 3600;
echo date('Y-m-d H:i', $start);
$end = g4nTimeCET();
for ($i = $start; $i <= $end; $i += 900) {
    $SQLDate = date("Y-m-d H:i:00", $i);
    echo "INSERT IGNORE INTO web32_db3.db_dummysoll SET anl_id = '0', anl_intnr = 'dummy', stamp = '$SQLDate', grp_id = '1', exp_kwh = '0'<br>";
    $conn->query("INSERT IGNORE INTO web32_db3.db_dummysoll SET anl_id = '0', anl_intnr = 'dummy', stamp = '$SQLDate', grp_id = '1', exp_kwh = '0'");
    $conn->query("INSERT IGNORE INTO web32_db2.db_dummysoll SET anl_id = '0', anl_intnr = 'dummy', stamp = '$SQLDate', grp_id = '1', exp_kwh = '0'");
}

$conn->close();

#
#Prüfen ob ARRAY LEER IST
function array_empty($arr)
{
    foreach ($arr as $val) {
        if ($val != '') { return false; }
    }
    return true;
}
