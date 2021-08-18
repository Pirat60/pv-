<?php
/**
 * -- Mark Schäfer --
 *
 * Aufruf via Broser (oder wget) make_dbup_man.php?from=2018-09-17&to=2018-09-23
 *
 * Aufruf via Terminal php make_dbup_man.php from='2018-09-17' to='2018-09-23'
 */

// Komandozeilen Parameter in $GET Array speichern
if (defined('STDIN')) {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);
}

require_once '../incl/config.php';
require_once '../incl/functions.php';
require_once './include/makeDatabaseUpdateLib.php';

// Tabellename Anfang
#
$first_soll   = "db__pv_soll_";
$first_solldc = "db__pv_dcsoll_";
$first_ws     = "db__pv_ws_";     //Tabellename Anfang

##
$gfrom = $_GET['from'];
$gto = $_GET['to'];
$anlageId = $_GET['aid'];

if (!$gto or !$gfrom) {
    $gfrom = $_POST['from'];
    $gto = $_POST['to'];
    $anlageId = $_POST['aid'];
    if (!$gto or !$gfrom) {
        echo "exit $gto and $gfrom empty";
        exit;
    }
}


$gfrom = date("Y-m-d", strtotime($gfrom)); #
$gto   = date("Y-m-d", strtotime($gto)); #

$nowt   = time();
$hnull  = " 00:00:00";
$hendd  = " 23:59:00";
$tsfrom = "$gfrom$hnull";
$tsto   = "$gto$hendd";
echo "-> SYNC<br>";
$conn = connect_to_database();
global $conn;
// Abfrage der inter_Nr.
if ($anlageId > 0) {
    $sql_check = "SELECT `anl_id`,`anl_name`,`anl_dbase`,`anl_grupe`,`anl_intnr`,`anl_view` FROM `db_anlage` WHERE anl_id = '$anlageId'";
    echo "Calculate only Plant with ID: $anlageId<br>";
} else {
    $sql_check = "SELECT `anl_id`,`anl_name`,`anl_dbase`,`anl_grupe`,`anl_intnr`,`anl_view` FROM `db_anlage`";
    echo "Calculate all Plant<br>";
}
$resulta   = $conn->query($sql_check);

if ($resulta->num_rows > 0) {
    while ($rowa = $resulta->fetch_assoc()) {
        $anlname    = $rowa["anl_intnr"];
        $adbase     = $rowa["anl_dbase"];
        $dbnamesoll = "$first_soll$anlname";
        $sql_create = "CREATE TABLE IF NOT EXISTS $adbase.`$dbnamesoll` (
                      `db_id` bigint(11) NOT NULL AUTO_INCREMENT,
                      `anl_id` bigint(11) NOT NULL,
                      `anl_intnr` varchar(50) NOT NULL,
                      `stamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                      `grp_id` varchar(15) NOT NULL,
                      `exp_kwh` text NOT NULL,
                       PRIMARY KEY (`db_id`),
                       KEY `stamp` (`stamp`)  
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $conn->query($sql_create);
    }
}
#
$dbws = showeignerdb_namews();
##
$resultb = $conn->query($sql_check);
if ($resultb->num_rows > 0) {
    while ($rowb = $resultb->fetch_assoc()) {
        $anlname = $rowb["anl_intnr"];
        foreach ($dbws as $rowi) {
            $intnrws = $rowi['anl_intnr'];
            if ($anlname == $intnrws) {
                $dbnameo   = $rowi['db_name'];
                $andbasews = $rowi['db_bank'];
                $dbnamews  = $dbnameo;
            }
        }
        $aid          = $rowb["anl_id"];
        $andbase      = $rowb["anl_dbase"];
        $dbnamesoll   = "$first_soll$anlname";
        $dbnamesolldc = "$first_solldc$anlname";
        writedata_ac($andbase, $andbasews, $dbnamews, $dbnamesoll, $aid, $anlname, $tsfrom, $tsto);
        writedata_dc($andbase, $andbasews, $dbnamews, $dbnamesolldc, $aid, $anlname, $tsfrom, $tsto);
        sleep(2);
    }
}
$conn->close();

