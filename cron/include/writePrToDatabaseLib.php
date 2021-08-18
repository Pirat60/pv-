<?php
/***************************************
 * green4net.de - Matthias Reinhardt
 * writePrToDatabaseLib.php - 2020/02/17
 * G4N PVplus4.0
 ***************************************/

/**
 * @param $sqlAnlagen
 * @param $currentDateTime
 * @param $dbws
 * @param $dbist
 */
function calcPrAndStoreToDatabse($sqlAnlagen, $currentDateTime, $dbws, $dbist){
    global $conn;
    $today = date("Y-m-d 00:00:00", $currentDateTime);
    $resultAnlagen = $conn->query($sqlAnlagen);
    if ($resultAnlagen->num_rows > 0) {
        while ($row = $resultAnlagen->fetch_assoc()) {
            $radiagwout  = 0.0;
            $anid        = $row["id"];
            $anname      = $row["anl_name"];
            $anintnr     = $row["anl_intnr"];
            $andbase     = $row["anl_dbase"];
            $anintzz     = $row["anl_zeitzone"];
            $unit        = $row["anl_db_unit"];
            $ankwp       = $row["anl_leistung"];
            $anlirchange = $row["anl_ir_change"];
            $anlhatgrupe = $row["anl_grupe"];
            $anlInputDaily = @$row["anl_input_daily"];

            $startTime = date("Y-m-d 00:00", $currentDateTime);
            $endTime = date("Y-m-d 23:59", $currentDateTime);

            echo "Anlage: $anname ($anintnr)<br>";
            echo "Starttime: $startTime - EndTime: $endTime - $anlInputDaily<br>";

            // DB Namen holen
            $dbnameist = showdbname($dbist, $anintnr);
            $dbnamews  = showdbname($dbws, $anintnr);
            $andbasews = DB_ANLAGEN_DATA;
            $actoutpast = actViewDatajet($andbase, $dbnameist, $startTime, $endTime, $anid);
            $expoutpast = expViewDatajet($andbasews, "1", $dbnamews, $dbnameist, $anid, $startTime, $endTime);
            $radiaoben  = radiationViewDatajetTop($andbasews, $dbnamews, $startTime, $endTime, $anid);
            $radiaunten = radiationViewDatajetDown($andbasews, $dbnamews, $startTime, $endTime, $anid);

            // Mittelwert Temp und IR
            $irradiationout = irtempjet($andbasews, $dbnamews, $startTime, $endTime, $anid);
            $panneltempout  = panneltempjet($andbasews, $dbnamews, $startTime, $endTime, $anid);

            $irradiationout = round($irradiationout, 2);
            $panneltempout  = round($panneltempout, 2);

            // PR % Berechnung
            $gwpast = viewsolldata($startTime, $anid); // laden der Monatsdaten für die Berechnung der SOLL WERTE (Gewichtung Pannel)
            $radiagwout = viewradiagwdata("1", $gwpast, $radiaoben, $radiaunten, $anid);

            $t1act = $actoutpast / $ankwp;
            $t1exp = $expoutpast / $ankwp;

            $t2 = $radiagwout / 4 / 1000;

            $eg1act = round($t1act, 2) / round($t2, 2);
            $eg1exp = round($t1exp, 2) / round($t2, 2);

            $actprout = $eg1act * 100;
            $expprout = $eg1exp * 100;
            $actprout = round($actprout);
            $expprout = round($expprout);
            if (true == is_nan($actprout)) $actprout = 0;
            if (true == is_nan($expprout))  $expprout = 0;
            if (true == is_infinite($actprout)) $actprout = 0;
            if (true == is_infinite($expprout))  $expprout = 0;
            $diffout   = $actoutpast - $expoutpast;
            echo "Act: $actoutpast - Exp: $expoutpast<br>";
            $diffpr    = $diffout / $expoutpast;
            $diffprout = round($diffpr * 100,2);
            if (true == is_infinite($diffprout))  $diffprout = 0;
            echo "PR: $diffprout%<br>";

            //erstellt einen Datensatz in Tabelle db_an_prw (PR Werte)
            $dup6 = checkdbentry($anid, $startTime);
            if ( ! $dup6) {
                $sql_sel_pr = "INSERT INTO db_anl_prw (anl_id, pr_stamp, pr_stamp_ist, pr_act, pr_exp, pr_diff, irradiation, pr_diff_poz, pr_act_poz, pr_exp_poz, panneltemp) VALUES ('$anid', '$today', '$startTime', '$actoutpast', '$expoutpast', '$diffout','$irradiationout','$diffprout','$actprout','$expprout','$panneltempout')";
                $conn->query($sql_sel_pr);
            } else {
                $sql_sel_pru = "UPDATE db_anl_prw SET pr_act = '$actoutpast', pr_exp = '$expoutpast', pr_diff = '$diffout', irradiation = '$irradiationout', pr_diff_poz = '$diffprout', pr_act_poz = '$actprout', pr_exp_poz = '$expprout', panneltemp = '$panneltempout' WHERE pr_id = '$dup6'";
                $conn->query($sql_sel_pru);
            }
            echo "END<br><hr>";
        }
    }
    $resultAnlagen->free();
}



/**
 * Prüfen Doppelte Werte
 *
 * @param $anid - Anlagen ID
 * @param $tstamp - Time Stamp
 * @return mixed|string - ID des doppelten Datensatzes
 */
function checkdbentry($anid, $tstamp)
{
    global $conn;
    $out = "";
    $queryck = "SELECT count(*) as `out`, pr_id FROM db_anl_prw WHERE anl_id = '$anid' and pr_stamp_ist = '$tstamp'";
    $result  = $conn->query($queryck);
    while ($row = $result->fetch_assoc()) {
        $out = $row["pr_id"];
    }

    return $out;
}
