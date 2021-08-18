<?php
##
#
require_once('../incl/config.php');
require('../incl/functions.php');

$from = "2019-09-05 10:00";
$to = "2019-09-05 14:00";

$conn = connect_to_database();

$istfirst = "db__pv_ist_";
$solldcfirst = "db__pv_dcsoll_";
$dummy = "`db_dummysoll`";
$gview = "dcall";                       #View Group / Single
$timenow = time();

$sql1 = "SELECT `anl_id`,`anl_name`,`anl_dbase`,`anl_intnr`,`anl_zeitzone`,`anl_db_unit`,`anl_grupe`,`anl_grupe_dc`,`anl_gr_count`,`anl_zeitzone_ws` FROM `db_anlage`";
$result = $conn->query($sql1);
while ($row = $result->fetch_assoc()) {
    $anlid = $row["anl_id"];
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

    echo "<b>G4N $anname ID:$anlid - $from : $to</b><br>";

    if ($anlhatgrupedc == "Yes") {
        $sql_ac = "SELECT w2.wr_num, w2.istdc, w1.solldc, w1.stamp FROM (SELECT a.stamp, sum(b.soll_pdcwr) as solldc, b.wr FROM (web32_db3.`db_dummysoll` a JOIN $andbase.$dcsolldb  b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN \"$from\" and \"$to\" GROUP by b.wr) as w1, (SELECT a.stamp, sum(b.wr_pdc) as istdc, b.wr_num FROM (web32_db3.`db_dummysoll` a JOIN $andbase.$dbnameist b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN \"$from\" and \"$to\" GROUP by b.wr_num) as w2 GROUP by w2.wr_num";
    } else {
        $sql_ac = "SELECT stamp FROM web32_db3.`db_dummysoll` WHERE stamp BETWEEN \"$from\" and \"$to\"";
    }
    $resultac = $conn->query($sql_ac);
    if ($resultac->num_rows > 0) {
        while ($roac = $resultac->fetch_assoc()) {
            $acist = "0.0";
            $dcist = "0.0";
            $stampa = $roac["stamp"];
            $wrsoll = $roac["wr_num"];
            if ($anlhatgrupedc == "Yes") {
                $soll = $roac["solldc"];
                $soll = round($soll, 2);
            }
            $dcist = $roac["istdc"];

            if ($unit == "w") {
                $dcist = $dcist / 1000 / 4;
            } else {
                $dcist = $dcist;
            }
            $dctout = round($dcist, 2);

            $expdiff = ($dctout - $soll) / $soll * 100; # fehler in %

            $expdiff = round($expdiff, 2);
            if ($anlhatgrupedc == "Yes") {
                echo "SOLL_DC : $soll  IST_DC : $dctout<br>";
                echo "WR : $wrsoll  SOLL : $expdiff %<br>";
                $build_tst[] = Array(
                    "CAT" => '"Date":"' . $stampa . '"',
                    "VAL" => '"ACTUALDC":' . $dctout . '',
                    "WR" => '"WR":"STR' . $wrsoll . '"',
                    "VALEXP" => '"EXP_BESTDC":' . $soll . '',
                    "VALUE" => '"VALUE":"' . $expdiff . '"',
                );
            } else {
                $build_tst[] = Array(
                    "CAT" => '"Date":"' . $stampa . '"',
                    "WR" => '"WR":"STR' . $wrsoll . '"',
                    "VALUE" => '"VALUE":' . $dctout . '',
                );
            }
        }

    }

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
    $conn = connect_to_database();
    $result = $conn->query($queryck);
    while ($row = $result->fetch_assoc()) {
        $out = $row["out"];
    }
    return $out;
}