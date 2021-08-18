<?php
// -- Mark Schäfer --
// Aufruf make_dbupdc_man.php?from=2018-09-17&to=2018-09-23

echo "Start<br>";

require_once __DIR__ . '/../pvp_v1/incl/config.php';
require_once __DIR__ . '/../pvp_v1/incl/functions.php';
require_once 'include/makeDatabaseUpdateLib.php';

// Tabellename Anfang
$first_solldc = "db__pv_dcsoll_";
$first_ws = "db__pv_ws_";     //Tabellename Anfang

$gfrom = $_POST['from'];
$gto = $_POST['to'];
$anlageId = $_POST['aid'];
if (!$gto or !$gfrom) {
    echo "exit $gto and $gfrom empty";
    exit;
}

$gfrom = date("Y-m-d", strtotime($gfrom)); #
$gto = date("Y-m-d", strtotime($gto)); #

$nowt = time();
$hnull = " 00:00:00";
$hendd = " 23:59:00";
$tsfrom = "$gfrom$hnull";
$tsto = "$gto$hendd";
echo "-> SYNC<br>";
$conn = connect_to_database();

// Abfrage der G4NET inter_Nr.
if ($anlageId > 0) {
    $sql_check = "SELECT `id`,`anl_name`,`anl_dbase`,`anl_grupe`,`anl_intnr`,`anl_view` FROM `db_anlage` WHERE id = '$anlageId'";
    echo "Calculate only Plant with ID: $anlageId<br>";
} else {
    $sql_check = "SELECT `id`,`anl_name`,`anl_dbase`,`anl_grupe`,`anl_intnr`,`anl_view` FROM `db_anlage`";
    echo "Calculate all Plant<br>";
}
$resulta = $conn->query($sql_check);

$dbws = showeignerdb_namews();

$resultb = $conn->query($sql_check);
if ($resultb->num_rows > 0) {
    while ($rowb = $resultb->fetch_assoc()) {
        $anlname = $rowb["anl_intnr"];
        echo "$anlname<br>";
        foreach ($dbws as $rowi) {
            $intnrws = $rowi['anl_intnr'];
            if ($anlname == $intnrws) {
                $dbnameo = $rowi['db_name'];
                $andbasews = $rowi['db_bank'];
                $dbnamews = $dbnameo;
            }
        }
        $aid = $rowb["id"];
        $andbase = $rowb["anl_dbase"];
        $dbnamesolldc = "$first_solldc$anlname";
        writedata_dc($andbase, $andbasews, $dbnamews, $dbnamesolldc, $aid, $anlname, $tsfrom, $tsto);
        sleep(2);
    }
}
$conn->close();

echo "-> Routine ENDE.";

