<?php
/**
 * Berechnung der DC Differenz zwichen Soll (Exp) und Ist (Act)
 * Speichern der Werte nach Datum und 'Zeitraum des Tages' (Vormittag, Mittag, Nachmittag = $daystep) in der Tabelle 'db_anl_dcdiv'
 * Vormittag    = Zeitfenster 1
 * Mittag       = Zeitfenster 2
 * Nachmittag   = Zeitfenster 3
 */
require_once '../incl/config.php';
require_once '../incl/functions/buildCharts.php';
require_once '../incl/functions.php';

echo "<pre>";
$conn = connect_to_database();
$dbSystemData = $GLOBALS['dbSystemData'];
$dbAnlagenData = $GLOBALS['dbAnlagenData'];

$istfirst    = "db__pv_ist_";
$solldcfirst = "db__pv_dcsoll_";
$dummy       = "`db_dummysoll`";
$gview       = true;                       #View Group / Single
$currentTime = g4nTimeCET();// - 0 * 3600;

#########################################################
$stampday = date("Y-m-d", $currentTime);

// Bitte auch CronJob entsprechend anpassen sollte immer um 5 minuten vor 'Zeitfenster Ende' laufen
$zeitfenster_s1 = 5;  //9h
$zeitfenster_e1 = 10; //10h

$zeitfenster_s2 = 10; //13h
$zeitfenster_e2 = 16; //14h

$zeitfenster_s3 = 16; //17h
$zeitfenster_e3 = 21; //18h

$aktuellezeit = date("G", $currentTime);
echo "START $aktuellezeit <br>";
if ($aktuellezeit >= $zeitfenster_s1 AND $aktuellezeit < $zeitfenster_e1) {
    $from    = date("Y-m-d 05:00",$currentTime);
    $to      = date("Y-m-d 09:59", $currentTime);
    $daystep = "1"; // daystep = Zeitfenster
} elseif ($aktuellezeit >= $zeitfenster_s2 AND $aktuellezeit < $zeitfenster_e2) {
    $from    = date("Y-m-d 10:00",$currentTime);
    $to      = date("Y-m-d 15:59", $currentTime);
    $daystep = "2";
} elseif ($aktuellezeit >= $zeitfenster_s3 AND $aktuellezeit < $zeitfenster_e3) {
    $from    = date("Y-m-d 16:00",$currentTime);
    $to      = date("Y-m-d 20:59", $currentTime);
    $daystep = "3";
} else {
    exit;
}
echo "From $from to $to <br>";

## START
$sql1   = "SELECT id, anl_name, anl_dbase, anl_intnr, anl_zeitzone, anl_db_unit, anl_grupe, anl_grupe_dc, anl_gr_count, anl_zeitzone_ws FROM $dbSystemData.db_anlage";
//$sql1   = "SELECT id, anl_name, anl_dbase, anl_intnr, anl_zeitzone, anl_db_unit, anl_grupe, anl_grupe_dc, anl_gr_count, anl_zeitzone_ws FROM $dbSystemData.db_anlage WHERE id = '75'"; // zum Test

$result = $conn->query($sql1);

while ($row = $result->fetch_assoc()) {
    $anlid         = $row["id"];
    $anname        = $row["anl_name"];
    $anintnr       = $row["anl_intnr"];
    $andbase       = $row["anl_dbase"];
    $anintzz       = $row["anl_zeitzone"];
    $anlgrpcount   = $row["anl_gr_count"];
    $anintzzws     = $row["anl_zeitzone_ws"];
    $unit          = $row["anl_db_unit"];
    $anlhatgrupe   = $row["anl_grupe"];
    $anlhatgrupedc = $row["anl_grupe_dc"];

    $dbGroup = show_data_group($anlid); // Gruppen der Anlage in Array $dbGroup

    $dcsolldb  = "$solldcfirst$anintnr";
    $dbnameist = "$istfirst$anintnr";

    //echo "anlID: $anlid - hatGruppe: $anlhatgrupedc - ". count($dbGroup) ."<br>";
    if ($gview == true && $anlhatgrupedc == "Yes") {
        $allwr = [];
        $data      = build_actexp_dc_sql($gview, $anlhatgrupedc, $andbase, $dbnameist, $dcsolldb, $from, $to, $anlgrpcount, $dbGroup, $anintzz, $unit);
        $build_tst = sortArrayByFields($data, array('TS' => SORT_ASC));
        foreach ($build_tst as $key => $value) {
            $allwr[] = $value['WR'];
        }
        $maxf = max($allwr); // ?? Anzahl Inverter / Gruppen
        $sqlarray = buildarray($build_tst, $anlid, $maxf, $daystep, $stampday);
        if ( ! empty($sqlarray)) {
            sqldatajet($sqlarray);
        }
    }
}


