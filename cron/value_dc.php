<?php

require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';

$grpmax = 3;                           #Anzahl WR

$from = "2019-05-17 10:00:00";
$to = "2019-05-17 11:00:00";

$dbase = "web32_db3";
$istfirst = "`db__pv_ist_G4NET_01`";
$sollfirst = "`db__pv_soll_G4NET_01`";
$wsfirst = "`db__pv_ws_G4NET_01`";
$solldcfirst = "`db__pv_dcsoll_G4NET_01`";

$tbl_dt = "web32_db2.db_dummysoll";    #Dummytime DB
$tbl_ac_ist = "$dbase.$istfirst";      #Ist DB
$tbl_ac_soll = "$dbase.$sollfirst";    #Soll DB
$gview = true;                         #View Group / Single
$anlhatgrupedc = "No";
#########################################################


$dbgrupe = show_data_group('43');

$data = build_actexp_dc_sql_a($gview, $anlhatgrupedc, $dbase, $istfirst, $solldcfirst, $from, $to, 'No', $dbgrupe, '1', 'kwh');
$build_tst = sortArrayByFields($data, array('TS' => SORT_ASC, 'TS' => SORT_ASC));

$maxf = 3;

if ($gview == true) {
    $out = '"dataProvider": [';
    foreach ($build_tst as $key => $value) {
        if ($value['WR'] == 1) {
            $out .= "{";
            $out .= $value['CAT'];
        }
        $out .= $value['VAL'];
        $out .= $value['VALEXP'];
        if ($value['WR'] >= $maxf) {
            $out .= "},";
        };
    }
} else {
    $out = '"dataProvider": [';
    foreach ($build_tst as $key => $value) {
        $out .= "{";
        $out .= $value['CAT'];
        $out .= $value['VAL'];
        $out .= $value['VALEXP'];
        $out .= "},";
    }
}

echo "$out <br>";

#Baut ein Array ACT DC

function build_actexp_dc_sql_a($gview, $anlhatgrupedc, $andbase, $dbnameist, $dbnamesoll, $from, $to, $anlgrpcount, $dbgrupe, $zz, $unit)
{

    $conn = connect_to_database();
    $e = "1";
    $d = "1";
    if ($gview == true) {
        # Group View #
        foreach ($dbgrupe as $value) {
            $anid = $value["ANLID"];
            $min = $value["GMIN"];
            $max = $value["GMAX"];
            $invnr = $value["INVNR"];
            if ($anlhatgrupedc == "Yes") {
                $sql_a = "SELECT a.stamp, sum(b.soll_pdcwr) as soll FROM (web32_db3.`db_dummysoll` a left JOIN $andbase.$dbnamesoll b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN \"$from\" and \"$to\" and b.wr = \"$invnr\" GROUP by a.stamp";
            } else {
                $sql_a = "SELECT stamp FROM web32_db3.`db_dummysoll` WHERE stamp BETWEEN \"$from\" and \"$to\" GROUP by stamp";
            }

            $resulta = $conn->query($sql_a);
            if ($resulta->num_rows > 0) {
                while ($roa = $resulta->fetch_assoc()) {
                    $stampa = $roa["stamp"];
                    if ($anlhatgrupedc == "Yes") {
                        $soll = $roa["soll"];
                    }
                    $stampstr = strtotime($stampa);
                    $stamps = utc_date($stampstr, $zz); #Zeitvorschub
                    if ($anlgrpcount == "No") {
                        $maxw = "$invnr";
                        $sql_b = "SELECT stamp, sum(wr_pac) as acist, sum(wr_pdc) as dcist, wr_num, inv FROM $andbase.$dbnameist WHERE stamp = \"$stamps\" and inv = \"$invnr\" GROUP by stamp LIMIT 1";
                    } else {
                        $maxw = "$max";
                        $sql_b = "SELECT stamp, sum(wr_pac) as acist, sum(wr_pdc) as dcist, wr_num, inv FROM $andbase.$dbnameist WHERE stamp = \"$stamps\" and wr_num BETWEEN \"$min\" and \"$max\" GROUP by stamp LIMIT 1";
                    }

                    $resultb = $conn->query($sql_b);
                    while ($rob = $resultb->fetch_assoc()) {
                        $acist = $rob["acist"];
                        $dcist = $rob["dcist"];
                    }

                    if ($unit == "w") {
                        $dcist = $dcist / 1000 / 4;
                    }
                    $dctout = round($dcist, 2);
                    if ($anlhatgrupedc == "Yes") {
                        $build_tst[] = Array("MAX" => "$e", "WR" => "$d", "TS" => strtotime($stampa), "CAT" => '"Date": "' . $stampa . '",', "VAL" => '"DC_INV' . $d . '": ' . $dctout . ',', "VALEXP" => '"EXP_INV' . $d . '": ' . $soll . ',',);
                    } else {
                        $build_tst[] = Array("MAX" => "$e", "WR" => "$d", "TS" => strtotime($stampa), "CAT" => '"Date": "' . $stampa . '",', "VAL" => '"DC_INV' . $d . '": ' . $dctout . ',',);
                    }
                    $e++;

                }
            }
            $d++;
        }
        $e = "1";
        $build_tst = sortArrayByFields($build_tst, array('TS' => SORT_ASC, 'TS' => SORT_ASC));
    } else {
        # Single View #

        if ($anlhatgrupedc == "Yes") {
            $sql_a = "SELECT a.stamp, sum(b.soll_pdcwr) as soll FROM (web32_db3.`db_dummysoll` a left JOIN $andbase.$dbnamesoll b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN \"$from\" and \"$to\" GROUP by a.stamp";
        } else {
            $sql_a = "SELECT stamp FROM web32_db3.`db_dummysoll` WHERE stamp BETWEEN \"$from\" and \"$to\" GROUP by stamp";
        }

        echo $sql_a;

        $resulta = $conn->query($sql_a);
        if ($resulta->num_rows > 0) {
            while ($roa = $resulta->fetch_assoc()) {
                $stampa = $roa["stamp"];
                if ($anlhatgrupedc == "Yes") {
                    $soll = $roa["soll"];
                    $soll = round($soll, 2);
                    $expdiff = $soll - $soll * 10 / 100;# -10% good
                    $expdiff = round($expdiff, 2);
                }
                $stampstr = strtotime($stampa);
                $stamps = utc_date($stampstr, $zz);#1 -1
                $sql_b = "SELECT stamp, sum(wr_pac) as acist, sum(wr_pdc) as dcist FROM $andbase.$dbnameist WHERE stamp = \"$stamps\" GROUP by stamp LIMIT 1";
                $resultb = $conn->query($sql_b);
                while ($rob = $resultb->fetch_assoc()) {
                    $acist = $rob["acist"];
                    $dcist = $rob["dcist"];
                }
                if ($unit == "w") {
                    $dcist = $dcist / 1000 / 4;
                }
                $dctout = round($dcist, 2);
                if ($anlhatgrupedc == "Yes") {
                    $build_tst[] = Array("CAT" => '"Date": "' . $stampa . '",', "VAL" => '"ACTUALDC": ' . $dctout . ',', "VALEXP" => '"EXP_BESTDC": ' . $soll . ',"EXP_GOODDC": ' . $expdiff . ',',);
                } else {
                    $build_tst[] = Array("CAT" => '"Date": "' . $stampa . '",', "VAL" => '"ACTUALDC": ' . $dctout . ',',);
                }

            }
        }
    }
	$conn->close();
    return $build_tst;
}

