<?php
/**
 * Load Data Duurkenakker – Manuel
 * mr 13-04-21
 */

require_once 'config.php';
require_once 'lib.php';
require_once '../lib/meteoControlCurlGets.php';
require_once '../../lib/globalFunctions.php';

echo '<pre>';
/*
$from_temp = '';
$to_temp = '';
$load_frommeters = 0;
$val = getopt("p:");
if($val['p'][0] == 'loaddatafrommeters'){
    $from_temp = $val['p'][1];
    $to_temp = $val['p'][2];
    $load_frommeters = 1;
    require_once 'loadDataFromApi.php';
    exit;
}
*/

$month      = 4;
$startday   = 13;
$endday     = 13;

for ($d = $startday; $d<=$endday; $d++) {
    $from = strtotime('2021-' . $month . '-' . $d . ' 04:00');
    $to = strtotime('2021-' . $month . '-' . $d . ' 23:00');
    /**
     * @var array $assignValueArray
     */
    $output = loadData($assignValueArray, $from, $to);
    echo str_replace('<br>', "\n", $output);
    sleep(2);
}
echo "fertig</pre>";


