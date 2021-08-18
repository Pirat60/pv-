<?php
/**
 * Load Data LELYSTAD 2
 * mr 25-01-21
 */

set_time_limit(5000);

require_once './config.php';
require_once 'lib.php';
require_once '../lib/meteoControlCurlGets.php';
require_once '../lib/metaDataNLCurlGets.php';
require_once '../../lib/globalFunctions.php';

$anlagenTabelle = DATABASE_ID;
$anlagenId = ANLAGEN_ID;
$systemKey = PLANT_KEY;
$weatherDbIdent = WEATHER_DB_IDENT;

//$from = strtotime('2020-10-26 04:00');
//$to   = strtotime('2020-10-26 22:00');
/**
 * @var array $assignValueArray
 */


if ($load_frommeters == 1){
    $from = explode("_", $from_temp);
    $to = explode("_", $to_temp);

    $timesart = strtotime($from[1].'/'.$from[2].'/'.$from[0]);
    $timeend = strtotime($to[1].'/'.$to[2].'/'.$to[0]);

    $y = 0;
    for ($i = $timesart; $i < $timeend + 86400; $i = $i + 86400) {
        $x = getResultsFromMeters(00001, 235959, $load_frommeters, $anlagenId, $i);
        $y = $y + $x;
    }
    echo "\n Final:".$y;
}else{
    $load_frommeters = 0;
    $from = strtotime(date('Y-m-d H:00', time() - (4 * 3600)));
    $to = time();
    $output = loadDataLelystad1($assignValueArray, $from, $to);
    getResultsFromMeters(30000, 100000, $load_frommeters, $anlagenId);
}





