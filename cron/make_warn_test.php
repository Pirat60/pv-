<?php
require_once '../incl/config.php';
require_once '../incl/functions.php';
require_once '../incl/functions/emailAlert.php';
require_once '../incl/functions/buildCharts.php';

#Zeitspanne zwischen 7 und 23 Uhr
if ( date( 'G', g4nTimeCET() ) >= 7 AND date( 'G', g4nTimeCET() ) <= 23 ) {
    echo "<pre>";

    $conn = connect_to_database();

    $currentTimeStamp   = g4nTimeCET();
    $NTime              = date( 'Y-m-d', $currentTimeStamp); // Datum - heute
    $NTimePastday       = date( 'Y-m-d', $currentTimeStamp - ( 60 * 60 * 24 ) ); // Datum gestern

    $istFirst    = "db__pv_ist_";
    $sollFirst   = "db__pv_soll_";
    $wsFirst     = "db__pv_ws_";
    $sollDcFirst = "db__pv_dcsoll_";

    // gemutede Anlagen prüfen, ob der 'Mute' gelöscht werden kann (in Abhänigkeit vom Feld 'anl_mute_until')
    $sqlUpdateMuteDate = "UPDATE " . DB_SYSTEM_DATA . ".db_anlage SET anl_mute = 'No' WHERE anl_mute = 'Yes' AND `anl_mute_until` < '" . g4nTimeCET('SQL') . "'";
    $conn->query($sqlUpdateMuteDate);
    //g4nLog($sqlUpdateMuteDate);

    // Abfrage der Datenbanken für die Wetterstationen
    $dbws = showeignerdb_namews(); //Array mit allen Datenbanken für Wetterstatione

    # SQL zur Abfrage der Anlagen
    $sql_anlage = "SELECT `id`,`eigner_id`,`anl_intnr`,`anl_dbase`, `anl_data_go_ws`,`anl_data_go_wr`,`anl_zeitzone_ir`,`anl_zeitzone_dc`,`anl_input_daily`,`anl_grupe_dc`,`anl_data_wsst`,`anl_zeitzone`,`anl_zeitzone_dce`,`anl_zeitzone_ws`,`anl_db_unit`,`anl_gr_count`,`anl_grupe`,`querytime` FROM `db_anlage` WHERE `anl_data_go_ws`='Yes'";
    $sql_anlage = "SELECT `id`,`eigner_id`,`anl_intnr`,`anl_dbase`, `anl_data_go_ws`,`anl_data_go_wr`,`anl_zeitzone_ir`,`anl_zeitzone_dc`,`anl_input_daily`,`anl_grupe_dc`,`anl_data_wsst`,`anl_zeitzone`,`anl_zeitzone_dce`,`anl_zeitzone_ws`,`anl_db_unit`,`anl_gr_count`,`anl_grupe`,`querytime` FROM `db_anlage` WHERE `anl_data_go_ws`='Yes' AND `anl_intnr` LIKE '%G4NET_01'";

    $resAnlagen  = $conn->query( $sql_anlage ); // Lade alle Anlagen

    $AuftragNr = 0; // nur für LogDatei / Kontrolle (echo)

    // Warn Meldungen erzeugen / updaten für alle Anlagen
    if ( $resAnlagen->num_rows > 0 && true) {
        while ( $rowAnlage = $resAnlagen->fetch_assoc() ) { // Für jede Anlage
            $anlid         = $rowAnlage['id'];
            $eid           = $rowAnlage['eigner_id'];
            $andbase       = $rowAnlage['anl_dbase'];
            $andbase       = $rowAnlage['anl_dbase'];
            $projectname   = $rowAnlage['anl_intnr'];
            $anintzz       = $rowAnlage['anl_zeitzone'];
            $anintzzws     = $rowAnlage["anl_zeitzone_ws"];
            $anintzzir     = $rowAnlage["anl_zeitzone_ir"];
            $anintzzdc     = $rowAnlage["anl_zeitzone_dc"];
            $anintzzdce    = $rowAnlage["anl_zeitzone_dce"];
            $anlinputdaily = $rowAnlage['anl_input_daily'];
            $anldatawr     = $rowAnlage['anl_data_go_wr'];
            $AnlageHatACGruppe   = $rowAnlage["anl_grupe"];
            $anlgrpcount   = $rowAnlage["anl_gr_count"];
            $AnlageHatDCGruppe = $rowAnlage["anl_grupe_dc"];
			$querytime     = $rowAnlage["querytime"];
            $unit          = $rowAnlage["anl_db_unit"];
            $wsstatus      = $rowAnlage['anl_data_wsst']; // "old/new"
            $groupsAC      = show_data_group( $anlid ); // Groups AC (Name, InvNr, Min, Max)
            $groupACAnzahl = count ($groupsAC);

            $dbtableist  = "$istFirst$projectname"; //generierte Table
            $dbtablesoll = "$sollFirst$projectname"; //generierte Table
            $dcsolldb    = "$sollDcFirst$projectname";

            $AuftragNr ++; // nur für LogDatei / Kontrolle (echo)

            // Zeitraum für den Daten geladen werden festlegen, je nach dem ob die Anlage regelmäßig Daten sendet oder nur einmal am Tag
            if ($anlinputdaily == "No") {
                // Anlage sendet regelmäßig Daten
                $month = date("m", $currentTimeStamp);
                $from = date("Y-m-d ". $GLOBALS['StartEndTimesAlert'][$month]['start'] .":00:00", $currentTimeStamp);
                $to = date("Y-m-d ". $GLOBALS['StartEndTimesAlert'][$month]['end'] .":00:00", $currentTimeStamp);
                g4nLog("from: $from -> to $to -- Month: " . $GLOBALS['StartEndTimesAlert'][$month]['end'] . "<br>");
            } else {
                // Anlage sendet nur einmal am Tag Daten => wir laden die Daten von gestern (aber warum nur im 3 Stunden intervall ??)
                $month = date("m", $currentTimeStamp - (24 * 3600));
                $from = date("Y-m-d ". $GLOBALS['StartEndTimesAlert'][$month]['start'] .":00:00", $currentTimeStamp - (24 * 3600));
                $to = date("Y-m-d ". $GLOBALS['StartEndTimesAlert'][$month]['end'] .":00:00", $currentTimeStamp - (24 * 3600));
            }

            // Suche Datenbank der für diese Anlage Zuständigen Wetterstation
            foreach ($dbws as $rowWetterStation) {
                $intNrWetterStation = $rowWetterStation['anl_intnr'];
                if ($projectname == $intNrWetterStation) {
                    $dbnameo = $rowWetterStation['db_name'];
                    $andbasews = $rowWetterStation['db_bank'];
                    $dbtablews = $dbnameo;
                }
            }

            echo "<b>Auftragsname -> [$AuftragNr] -> $projectname -> $from - $to</b><br>";

            ////////////// Alert Code DC Soll warn_view = 5, 6, 7 [START]
            if ($AnlageHatDCGruppe == "Yes" && false) {
                $sql3c = "SELECT `stamp` AS sqldate, DATE_FORMAT(`stamp`, '%d.%m.%Y') AS date, DATE_FORMAT(`stamp`, '%H:%i') AS hour,sum(`wr_udc`) as 'sumudc', sum(`wr_pdc`) as 'sumpdc', sum(`wr_pac`) as 'sumpac', sum(`wr_idc`) as 'sumidc',`wr_num`,`inv` AS 'grp_inv' FROM $andbase.`$dbtableist` WHERE `stamp` BETWEEN \"$from\" and \"$to\" GROUP by `stamp`";
                echo "$sql3c<br>";
                $resi = $conn->query($sql3c);

                /// Single DC
                /// warn_view = 5 Typ der Fehlermeldung (hier DC Power ???)
                while ($ro = $resi->fetch_assoc()) {
                    $stampist = $ro["sqldate"];
                    $invgp = $ro["grp_inv"];
                    if ($unit == "w") {
                        $math = $ro["sumpdc"] / 1000 / 4;
                    } else {
                        $math = $ro["sumpdc"];
                    }

                    $stampstrg = strtotime($stampist);
                    $utctimeist = utc_date($stampstrg, $anintzzdc);

                    $pdcexp = expdcviewdatachartsingle_sql($andbase, $dcsolldb, $utctimeist);
                    $pdcoutexp = round($pdcexp, 2);
                    $expma = $pdcoutexp;
                    $pdcoutgood = $expma - $expma * 15 / 100; # -15% good
                    $pdcoutgood = round($pdcoutgood, 2);
                    $dcmath += $expma;
                    $dcmathgood += $pdcoutgood;

                    echo "DC Single -> $dcmath < $dcmathgood<br>";

                    ////// Typ der Fehlermeldung (hier DC Power ???) soll pro Tag nur einmal erscheinen
                    $warnView = 5;
                    if ($dcmath < $dcmathgood) {
                        $warninfo = "DC (ACT) Power over -15%";
                        $alertprioquodc1 = "6";
                        $alertcodequodc1 = "AC005B";
                        $dup9 = checkdbentry($anlid, $NTime, $warnView);
                        if (!$dup9) {
                            $sql_ins_al012 = "INSERT INTO db_anl_warn set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc1',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc1',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                            //SQL BLOCK $conn->query($sql_ins_al012);
                        } else {
                            $sql_upd_al012 = "UPDATE db_anl_warn set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc1',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc1',`warn_active`='yes',`warn_view`='5',`warn_count`='$warnView' WHERE `warn_id` = '$dup9'";
                            //SQL BLOCK $conn->query($sql_upd_al012);
                        }
                    } else {
                        $warninfo = "DC (ACT) Power Value in Range";
                        $alertprioquodc1 = "0";
                        $alertcodequodc1 = "AC005A";
                        $dup10 = checkdbentry($anlid, $NTime, $warnView);
                        if (!$dup10) {
                            $sql_ins_al013 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc1',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc1',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                            //SQL BLOCK $conn->query($sql_ins_al013);
                        } else {
                            $sql_upd_al013 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc1',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc1',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='2' WHERE `warn_id` = '$dup10'";
                            //SQL BLOCK $conn->query($sql_upd_al013);
                        }
                    }
                    echo "Alert Code DC Soll: $alertcodequodc1 - Prio: $alertprioquodc1<br>";
                }

                /// Group DC / I WARN String Abfrage
                /// warn_view = 6 Typ der Fehlermeldung (hier Spannung & Strom ???)

                $dateto = explode(' ', $to, 2);
                #Neu $to setzten  $toN
                $toquery = "SELECT `soll_pdcwr`,`stamp` FROM $andbase.$dcsolldb WHERE `stamp` like '$dateto[0]%' ORDER BY db_id DESC LIMIT 1";
                $result22 = $conn->query($toquery);
                while ($row22 = $result22->fetch_assoc()) {
                    $toN = $row22["stamp"];
                }
                #
                echo "TIME CHECK $from - $to <br>";
                //////// Typ der Fehlermeldung (hier Spannung & Strom ???) soll pro Tag nur einmal erscheinen
                $warnView = 6;
                $view = "dcall";
                $range = "15";
                $build_det = build_actexp_dc_sql($view, $AnlageHatDCGruppe, $andbase, $dbtableist, $dcsolldb, $from, $toN, $anlgrpcount, $groupsAC, $anintzz, $unit);
                foreach ($build_det as $key => $value) {
                    $value = $value['WERT'];
                    //echo "Strom $value >= $range <br>";
                    if ($value >= $range) { #in % negativ
                        $wrstr = $value['WR_R'];
                        $valuestr = $value['WERT'];
                        $warninfo = "DC $valuestr on $wrstr out of Range $range %";
                        $alertprioquodc = "6";
                        $alertcodequodc = "AC006D";
                        $sql_ins_012 = "INSERT INTO `db_anl_warn_val` (`val_id`, `stamp`, `anl_id`, `power`, `wr`, `value`, `warn_id`, `meas`, `val_create_date`) VALUES (NULL, '$NTime', '$anlid', 'DC', '$wrstr', '$valuestr', '$alertcodequodc', '$meas', CURRENT_TIMESTAMP)";
                        $conn->query($sql_ins_012);
                        $dup101 = checkdbentry($anlid, $NTime, $warnView);
                        if (!$dup101) {
                            $sql_ins_al0131 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                            //SQL BLOCK $conn->query($sql_ins_al0131);
                        } else {
                            $sql_upd_al0131 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='2' WHERE `warn_id` = '$dup101'";
                            //SQL BLOCK $conn->query($sql_upd_al0131);
                        }
                        //echo "<i>DC-DIFF -> WR: $wrstr -> Value: $valuestr % ->$warninfo || $anlid </i><br>";
                        echo "Alert Code DC Power: $alertcodequodc - Prio: $alertprioquodc<br>";
                    }
                }

                $view = "dcalli";
                //$build_det = build_actexp_dc_sql($view,$AnlageHatDCGruppe,$andbase,$dbtableist,$dcsolldb,$from,$toN,$anlgrpcount,$groupsAC,$anintzz,$unit);
                foreach ($build_det as $key => $value) {
                    $value = $value['WERT'];
                    echo "Strom $value >= $range <br>";
                    if ($value >= $range) { #in % negativ
                        $wrstr = $value['WR_R'];
                        $valuestr = $value['WERT'];
                        $warninfo = "I $valuestr on $wrstr out of Range $range %";
                        $alertprioquodc = "6";
                        $alertcodequodc = "AC007D";
                        $sql_ins_012 = "INSERT INTO `db_anl_warn_val` (`val_id`, `stamp`, `anl_id`, `power`, `wr`, `value`, `warn_id`, `meas`, `val_create_date`) VALUES (NULL, '$NTime', '$anlid', 'DC', '$wrstr', '$valuestr', '$alertcodequodc', '$meas', CURRENT_TIMESTAMP)";
                        $conn->query($sql_ins_012);
                        $dup101 = checkdbentry($anlid, $NTime, $warnView);
                        if (!$dup101) {
                            $sql_ins_al0131 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                            //SQL BLOCK $conn->query($sql_ins_al0131);
                        } else {
                            $sql_upd_al0131 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='2' WHERE `warn_id` = '$dup101'";
                            //SQL BLOCK $conn->query($sql_upd_al0131);
                        }
                        //echo "<i>I-DIFF -> WR: $wrstr -> Value: $valuestr % ->$warninfo || $anlid </i><br>";
                        echo "Alert Code DC Current: $alertcodequodc - Prio: $alertprioquodc<br>";
                    }
                }
                #
                #Group DC WARN abfrage
                #
                $sql_x1 = "SELECT DISTINCT `meas` FROM `db_anl_warn_val` WHERE `stamp` = '$NTime' AND `anl_id` LIKE '$anlid' ORDER BY `meas` ASC";
                $resx = $conn->query($sql_x1);
                if ($resx->num_rows > 0) {
                    while ($rox = $resx->fetch_assoc()) {
                        $meas = $rox["meas"];
                    }
                    $meas++;
                } else {
                    $meas = "1";
                }
                ##
                if ($groupsAC) {
                    $view = "true";
                    $cod = 0;
                    $cad = 0;
                    foreach ($groupsAC as $val) {
                        $anid = $val["ANLID"];
                        $min = $val["GMIN"];
                        $max = $val["GMAX"];
                        $invnr = $val["INVNR"];

                        if ($AnlageHatACGruppe == "Yes") {
                            if ($anlgrpcount == "No") {
                                $sql2c = "SELECT `stamp` AS sqldate, DATE_FORMAT(`stamp`, '%d.%m.%Y') AS date, DATE_FORMAT(`stamp`, '%H:%i') AS hour,sum(`wr_udc`) as 'sumudc',sum(`wr_pdc`) as 'sumpdc',sum(`wr_pac`) as 'sumpac',sum(`wr_idc`) as 'sumidc',`wr_num`,`inv` AS 'grp_inv' FROM $andbase.`$dbtableist` WHERE `stamp` BETWEEN \"$from\" and \"$to\" and `inv` = \"$invnr\" GROUP by `stamp`"; #LIMIT 1"; #
                            } else {
                                $sql2c = "SELECT `stamp` AS sqldate, DATE_FORMAT(`stamp`, '%d.%m.%Y') AS date, DATE_FORMAT(`stamp`, '%H:%i') AS hour,sum(`wr_udc`) as 'sumudc',sum(`wr_pdc`) as 'sumpdc',sum(`wr_pac`) as 'sumpac',sum(`wr_idc`) as 'sumidc',`wr_num`,`inv` AS 'grp_inv' FROM $andbase.`$dbtableist` WHERE `stamp` BETWEEN \"$from\" and \"$to\" and `wr_num` >= \"$min\" and `wr_num` <= \"$max\" GROUP by `stamp`"; #LIMIT 1"; #GROUP by `stamp`";
                            }
                        } else {
                            $sql2c = "SELECT `stamp` AS sqldate, DATE_FORMAT(`stamp`, '%d.%m.%Y') AS date, DATE_FORMAT(`stamp`, '%H:%i') AS hour,sum(`wr_udc`) as 'sumudc',sum(`wr_pdc`) as 'sumpdc',sum(`wr_pac`) as 'sumpac',sum(`wr_idc`) as 'sumidc',`wr_num`,`inv` AS 'grp_inv' FROM $andbase.`$dbtableist` WHERE `stamp` BETWEEN \"$from\" and \"$to\" LIMIT 1"; #GROUP by `stamp`
                        }

                        if (($resi = $conn->query($sql2c)) === false) {
                            printf("Invalid query: %s\nWhole query: %s\n", $conn->error, $sql2c);
                        }

                        while ($ros = $resi->fetch_assoc()) {
                            $cod++;
                            $stampist = $ros["sqldate"];
                            $invgp = $ros["grp_inv"];
                            if ($unit == "w") {
                                $math = $ros["sumpdc"] / 1000 / 4;
                            } else {
                                $math = $ros["sumpdc"];
                            }

                            $stampstrg = strtotime($stampist);

                            $utctimeist = utc_date($stampstrg, $anintzzdc);
                            $utctimedce = utc_date($stampstrg, $anintzzdce);
                            $actdcout = round($math, 2);
                            $pdcout = expdcviewdatachartgroup_sql($andbase, $dcsolldb, $utctimedce, $invnr);
                            $expdcout = round($pdcout, 2);

                            $mdcact += $actdcout;
                            $mdcexp += $expdcout;
                        }

                        if ($anldatawr == "Yes") {
                            $mdcactmw = $mdcact / $cod;
                            $mdcexpmw = $mdcexp / $cod;
                            $quotadcmw = ($mdcactmw - $mdcexpmw) * 100 / $mdcactmw;
                            $quotadcmw = round($quotadcmw, 2);

                            if ($quotadcmw < 0) {
                                $maqd = "<mark>";
                                $ma2qd = "</mark>";
                            } else {
                                $maqd = "";
                                $ma2qd = "";
                            }
                            ###
                            if (!is_nan($quotadcmw)) {
                                if ($quotadcmw <= "-20" and $quotadcmw >= "-100") {
                                    $cad++;
                                } elseif ($quotadcmw >= "10") {
                                    $cad++;
                                } else {
                                    $cad = "0";
                                }
                                if ($quotadcmw <= "-99") {
                                    $cad = "5";
                                }
                            } else {
                                $cad = "-1";
                            }
                            $cod = 0;
                            $mdcact = 0;
                            $mdcexp = 0;
                            ###
                        }

                        $warnView = 6; // Typ der Fehlermeldung (hier DC Quota ???) soll pro Tag nur einmal erscheinen
                        if ($cad >= "1") {
                            $warninfo = "HIGH DC QUO AT GRP $invgp";
                            $alertprioquodc = "6";
                            $alertcodequodc = "AC006B";
                            $sql_ins_01 = "INSERT INTO `db_anl_warn_val` (`val_id`, `stamp`, `anl_id`, `power`, `wr`, `value`, `warn_id`, `meas`, `val_create_date`) VALUES (NULL, '$NTime', '$anlid', 'DC', '$invgp', '$quotadcmw', '$alertcodequodc', '$meas', CURRENT_TIMESTAMP)";
                            $conn->query($sql_ins_01);
                            $dup13 = checkdbentry($anlid, $NTime, $warnView);
                            if (!$dup13) {
                                $sql_ins_al01 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                                //SQL BLOCK $conn->query($sql_ins_al01);
                            } else {
                                $sql_upd_al01 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='2' WHERE `warn_id` = '$dup13'";
                                //SQL BLOCK $conn->query($sql_upd_al01);
                            }
                        }

                        if ($cad == "0") {
                            $warninfo = "DC QUO ARE IN RANGE";
                            $alertprioquodc = "0";
                            $alertcodequodc = "AC006A";
                            $sql_ins_02 = "INSERT INTO `db_anl_warn_val` (`val_id`, `stamp`, `anl_id`, `power`, `wr`, `value`, `warn_id`, `meas`, `val_create_date`) VALUES (NULL, '$NTime', '$anlid', 'DC', '$invgp', '$quotadcmw', '$alertcodequodc', '$meas', CURRENT_TIMESTAMP)";
                            $conn->query($sql_ins_02);
                            $dup14 = checkdbentry($anlid, $NTime, $warnView);
                            if (!$dup14) {
                                $sql_ins_al02 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                                //SQL BLOCK $conn->query($sql_ins_al02);
                            } else {
                                $sql_upd_al02 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='2' WHERE `warn_id` = '$dup14'";
                                //SQL BLOCK $conn->query($sql_upd_al02);
                            }
                        }
                        if ($cad < "0") {
                            $warninfo = "NO DC QUO ARVIBLE";
                            $alertprioquodc = "0";
                            $alertcodequodc = "AC006C";
                            $sql_ins_03 = "INSERT INTO `db_anl_warn_val` (`val_id`, `stamp`, `anl_id`, `power`, `wr`, `value`, `warn_id`, `meas`, `val_create_date`) VALUES (NULL, '$NTime', '$anlid', 'DC', '$invgp', '$quotadcmw', '$alertcodequodc', '$meas', CURRENT_TIMESTAMP)";
                            $conn->query($sql_ins_03);
                            $dup15 = checkdbentry($anlid, $NTime, $warnView);
                            if (!$dup15) {
                                $sql_ins_al03 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                                //SQL BLOCK $conn->query($sql_ins_al03);
                            } else {
                                $sql_upd_al03 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodequodc',`warn_info`='$warninfo',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioquodc',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='2' WHERE `warn_id` = '$dup15'";
                                //SQL BLOCK $conn->query($sql_upd_al03);
                            }
                        }
                        //echo "<i>DC -> GPID: $invgp ->ACT_DC_MW: $mdcactmw ->EXP_DC_MW: $mdcexpmw ->QUO_DC_MW: $maqd$quotadcmw$ma2qd ->$warninfo || QUO_DC -> $cad </i><br>";
                        echo "Alert Code PR: $alertcodequodc - Prio: $alertprioquodc<br>";
                        $cad = 0;
                    }
                }
            }
            ////////////// Alert Code DC Soll [END]

            ////////////// Alert Code Group AC (AC Quota) [START]
            // Im Array $groupsAC sind die AC Gruppen der Anlage gespeichert (oben per SQL geladen)
            if ($groupsAC  && true) {
                $view = "true";
                $ca = 0; // Zähler für die Häufigkeit der Fehler
                $warninfo = "";
                $d = 0;
                foreach ($groupsAC as $groupValue) {
                    $anid   = $groupValue["ANLID"];
                    $min    = $groupValue["GMIN"];
                    $max    = $groupValue["GMAX"];
                    $invnr  = $groupValue["INVNR"];

                    //Ist Daten AC und DC der letzten 2 Stunden laden und in Array ArrACTGroup speichern
                    $arrACTGroup[$d] = actviewdatachartgroup_sqltest($andbase, $dbtableist, $from, $to, $anlgrpcount, $invnr, $min, $max);

                    // SQL zum laden der EXP DC und AC Daten des aktuellen Inverters für den zu vergleichenden Zeitraum
                    if ($AnlageHatACGruppe == "Yes") {
                        $sqlExpData = "SELECT DISTINCT stamp AS istdate, sum(exp_kwh) as exppac, grp_id FROM $andbase.$dbtablesoll WHERE stamp BETWEEN '$from' and '$to' and grp_id = '$invnr' GROUP by stamp ORDER BY istdate ASC";
                    } else {
                        // Sollte eigntlich nie ausgeführt werden, wenn doch bitte prüfen warum und ob das sinn macht, event in Zeile 369 (if anweisung) noch prüfen ob AnlageHatGruppe == Yes;
                        // deshalb loggen ob das doch mal auftritt
                        g4nLog("Gruppen AC Berechnung: AnlageHatACGruppe == No bei $invnr");
                        $sqlExpData = "SELECT DISTINCT stamp AS istdate, sum(wr_pac) as exppac, grp_id FROM $andbase.$dbtablesoll WHERE stamp BETWEEN '$from' and '$to' LIMIT 1 ";
                        g4nLog("Gruppen AC Berechnung: $sqlExpData");
                    }
                    $resultExpData = $conn->query($sqlExpData);
                    $numRowsResultExpData = $resultExpData->num_rows;
                    while ($rowExpData = $resultExpData->fetch_assoc()) {
                        $stampsoll = $rowExpData["istdate"];
                        $expout = $rowExpData["exppac"];
                        $grpid = $rowExpData["grp_id"];
                        //Zeiten korriegieren - Zeitverschiebeung aus Anlagen Daten (zz)
                        $timeIstAdjust = timeAjustment(strtotime($stampsoll), $anintzz);
                        $maExp += $expout;
                        //echo "$stampsoll vs. $timeIstAdjust --- Expect Out: $expout<br>";
                    }

                    $actPowerAc = round( actviewdatachartgroup_array_mw($arrACTGroup, $timeIstAdjust, $d, "act") , 2);
                    //$actpdc = actviewdatachartgroup_array_mw($ArrACTGroup, $timeIstAdjust, $d, "pdc");
                    if ($unit == "w") {
                        $actPowerAc = round($actPowerAc / 1000 / 4, 2);
                        //$pdcout = round($actpdc / 1000 / 4, 2);
                    }

                    if ($anldatawr == "Yes") { // Was soll diese Abfrage
                        $expPowerAc = $maExp / $numRowsResultExpData;
                        //echo "Average Expect: $expPowerAc -- Average Actual: $actPowerAc<br>";
                        $quotamw = round(($actPowerAc - $expPowerAc) * 100 / $actPowerAc, 2);
                        if (!is_nan($quotamw)) {
                            if ($quotamw >= -100 && $quotamw <= -20) { $ca++; } // Zwischen -100% und -20%
                            elseif ($quotamw >= 10) { $ca++; } // größer als +20%
                            else {
                                //$ca = 0;
                            } // in allen anderen Fällen

                            if ($quotamw < -100) { $ca = 5; }

                        } else {
                            $ca = -1;
                        }
                        $maExp = 0;
                        $warninfo .= " ($grpid -> $quotamw% | $ca)";
                    }
                    //echo "AC ->GPID: $grpid -> ACT_MW: $actPowerAc -> EXP_MW: $expPowerAc -> QUO_MW: $quotamw -> $warninfo || QUO -> $ca<br>";
                }

                $warnView = 4; // Typ der Fehlermeldung (Group AC) soll pro Tag nur einmal erscheinen
                if ($ca >= 1) {
                    $alertprioquo = "6";
                    $alertcodequo = "AC004B"; //error
                }
                if ($ca == 0) {
                    $alertprioquo = "0";
                    $alertcodequo = "AC004A"; //success
                }
                if ($ca <= -1) {
                    $alertprioquo = "0";
                    $alertcodequo = "AC004C"; //info
                }
                $dup4 = checkdbentry($anlid, $NTime, $warnView);
                if (!$dup4) {
                    $sql_ins_al01 = "INSERT INTO db_anl_warn set stamp='$NTime', anl_id='$anlid', warn_code='$alertcodequo', warn_info='$warninfo', warn_create_date=CURRENT_TIMESTAMP, warn_prio='$alertprioquo', warn_active='yes', warn_view='$warnView', warn_count='1'";
                    //$conn->query($sql_ins_al01);
                } else {
                    $sql_upd_al01 = "UPDATE db_anl_warn set stamp = '$NTime', anl_id='$anlid', warn_code='$alertcodequo', warn_info='$warninfo', warn_create_date=CURRENT_TIMESTAMP, warn_prio='$alertprioquo', warn_active='yes', warn_view='$warnView', warn_count='2' WHERE warn_id='$dup4'";
                    //$conn->query($sql_upd_al01);
                    echo "$sql_upd_al01<br>";
                }
            } //end if $groupsAC
            ////////////// Alert Code AC Quota [END]

            ////////////// Alert Code IO Meldungen Wetter und IST Daten warn_view = 2 oder 3 [START]
            if (false) {
                $acist = "I/O IST";
                $acws = "I/O WS";
                $acpr = "PR p.D.";

                // Suche letzten Eintrag in PV_IST und extrahiere den Timestamp ($stamp_sql_ist)
                $sql_wdio_ist = 'SELECT `stamp` FROM `' . $andbase . '`.`' . $dbtableist . '` ORDER BY `stamp` DESC limit 1 '; // SQL um IST Daten zu laden
                $resIoIst = $conn->query($sql_wdio_ist);
                while ($rowIoIst = $resIoIst->fetch_assoc()) {
                    $lastIOInputTimestamp = strtotime($rowIoIst['stamp']); // Timestamp des letzten IO Daten eingang
                }


                // Suche letzten Eintrag in PV_WS (Wetterdaten) und extrahiere den Timestamp ($stamp_sql_ws)
                $sql_wdio_ws = 'SELECT `stamp` FROM `' . $andbasews . '`.`' . $dbtablews . '` ORDER BY `stamp` DESC limit 1 '; // SQL um Wetter Daten zu laden
                $resIoWs = $conn->query($sql_wdio_ws);
                while ($rowiows = $resIoWs->fetch_assoc()) {
                    $lastWSInputTimestamp = strtotime($rowiows['stamp']);// Timestamp des letzten Wetter Daten eingang
                }

                $stamp_sql_istu = strtotime(timeAjustment($lastIOInputTimestamp, $anintzz));
                $stamp_div_ist = $currentTimeStamp - $stamp_sql_istu;
                $stamp_div_min_ist = round($stamp_div_ist / 60); // Diverenz 'IST' in Minuten (letzte Zeit vs. current Time)

                $stamp_div_ws = $currentTimeStamp - $lastWSInputTimestamp;
                $stamp_div_min_ws = round($stamp_div_ws / 60); // Diverenz 'Wetter' in Minuten (letzte Zeit vs. current Time)

                # Alert Code für Anlagen IO in Datenbank schreiben [START]
                if ($anlinputdaily == "No" && $anldatawr == "Yes") {
                    if ($stamp_div_min_ist <= 60) {
                        $alertcodeist = "AC001A"; // grün = alles Okay (Abweichung kleiner als 1 Stunde)
                        $alertprioist = "0";
                    } elseif ($stamp_div_min_ist > 60 && $stamp_div_min_ist <= 180) {
                        $alertcodeist = "AC001B"; // orange = Warnung (Abweichung zwischn 1 und 3 Stunden
                        $alertprioist = "2";
                    } elseif ($stamp_div_min_ist > 180) {
                        $alertcodeist = "AC001C"; // rot = Fehler (Abweichung größer als 3 Stunden)
                        $alertprioist = "6";
                    }
                } else {
                    // wir nur ausgeführt wenn die Anlage nur alle 24 Stunden (tägliches) Datenupdate hat
                    g4nLog("Stamp div min ist: $stamp_div_min_ist - Anlagen ID: $anlid");
                    if ($stamp_div_min_ist < 1440) { // 1440 = 24 Stunden
                        $alertcodeist = "AC001A";
                        $alertprioist = "0";
                    } else {
                        $alertcodeist = "AC001C";
                        $alertprioist = "6";
                    }
                }
                $warnView = 3; // Typ der Fehlermeldung (hier IO) soll pro Tag nur einmal erscheinen
                $dup6 = checkdbentry($anlid, $NTime, $warnView);
                if (!$dup6) {
                    $sql_ins_al1 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodeist',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioist',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                    //g4nLog("--make_update: $sql_ins_al1");
                    //SQL BLOCK $conn->query($sql_ins_al1);
                } else {
                    $sql_upd_al1 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodeist',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertprioist',`warn_active`='yes',`warn_view`='$warnView',`warn_count`=`warn_count`+1 WHERE `warn_id` = '$dup6'";
                    //g4nLog("--make_update: $sql_upd_al1");
                    //SQL BLOCK $conn->query($sql_upd_al1);
                }
                echo "Alert Code Anlagen IO: $alertcodeist - Prio: $alertprioist<br>";
                # Alert Code für Anlagen IO in Datenbank schreiben [END]

                # Alert Code für Wetterstationen in Datenbank schreiben [START]
                if ($anlinputdaily == "No") {
                    if ($stamp_div_min_ws <= 60) {
                        $alertcodews = "AC002A"; // grün = alles Okay (Abweichung kleiner als 1 Stunde)
                        $alertpriows = "0";
                    } elseif ($stamp_div_min_ws > 60 and $stamp_div_min_ws <= 180) {
                        $alertcodews = "AC002B"; // orange = Warnung (Abweichung zwischen 1 und 3 Stunden
                        $alertpriows = "2";
                    } elseif ($stamp_div_min_ws > 180) {
                        $alertcodews = "AC002C";  // rot = Fehler (Abweichung größer als 3 Stunden)
                        $alertpriows = "6";
                    }
                } else {
                    // wir nur ausgeführt wenn die Anlage nur alle 24 Stunden (tägliches) Datenupdate hat
                    if ($stamp_div_min_ws < 24 * 60) {
                        $alertcodews = "AC002A";
                        $alertpriows = "0";
                    } else {
                        $alertcodews = "AC002C"; // rot = Fehler (Abweichung größer als 3 Stunden)
                        $alertpriows = "6";
                    }
                }
                $warnView = 2; // Typ der Fehlermeldung (hier Wetter) soll pro Tag nur einmal erscheinen
                $dup7 = checkdbentry($anlid, $NTime, $warnView);
                if (!$dup7) {
                    $sql_ins_al2 = "INSERT INTO `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodews',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertpriows',`warn_active`='yes',`warn_view`='$warnView',`warn_count`='1'";
                    //SQL BLOCK $conn->query($sql_ins_al2);
                } else {
                    $sql_upd_al2 = "UPDATE `db_anl_warn` set `stamp` = '$NTime',`anl_id`='$anlid',`warn_code`='$alertcodews',`warn_create_date`=CURRENT_TIMESTAMP,`warn_prio`='$alertpriows',`warn_active`='yes',`warn_view`='$warnView',`warn_count`=`warn_count`+1 WHERE `warn_id` = '$dup7'";
                    //SQL BLOCK $conn->query($sql_upd_al2);
                }
                echo "Alert Code Wetter IO: $alertcodews - Prio: $alertpriows<br>";
                # Alert Code für Wetterstationen in Datenbank schreiben [END]
            }
            ////////////// Alert Code IO Meldungen Wetter und IST Daten [END]

            ////////////// Alert Code für Ausfall von WR [Start]
            if (false) {

                $fromTs = strtotime($from);
                $toTs = strtotime($to);
                $minutes15 = 60 * 60;
                for ($stamp = $fromTs; $stamp <= $toTs; $stamp += $minutes15) {
                    $localFrom = date("Y-m-d H:i:00", $stamp);
                    $localTo = date("Y-m-d H:i:00", $stamp + $minutes15);
                    $sqlAllWR = "SELECT AVG(wr_pac) AS avg_wr_pac, AVG(wr_pdc) AS avg_wr_pdc FROM $andbase.$dbtableist WHERE stamp >= '$localFrom' and stamp < '$localTo'";
                    echo "$sqlAllWR<br>---<br>";
                    $resAllWr = $conn->query($sqlAllWR);
                    $rowAllWR = $resAllWr->fetch_assoc();
                    $allWrAvgAc = round($rowAllWR['avg_wr_pac'], 2);
                    $allWrAvgDc = round($rowAllWR['avg_wr_pdc'], 2);
                    $allWrAvgAcLimit = round($allWrAvgAc - ($allWrAvgAc * 20 /100), 2); //20% Abweichung
                    $allWrAvgDcLimit = round($allWrAvgDc - ($allWrAvgDc * 20 /100), 2); //20% Abweichung

                    $sqlSingleWr = "SELECT *, AVG(wr_pac) AS avg_wr_pac, AVG(wr_pdc) AS avg_wr_pdc FROM $andbase.$dbtableist WHERE stamp >= '$localFrom' and stamp < '$localTo' GROUP BY inv, wr_num";
                    echo "$sqlSingleWr<br>";
                    $resSingleWr = $conn->query($sqlSingleWr);
                    while ($rowSingleWR = $resSingleWr->fetch_assoc()) {
                        $singleWRAvgAc = $rowSingleWR['avg_wr_pac'];
                        $singleWRAvgDc = $rowSingleWR['avg_wr_pdc'];
                        $inverter = $rowSingleWR['inv'];
                        $wr = $rowSingleWR['wr_num'];
                        if ($singleWRAvgAc < $allWrAvgAcLimit) {
                            g4nLog("Alert: WR kaputt. Anlage: $anlid / $projectname Inverter: $inverter WR: $wr - Zeitpunkt: $localFrom", "wr-alert");
                            echo ("Single AVG AC: $singleWRAvgAc Limit: $allWrAvgAcLimit AVG All: $allWrAvgAc<br>");
                            print_r($rowSingleWR);
                            if ($singleWRAvgDc < $allWrAvgDcLimit) {
                                g4nLog("Alert: WR kaputt? Nee liegt anscheinend auf der DC Seite. Anlage: $anlid / $projectname Inverter: $inverter WR: $wr - Zeitpunkt: $localFrom", "wr-alert");
                            }
                        }
                    }
                    echo "<hr>";
                }
            }
            ////////////// Alert Code für Ausfall von WR [END]

            ////////////// PR aus Tabelle 'db_anl_prw' lesen und entsprechende Meldung Speichern warn_view = 1 [START]
            if (false) {
                $sqlpr = "SELECT pr_id, anl_id, DATE_FORMAT(pr_stamp_ist,'%d-%m-%Y') AS stamp_ist, pr_act, pr_exp, pr_diff, pr_diff_poz, irradiation, pr_act_poz, pr_exp_poz, panneltemp  FROM db_anl_prw WHERE anl_id = '$anlid' and pr_stamp_ist = '$NTimePastday'";//' LIMIT 1';
                $respr = $conn->query($sqlpr);
                $warnView = 1; // Typ der Fehlermeldung (hier PR) soll pro Tag nur einmal erscheinen
                while ($rowpr = $respr->fetch_assoc()) {
                    $prdiff = round($rowpr['pr_diff_poz'], 2);
                    echo "$prdiff<br>";
                    if ($prdiff >= $GLOBALS['abweichung']['produktion']['yellow'] && $prdiff <= $GLOBALS['abweichung']['produktion']['green']) {
                        $alertcodepr = "AC003A";
                        $alertpriopr = "0";
                    } elseif ($prdiff < $GLOBALS['abweichung']['produktion']['yellow'] and $prdiff >= $GLOBALS['abweichung']['produktion']['red']) {
                        $alertcodepr = "AC003B";
                        $alertpriopr = "2";
                    } elseif ($prdiff < $GLOBALS['abweichung']['produktion']['red']) {
                        $alertcodepr = "AC003C";
                        $alertpriopr = "6";
                    }
                    if ($prdiff > 0) {
                        $alertcodepr = "AC003D";
                        $alertpriopr = "0";
                    }
                }

                $dup8 = checkdbentry($anlid, $NTime, $warnView);
                if (!$dup8) {
                    $sql_ins_al3 = "INSERT INTO db_anl_warn set stamp = '$NTime', anl_id = '$anlid', warn_code = '$alertcodepr', warn_create_date = CURRENT_TIMESTAMP, warn_prio ='$alertpriopr', warn_active = 'yes', warn_view = '1', warn_count = '1', warn_info = '$prdiff'";
                    $conn->query($sql_ins_al3);
                } else {
                    $sql_upd_al3 = "UPDATE db_anl_warn set stamp = '$NTime', anl_id = '$anlid', warn_code = '$alertcodepr', warn_create_date = CURRENT_TIMESTAMP, warn_prio = '$alertpriopr', warn_active = 'yes', warn_view = '1', warn_count ='2', warn_info = '$prdiff' WHERE warn_id = '$dup8'";
                    $conn->query($sql_upd_al3);
                }
                echo "<i>IST $stamp_div_min_ist MIN - $alertcodeist || PR p.D. $alertcodepr - $prdiff</i><br>";
                echo "Alert Code PR: $alertcodepr - Prio: $alertpriopr<br>";
            }
            ////////////// PR aus Tabelle 'db_anl_prw' lesen und entsprechende Meldung Speichern [END]
            echo "<hr>";

        } // end while
    } // end if $resAnlage

    // Meldungen Senden
    // Admin Email Senden (Warnungen) Alle zwei Stunden in der Zeit zwischen 10 und 18 Uhr (9, 11, 13, 15, 17 Uhr)
    if (false) {
        $month = date( 'm', g4nTimeCET());
        $hour = date( 'G', g4nTimeCEt());

        if ( $hour >= $GLOBALS['StartEndTimesAlert'][$month]['start'] AND $hour <= $GLOBALS['StartEndTimesAlert'][$month]['end'] ) {
            if ($hour % 2 == 1) {
                checkAlertsAndSendEmailAdmin();
            }
        }
        // Eigner E-Mails Senden - Alle drei Stunden in der Zeit zwischen 10 und 17 Uhr (9, 12, 15 Uhr)
        if ( $hour >= 10 AND $hour <= 17 ) {
            if (date('G', g4nTimeCET()) % 3 == 0) {
                echo '<p>Send alert e-mails<p>';
                //checkAlertsAndSendEmail();
            }
        }
        $sql_cron = "INSERT INTO `pvp_cronlog` (`cron_modul`, `cron_massage`) VALUES ('makewarn', 'ok')";
    }

    //SQL BLOCK $conn->query( "$sql_cron" );
    $conn->close();
    echo "</pre>";
}
###

/* Prüfen Doppelte Werte
 *
 * $anid    = Anlagen ID
 * $tstamp  = Zeitstempel
 * $ac      = WarnCode
 */
function checkdbentry( $anid, $tstamp, $ac ) {
    global $conn;
    $out = "";
    $queryck = "SELECT count(*) as 'out', `warn_id` FROM `db_anl_warn` WHERE `anl_id` = '$anid' and `stamp` = '$tstamp' and `warn_view` = '$ac'";
    $result         = $conn->query( $queryck );
    while ( $row = $result->fetch_assoc() ) {
        $out = $row["warn_id"];
    }
    $result->free();
    return $out;
}
