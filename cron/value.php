<?php
require_once '../pvp_v1/pvp_v1/incl/config.php';
require_once '../pvp_v1/pvp_v1/incl/functions.php';

$grpmax = 2;                           #Anzahl WR

$from = "2019-08-07 10:00:00";
$to = "2019-08-08 11:00:00";

$dbase = "web32_db3";
$istfirst = "`db__pv_ist_G4NET_10`";
$sollfirst = "`db__pv_soll_G4NET_10`";
$wsfirst = "`db__pv_ws_G4NET_10`";
$solldcfirst = "`db__pv_dcsoll_G4NET_10`";

$dummy = "`db_dummysoll`";

$tbl_dt = "web32_db3.$dummy";          #Dummytime DB
$tbl_ac_ist = "$dbase.$istfirst";      #Ist DB
$tbl_ac_soll = "$dbase.$sollfirst";    #Soll DB
$gview = true;                         #View Group / Single

#########################################################

$dbgrupe = show_data_group('27');
#function build_actexp_ac_sql( $gview, $andbase, $dbnameist, $dbnamesoll, $from, $to, $anlgrpcount, $dbgrupe, $zz, $unit )
$data = build_actexp_ac_sql($gview,$dbase,$istfirst,$sollfirst,$from,$to,'No',$dbgrupe,'0','kwh');

$sorted = sortArrayByFields($data,array('TS' => SORT_ASC,'TS' => SORT_ASC));
echo "aaa $out <br>";

$maxi = 2;

if ($gview == true){
    $out = '"dataProvider": [';
	foreach ($sorted as $key => $value) { 
	if ($value['WR'] == 1){	$out .= "{";$out .= $value['CAT'];}
    $out .= $value['VAL'];
	$out .= $value['VALEXP'];
	if ($value['WR'] >= $maxi){$out .="},";};
	}
}else{
	$out = '"dataProvider": [';
	foreach ($sorted as $key => $value) { 
	$out .= "{";$out .= $value['CAT'];
    $out .= $value['VAL'];
	$out .= $value['VALEXP'];
	$out .="},";
	}
}
	
echo "$out <br>";


?>

	