echo "<br>Fertig<br></pre>";

######### start functions

/**
 * @param $build_tst
 * @param $anlid
 * @param $maxf
 * @param $daystep
 * @param $stampday
 * @return array
 */
function buildarray($build_tst, $anlid, $maxf, $daystep, $stampday)
{
    global $dbSystemData;
    $xy = count($build_tst);
    $x  = $xy - $maxf;
    $counter = 0;
    $sumActDC = 0;
    $sumExpDC = 0;
    $sqlarray = [];

    foreach ($build_tst as $key => $value) {
        $wr    = $value['WR'];
        $ActDC = str_replace(",", " ", explode(':', $value['VAL']));
        $ExpDC = str_replace(",", " ", explode(':', $value['VALEXP']));
        //echo $ActDC[0] . "  -  " . $ActDC[1] . "  -  " . $value['VAL'] . "<br>";

        for ($i = 1; $i <= $maxf; $i++) {
            if ($wr == $i) {
                if ($ActDC[1] > 0) {
                    $sumActDC += $ActDC[1];
                    $sumExpDC += $ExpDC[1];
                }
            }
        }

        //echo "Sum ActDC: $sumActDC - SumExpDc: $sumExpDC<br>";
        if ($counter >= $x) {
            //echo "Anlagen ID: $anlid | Inverter: $wr -> ACT $sumActDC EXP $sumExpDC <br>";
            $divdc = round(($sumActDC - $sumExpDC) / $sumExpDC * 100, 0);
            if ($divdc > 100) {
                $divdc = $divdc * -1;
            }
            $sqlarray[] = [
                "SQL_INS"  => "INSERT INTO $dbSystemData.db_anl_dcdiv (stamp, anl_id, power, wr , actdc, expdc, divdc, daystep) VALUES ('$stampday', '$anlid', 'DC', '$wr', '$sumActDC', '$sumExpDC', '$divdc', '$daystep')",
                "SQL_UPD"  => "UPDATE $dbSystemData.db_anl_dcdiv SET actdc = '$sumActDC', expdc = '$sumExpDC', divdc = '$divdc' WHERE anl_id = '$anlid' and wr = '$wr' and daystep = '$daystep' and stamp = '$stampday'",
                "WR"       => "$wr",
                "ANLID"    => "$anlid",
                "DAYSTEP"  => "$daystep",
                "STAMPDAY" => "$stampday",
            ];
        }
        $counter++;
    }

    return $sqlarray;
}

/**
 * @param $sqlarray
 * @return bool
 */
function sqldatajet($sqlarray)
{
    global $conn;
    foreach ($sqlarray as $key => $svalue) {
        $sqlIns   = $svalue['SQL_INS'];
        $sqlUpd   = $svalue['SQL_UPD'];
        $anlid    = $svalue['ANLID'];
        $wr       = $svalue['WR'];
        $daystep  = $svalue['DAYSTEP'];
        $stampday = $svalue['STAMPDAY'];
        $dup      = checkdbentry($anlid, $wr, $daystep, $stampday);
        if ($dup == "0") {
            $conn->query($sqlIns);
            //echo "$sqlIns<br>";
        } else {
            $conn->query($sqlUpd);
            //echo "$sqlUpd<br>";
        }
    }
    return true;
}

/**
 * @param $anlid
 * @param $wr
 * @param $daystep
 * @param $stampday
 * @return int|mixed
 */
function checkdbentry($anlid, $wr, $daystep, $stampday)
{
    global $conn, $dbSystemData;
    $out = 0;
    $queryck = "SELECT count(*) as 'out' FROM $dbSystemData.db_anl_dcdiv WHERE anl_id = '$anlid' and wr = '$wr' and daystep = '$daystep' and stamp = '$stampday'";
    $result  = $conn->query($queryck);
    while ($row = $result->fetch_assoc()) {
        $out = $row["out"];
    }
    return $out;
}
