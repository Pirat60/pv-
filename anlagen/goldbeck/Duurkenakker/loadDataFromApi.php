<?php
/**
 * Load Data Duurkenakker
 * mr 13-04-21
 */

set_time_limit(5000);

require_once './config.php';
require_once 'lib.php';
require_once '../lib/meteoControlCurlGets.php';
require_once '../../lib/globalFunctions.php';

//$from = strtotime('2020-10-26 04:00');
//$to   = strtotime('2020-10-26 22:00');
/**
 * @var array $assignValueArray
 */

$from = strtotime(date('Y-m-d H:00', time() - (4 * 3600)));
$to = time();
$output = loadData($assignValueArray, $from, $to);






