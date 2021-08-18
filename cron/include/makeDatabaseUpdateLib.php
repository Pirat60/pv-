<?php
/// Include Datei für alle Funktionen die für das Updaten von Datenbanken genutzt werden.
///  -- make_dbup (und seine derivate) zum erzeugen der Soll Werte AC und DC
///


// Funktionen zum schreibt der Daten für AC
function writedata_ac($andbase, $andbasews, $dbnamews, $dbnamesoll, $aid, $anlinnum, $from, $to ) {
    global $conn;

    $view = "true";
    echo "-> Start 2 AC ($dbnamesoll)<br>";
    $array_exp = expViewDatajet($andbasews, $view, $dbnamews, "", $aid, $from, $to);
    if (is_array($array_exp)) {
        foreach ($array_exp as $valu) {
            $exstamp  = $valu["WSTAMP"];
            $exgruppe = $valu["GRPID"];
            $vorexp   = $valu["EXP"];

            $dup6 = checkdbentry_ac($andbase, $aid, $exstamp, $dbnamesoll, $exgruppe);
            if ( ! $dup6) {
                $sql_sel_ins = "INSERT INTO $andbase.$dbnamesoll SET `anl_id` = '$aid', `stamp` = '$exstamp', `anl_intnr` = '$anlinnum', `grp_id` = '$exgruppe', `exp_kwh` = '$vorexp'";
                $conn->query($sql_sel_ins);
            } else {
                $sql_upd_db = "UPDATE $andbase.$dbnamesoll SET `exp_kwh` = '$vorexp'  WHERE `db_id` = '$dup6'";
                $conn->query($sql_upd_db);
            }
        }
    }
    echo "-> Ende AC<br>";
}

// Prüfen Doppelte Werte AC
function checkdbentry_ac($andbase, $anid, $tstamp, $dbnamesoll, $exgruppe) {
    global $conn;
    $out = 0;
    $result  = $conn->query("SELECT `db_id` FROM $andbase.$dbnamesoll WHERE `anl_id` = '$anid' and `grp_id` = '$exgruppe' and `stamp` = '$tstamp'");
    while ($row = $result->fetch_assoc()) {
        $out = $row["db_id"];
    }
    return $out;
}


// Funktionen zum schreibt der Daten für DC
function writedata_dc($andbase, $andbasews, $dbnamews, $dbnamesolldc, $aid, $anlinnum, $from, $to)
{
    global $conn;
    //
    echo "-> Start DC ($dbnamesolldc)<br>";
    $array_expdc = expViewDatajet_dc($andbasews, $dbnamews, $aid, $from, $to);
    if (is_array($array_expdc)) {
        foreach ($array_expdc as $valu) {
            $dbase       = $valu["DBASE"];
            $stamp       = $valu["WSTAMP"];
            $wr          = $valu["WR"];
            $wrnum       = $valu["WRNUM"];
            $soll_imppmo = $valu["SOLLIMPPMO"];
            $soll_imppwr = $valu["SOLLIMPPMWR"];
            $soll_pdcmo  = $valu["SOLLPDCMO"];
            $soll_pdcwr  = $valu["SOLLPDCWR"];
            $soll_umppmo = $valu["SOLLUMPPMO"];
            $soll_umppwr = $valu["SOLLUMPPWR"];
            $deg         = $valu["DEGATATION"];
            $wsirr       = $valu["WSIRR"];
            $wstmp       = $valu["WSTMP"];

            // prüfe ob für diese Anlage zu diesem Zeitpung für diese Wechselrichter schon ein Datenbankeintrag existiert
            // wen JA, dann gib die Datensatz ID zurück
            $dup6        = checkdbentry_dc($andbase, $stamp, $wr, $dbnamesolldc);
            if ( ! $dup6) {
                $sql_sel_ins = "INSERT INTO $andbase.$dbnamesolldc SET wr = '$wr', wr_num = '$wrnum', stamp = '$stamp', anl_id = '$aid', soll_imppmo = '$soll_imppmo', soll_imppwr = '$soll_imppwr', soll_pdcmo = '$soll_pdcmo', soll_pdcwr = '$soll_pdcwr', soll_umppmo = '$soll_umppmo', soll_umppwr = '$soll_umppwr', degatation = '$deg', ws_irr = '$wsirr', ws_tmp = '$wstmp'";
                $conn->query($sql_sel_ins);
            } else {
                $sql_upd_db = "UPDATE $andbase.$dbnamesolldc SET soll_imppmo = $soll_imppmo, soll_imppwr = '$soll_imppwr', soll_pdcmo = '$soll_pdcmo', soll_pdcwr = '$soll_pdcwr', soll_umppmo = '$soll_umppmo', soll_umppwr = '$soll_umppwr', degatation = '$deg', ws_irr = '$wsirr', ws_tmp = '$wstmp' WHERE db_id = '$dup6'";
                $conn->query($sql_upd_db);
                echo "+";
            }
        }
    }
    echo "<br>-> Ende DC<br>";
}

// Prüfen Doppelte Werte DC
function checkdbentry_dc($andbase, $tstamp, $wr, $dbnamesolldc)
{
    global $conn;
    $out = 0;

    $result  = $conn->query("SELECT db_id FROM $andbase.$dbnamesolldc WHERE wr = '$wr' and stamp = '$tstamp'");
    while ($row = $result->fetch_assoc()) {
        $out = $row["db_id"];
    }
    return $out;
}
