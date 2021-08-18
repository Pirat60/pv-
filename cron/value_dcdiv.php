<?php
##
#
require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';
$conn = connect_to_database();

$istfirst = "db__pv_ist_";
$solldcfirst = "db__pv_dcsoll_";
$dummy = "`db_dummysoll`";
$gview = true;                       #View Group / Single
$timenow = time();

#########################################################
$stampday = date("Y-m-d", time());

$zeitfenster_s1 = 9; //9h
$zeitfenster_e1 = 10; //10h

$zeitfenster_s2 = 13; //13h
$zeitfenster_e2 = 14; //14h

$zeitfenster_s3 = 16; //17h
$zeitfenster_e3 = 18; //18h

$zeitfenster_s4 = 21; //21h
$zeitfenster_e4 = 22; //22h

$aktuellezeit = date("G", time());
echo "START $aktuellezeit ";
if ($aktuellezeit >= $zeitfenster_s1 && $aktuellezeit <= $zeitfenster_e1) {
    $time3h = time() - 14400;
    $time1h = time() - 1800;
    $to = date("Y-m-d H:i", $time1h);
    $from = date("Y-m-d 05:00");#date( "Y-m-d H:i",$time3h);
    $daystep = "1";
} elseif ($aktuellezeit >= $zeitfenster_s2 && $aktuellezeit <= $zeitfenster_e2) {
    $time3h = time() - 14400;
    $time1h = time() - 1800;
    $to = date("Y-m-d H:i", $time1h);
    $from = date("Y-m-d 05:00");#date( "Y-m-d H:i",$time3h);
    $daystep = "2";
} elseif ($aktuellezeit >= $zeitfenster_s3 && $aktuellezeit <= $zeitfenster_e3) {
    $time3h = time() - 14400;
    $time1h = time() - 1800;
    $to = date("Y-m-d H:i", $time1h);
    $from = date("Y-m-d 05:00");#date( "Y-m-d H:i",$time3h);
    $daystep = "3";
} elseif ($aktuellezeit >= $zeitfenster_s4 && $aktuellezeit <= $zeitfenster_e4) {
    $time3h = time() - 14400;
    $time1h = time() - 1800;
    $to = date("Y-m-d H:i", $time1h);
    $from = date("Y-m-d 05:00");#date( "Y-m-d H:i",$time3h);
    $daystep = "4";
} else {
    exit;
}
## START 
$sql1 = "SELECT `id`,`anl_name`,`anl_dbase`,`anl_intnr`,`anl_zeitzone`,`anl_db_unit`,`anl_grupe`,`anl_grupe_dc`,`anl_gr_count`,`anl_zeitzone_ws` FROM `db_anlage`";
$result = $conn->query($sql1);
while ($row = $result->fetch_assoc()) {
    $anlid = $row["id"];
    $anname = $row["anl_name"];
    $anintnr = $row["anl_intnr"];
    $andbase = $row["anl_dbase"];
    $anintzz = $row["anl_zeitzone"];
    $anlgrpcount = $row["anl_gr_count"];
    $anintzzws = $row["anl_zeitzone_ws"];
    $unit = $row["anl_db_unit"];
    $anlhatgrupe = $row["anl_grupe"];
    $anlhatgrupedc = $row["anl_grupe_dc"];
    $dbgrupe = show_data_group($anlid);

    $dcsolldb = "$solldcfirst$anintnr";
    $dbnameist = "$istfirst$anintnr";

    if ($gview == true && $anlhatgrupedc == "Yes") {

        $data = build_actexp_dc_sql($gview, $anlhatgrupedc, $andbase, $dbnameist, $dcsolldb, $from, $to, $anlgrpcount, $dbgrupe, $anintzz, $unit);
        $build_tst = sortArrayByFields($data, array('TS' => SORT_ASC, 'TS' => SORT_ASC));

        foreach ($build_tst as $key => $value) {
            $allwr[] = $value['WR'];
        }

        $maxf = max($allwr);

        $sqlarray = buildarray($build_tst, $anlid, $maxf, $daystep, $stampday);

        if (!empty($sqlarray)) {
            sqldatajet($sqlarray);
        }
        $allwr = array();
    }
}
#
##
function buildarray($build_tst, $anlid, $maxf, $daystep, $stampday)
{
    $xy = count($build_tst);
    $x = $xy - $maxf;

    foreach ($build_tst as $key => $value) {
        $wr = $value['WR'];
        $ACTDC = str_replace(",", " ", explode(':', $value['VAL']));
        $EXPDC = str_replace(",", " ", explode(':', $value['VALEXP']));

        for ($i = 1; $i <= $maxf; $i++) {
            if ($wr == $i) {
                if ($ACTDC[1] > 0) {
                    $sumACTDC += $ACTDC[1];
                    # }
                    # if ($EXPDC[1] > 0){
                    $sumEXPC += $EXPDC[1];
                }
            }
        }
        if ($c >= $x) {
            $divdc = round($sumACTDC * 100 / $sumEXPC - 100, 0);
            if ($divdc > "100") {
                $divdc = "-$divdc";
            }
            $sqlarray[] = Array(
                "SQL" => "INSERT INTO web32_db3.`db_anl_dcdiv` (`stamp`, `anl_id`, `power`, `wr` , `actdc`, `expdc`, `divdc`,`daystep`) VALUES ('$stampday', '$anlid', 'DC', '$wr', '$sumACTDC', '$sumEXPC', '$divdc', '$daystep')",
                "WR" => "$wr",
                "ANLID" => "$anlid",
                "DAYSTEP" => "$daystep",
                "STAMPDAY" => "$stampday",
            );
        }
        $c++;
    }
    return $sqlarray;
}

#
## 
function sqldatajet($sqlarray)
{
    global $conn;
    foreach ($sqlarray as $key => $svalue) {
        $sql = $svalue['SQL'];
        $anlid = $svalue['ANLID'];
        $wr = $svalue['WR'];
        $daystep = $svalue['DAYSTEP'];
        $stampday = $svalue['STAMPDAY'];
        $dup = checkdbentry($anlid, $wr, $daystep, $stampday);
        if ($dup == "0") {
            $conn->query($sql);
        }
    }
}

#
## Prüfen Doppelte Werte
function checkdbentry($anlid, $wr, $daystep, $stampday)
{
    global $conn;
    $queryck = "SELECT count(*) as 'out' FROM web32_db3.`db_anl_dcdiv` WHERE `anl_id` = '$anlid' and `wr` = '$wr' and `daystep` = '$daystep' and `stamp` = '$stampday'";

    $result = $conn->query($queryck);
    while ($row = $result->fetch_assoc()) {
        $out = $row["out"];
    }
    return $out;
}

#	
?>
