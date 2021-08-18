<?php
require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';
require_once '../pvp_v1/incl/functions/emailAlert.php';
require_once '../pvp_v1/incl/functions/buildCharts.php';

#Zeitspanne zwischen 7 und 23 Uhr

echo "<pre>Start<br>";

$conn = connect_to_database();

$currentTimeStamp = g4nTimeCET();
$yesterdayTimeStamp = $currentTimeStamp - 24 * 60 * 60;
//$yesterdayTimeStamp = $currentTimeStamp - 48 * 60 * 60;

$istFirst = "db__pv_ist_";
$sollFirst = "db__pv_soll_";
$wsFirst = "db__pv_ws_";
$sollDcFirst = "db__pv_dcsoll_";



$style = "<style> table {text-align: center;} td, th { border: none; border-right: 1px solid gray; padding: 1px 3px;} </style>";


// Suche alle aktiven Eigner
$sqlAkiveEigner = "SELECT * FROM eigner WHERE active = 1";
$resAktiveEigner = $conn->query($sqlAkiveEigner);
while ($rowEigner = $resAktiveEigner->fetch_assoc()) {
    $eignerID = $rowEigner['id'];
    $eignerAlertEmail = $rowEigner['bv_email'];
    $eignerMeldung = "";

    // Zeitraum für den Daten geladen werden festlegen, je nach dem ob die Anlage regelmäßig Daten sendet oder nur einmal am Tag
    // Anlage sendet regelmäßig Daten
    $month = date("m", $yesterdayTimeStamp);
    $from = date("Y-m-d " . $GLOBALS['StartEndTimesAlert'][$month]['start'] . ":00:00", $yesterdayTimeStamp);
    $to = date("Y-m-d " . $GLOBALS['StartEndTimesAlert'][$month]['end'] . ":00:00", $yesterdayTimeStamp);
    $yesterdaySqlStamp = date("Y-m-d", $yesterdayTimeStamp);

    // SQL zur Abfrage der Anlagen
    $sqlAnlage = "SELECT * FROM db_anlage WHERE anl_data_go_ws='Yes' AND eigner_id = '$eignerID'";

    $resAnlagen = $conn->query($sqlAnlage); // Lade alle Anlagen

    // Warn Meldungen erzeugen
    // Wenn Datenlücken (Vortag) vorhanden dann E-Mail mit Hinweis Datenlücken
    // ermitteln der Abweichung der AC Gruppen (Vortag) und E-Mail mit Hinweis PR Abweichung und AC Gruppen ABweichung
    if ($resAnlagen->num_rows > 0) {
        while ($rowAnlage = $resAnlagen->fetch_assoc()) { // Für jede Anlage
            $anlagenID = $rowAnlage['anl_id'];
            $anlageMute = $rowAnlage['anl_mute'];
            $anlageHide = $rowAnlage['anl_hide_plant'];
            if ($rowAnlage['anl_mute'] != 'Yes' && $rowAnlage['anl_hide_plant'] != 'Yes') {

                $anlagenDbase = $rowAnlage['anl_dbase'];
                $anlageInternNr = $rowAnlage['anl_intnr'];
                $anlageName = $rowAnlage['anl_name'];
                $anlageZeitZoneInterne = $rowAnlage['anl_zeitzone'];
                $anlageZeitZoneWetterStation = $rowAnlage["anl_zeitzone_ws"];
                $anlageInputDaily = $rowAnlage['anl_input_daily'];
                $anlageHatACGruppe = $rowAnlage["anl_grupe"];
                $anlageGruppenCounter = $rowAnlage["anl_gr_count"];
                $anlageHatDCGruppe = $rowAnlage["anl_grupe_dc"];
                $anlagenUnit = $rowAnlage["anl_db_unit"];
                $anlagenWetterStationTyp = $rowAnlage['anl_data_wsst']; // "old/new"
                $groupsAC = show_data_group($anlagenID); // Groups AC (Name, InvNr, Min, Max)
                $groupsDC = show_data_group_dc($anlagenID); // Groups DC (Name, InvNr, Min, Max)
                $groupACAnzahl = count($groupsAC);

                $from = timeAjustment($from, $anlageZeitZoneInterne);
                $to = timeAjustment($to, $anlageZeitZoneInterne);

                $dbTableIst = "$istFirst$anlageInternNr"; //generierte Table
                $dbTableAcSoll = "$sollFirst$anlageInternNr"; //generierte Table
                $dbTableDcSoll = "$sollDcFirst$anlageInternNr";


                // PR vom Vortag lesen, wenn über schwellwert (im Moment 15%) dann weiter
                $sqlWarnmeldung = "SELECT warn_code, warn_info FROM db_anl_warn WHERE anl_id = '$anlagenID' AND stamp = '$yesterdaySqlStamp' AND warn_view = '1'";
                $resWarnmeldung = $conn->query($sqlWarnmeldung);
                if ($resWarnmeldung->num_rows == 1) {
                    $rowWarnmeldung = $resWarnmeldung->fetch_assoc();
                    $warnCode = $rowWarnmeldung['warn_code'];
                    $warnInfo = $rowWarnmeldung['warn_info'];
                    if ($warnInfo <= $GLOBALS['abweichung']['produktion']['red']) {
                        // Es liegt eine Warnmeldung vor.
                        $subject = "Für die Anlage: $anlageName liegen folgende Meldungen vor";
                        $eignerMeldung .= "<h2>Für die Anlage: $anlageName ($anlagenID) liegt folgende Störungsmeldung vor:</h2> ";
                        $eignerMeldung .= "<p><b>Die Abweichung SOLL/ IST für gestern ($yesterdaySqlStamp) liegt bei $warnInfo%</b></p>";


 ##                     // Auf Datenlücken prüfen
                        if (true) {
                            $sqlPvIst = "SELECT db_id, wr_num, inv, COUNT(inv) AS anz_records FROM $anlagenDbase.$dbTableIst WHERE stamp >= '$from' AND stamp <= '$to' GROUP BY inv";
                            //echo "$sqlPvIst<br>";
                            $resPvIst = $conn->query($sqlPvIst);
                            $dataGap = "";
                            $dataGapError = false;
                            if ($resPvIst->num_rows == 0) {
                                $dataGapError = true;
                                $dataGap .= "Es wurden keine Datensätze für diesen Zeitraum gefunden.<br>";
                            } else {
                                while ($rowPvIst = $resPvIst->fetch_assoc()) {
                                    $invGrp = $rowPvIst['inv'];
                                    $invnr = $rowPvIst['wr_num'];
                                    //Berechnen der Datensätze die pro Tag anfallen müssten, abhänig von der Anzahl der Inverter pro Gruppe und der Jahreszeit (je nach Jahreszeit betrachten wir einen andern Zeitraum)
                                    $anzRecordsPerDay = ($groupsAC[$invGrp]['GMAX'] - $groupsAC[$invGrp]['GMIN'] + 1) * ((($GLOBALS['StartEndTimesAlert'][$month]['end'] - $GLOBALS['StartEndTimesAlert'][$month]['start']) * 4) + 1);
                                    //echo "(" . $groupsAC[$invGrp]['GMAX'] . " - " . $groupsAC[$invGrp]['GMIN'] . " + 1) * (((" . $GLOBALS['StartEndTimesAlert'][$month]['end'] . " - " . $GLOBALS['StartEndTimesAlert'][$month]['start'] . ") * 4) + 1)<br>";
                                    //echo "Anzahl Records für Inverter Gruppe: $invGrp = $anzRecordsPerDay<br>";
                                    if ($rowPvIst['anz_records'] < $anzRecordsPerDay) {
                                        $dataGap .= "Inverter Gruppe: $invGrp - Inverter $invnr hat nur " . $rowPvIst['anz_records'] . " von $anzRecordsPerDay Datensätze.<br>";
                                        $dataGapError = true;
                                    } elseif ($rowPvIst['anz_records'] > $anzRecordsPerDay) {
                                        $dataGap .= "Inverter Gruppe: $invGrp - Inverter $invnr hat " . $rowPvIst['anz_records'] . " es sollten aber nur $anzRecordsPerDay Datensätze sein.<br>";
                                        $dataGapError = true;
                                    }
                                }
                            }
                            if (! $dataGapError) {
                                // keine Datenlücken
                                $eignerMeldung .= "<p>Im Zeitraum von $from bis $to haben wir <b>keine Datenlücken</b> festgestellt.</p>";
                            } else {
                                $eignerMeldung .= "<p>Wir haben die Daten von gestern, im Zeitraum von $from bis $to, auf Datenlücken geprüft und sind auf <b>folgenden Unstimmigkeiten</b> in den Daten gestoßen:</p>";
                                $eignerMeldung .= "<p>$dataGap</p>";
                            }

                            // ToDo: Auflisten der Datenlücken
                        }

##                      // AC Gruppen Betrachtung
                        if ($groupsAC && $anlageHatACGruppe == 'Yes' && true) {
                            $eignerMeldung .= "<h3>Abweichungen der AC Gruppen </h3><p>";
                            foreach ($groupsAC as $groupValue) {
                                $min    = $groupValue["GMIN"];
                                $max    = $groupValue["GMAX"];
                                $invnr  = $groupValue["INVNR"];
                                //Ist Daten AC und DC von gestern laden und in Array ArrACTGroup speichern
                                //$arrayACTGroup[0] = actviewdatachartgroup_sqltest($anlagenDbase, $dbTableIst, $from, $to, $anlageGruppenCounter, $invnr, $min, $max);


                                if ($anlageGruppenCounter == "No") {
                                    $sqlAcIstData = "SELECT DISTINCT `stamp` AS istdate, sum(`wr_pac`) as 'actpac', wr_num, inv AS grp_inv FROM $anlagenDbase.$dbTableIst WHERE stamp BETWEEN '$from' and '$to' and inv = '$invnr' GROUP by stamp ORDER BY istdate ASC";
                                } else {
                                    $sqlAcIstData = "SELECT DISTINCT `stamp` AS istdate, sum(`wr_pac`) as 'actpac', wr_num, inv AS grp_inv FROM $anlagenDbase.$dbTableIst WHERE `stamp` BETWEEN '$from' and '$to' and `wr_num` BETWEEN '$min' and '$max' GROUP by `stamp` ORDER BY `istdate` ASC";
                                }

                                $resultAcIstData = $conn->query($sqlAcIstData);
                                while ($rowAcIstData = $resultAcIstData->fetch_assoc()) {
                                    $actPowerAc += $rowAcIstData['actpac'];
                                    $actPowerDC += $rowAcIstData['actpdc'];
                                }

                                // SQL zum laden der EXP AC Daten des aktuellen Inverters für den zu vergleichenden Zeitraum
                                $sqlExpData = "SELECT DISTINCT `stamp` AS istdate, sum(`exp_kwh`) as 'exppac', grp_id FROM $anlagenDbase.$dbTableAcSoll WHERE `stamp` BETWEEN '$from' and '$to' and `grp_id` = '$invnr' GROUP by `stamp` ORDER BY `istdate` ASC";
                                $resultExpData = $conn->query($sqlExpData);
                                $numRowsResultExpData = $resultExpData->num_rows;
                                while ($rowExpData = $resultExpData->fetch_assoc()) {
                                    $expPowerAc += $rowExpData['exppac'];
                                }
                                //print_r($arrayACTGroup);
                                //$actPowerAc = round( actviewdatachartgroup_array_mw($arrayACTGroup, $timeIstAdjust, $d, "act") , 2);

                                if ($anlagenUnit == "w") { $actPowerAc = round($actPowerAc / 1000 / 4, 2);}
                                //$expPowerAc = round($expPowerAc / $numRowsResultExpData, 2);
                                $abweichungExpAct = round((100 - ($actPowerAc *100 / $expPowerAc)) * (-1), 2);
                                $eignerMeldung .= "SOLL / IST Abweichung in Gruppe: $invnr -> $abweichungExpAct% (Exp Power: $expPowerAc – Act Power: $actPowerAc)<br>";
                                $expPowerAc = 0;
                                $actPowerAc = 0;
                            }
                            $eignerMeldung .= "</p>";
                        }

##                      // DC Gruppen Betrachtung

                        if ($anlageHatDCGruppe == 'Yes' && true) {
                            $eignerMeldung .= "<h3>Abweichungen der DC Gruppen </h3><p>";
                            foreach ($groupsDC as $groupValueDC) {
                                $grpInvMin      = $groupValueDC['GMIN'];
                                $grpInvMax      = $groupValueDC['GMAX'];
                                $grpNr          = $groupValueDC['GRPNR'];

                                // SQL zum laden der IST DC Daten der aktuellen Inverter Gruppe für den zu vergleichenden Zeitraum
                                if ($anlageGruppenCounter == "No") {
                                    $sqlDcIstData = "SELECT DISTINCT stamp AS istdate, sum(wr_pdc) as actpdc FROM $anlagenDbase.$dbTableIst WHERE stamp BETWEEN '$from' and '$to' and inv = '$grpNr' GROUP by stamp ORDER BY istdate ASC";
                                } else {
                                    $sqlDcIstData = "SELECT DISTINCT stamp AS istdate, sum(wr_pdc) as actpdc FROM $anlagenDbase.$dbTableIst WHERE stamp BETWEEN '$from' and '$to' and wr_num BETWEEN $grpInvMin and $grpInvMax GROUP by stamp ORDER BY istdate ASC";
                                }
                                //echo "$sqlDcIstData<br>";
                                $resultDcIstData = $conn->query($sqlDcIstData);
                                while ($rowDcIstData = $resultDcIstData->fetch_assoc()) {
                                    $actPowerDC += $rowDcIstData['actpdc'];
                                }
                                if ($anlagenUnit == "w") {  $actPowerDC = $actPowerDC / 1000 / 4; }
                                $actPowerDC = round($actPowerDC, 2);

                                // SQL zum laden der EXP AC Daten des aktuellen Inverters für den zu vergleichenden Zeitraum
                                if ($anlageGruppenCounter == "No") {
                                    $sqlExpData = "SELECT DISTINCT stamp AS istdate, sum(soll_pdcwr) as exppdc, wr_num FROM $anlagenDbase.$dbTableDcSoll WHERE stamp BETWEEN '$from' and '$to' and wr_num = '$grpNr' GROUP by stamp ORDER BY istdate ASC";
                                } else {
                                    $sqlExpData = "SELECT DISTINCT stamp AS istdate, sum(soll_pdcwr) as exppdc, wr_num FROM $anlagenDbase.$dbTableDcSoll WHERE stamp BETWEEN '$from' and '$to' and wr BETWEEN $grpInvMin and $grpInvMax ORDER BY wr_num ASC";
                                }
                                //echo "$sqlExpData<br>";
                                $resultExpData = $conn->query($sqlExpData);
                                $numRowsResultExpData = $resultExpData->num_rows;
                                while ($rowExpData = $resultExpData->fetch_assoc()) {
                                    $expPowerDc += $rowExpData['exppdc'];
                                }

                                $expdiff = round (($actPowerDC - $expPowerDc) / $expPowerDc * 100, 2);

                                $eignerMeldung .= "SOLL / IST Abweichung in Gruppe: $grpNr -> $expdiff% (Exp Power: $expPowerDc – Act Power: $actPowerDC)<br>";
                                $actPowerDC = 0;
                                $expPowerDc = 0;
                            }
                        } else {
                            $eignerMeldung .= "<p><b>Anlage hat KEINE DC Gruppen</b></p>";
                        }

                        // füge CSS mit html meldung zusammen und gib sie aus
                        echo $style . $eignerMeldung;
                        echo "<br><hr><br>";
                        

##                      // Meldungen Senden
                        if (true) {
                            if ($eignerID != 14 && $eignerID != 17) {
                                //$eignerAlertEmail = "alert@g4npvplus.de";
                                $eignerAlertEmail = "mr@green4net.de, tl@green4net.de, alert@g4npvplus.de";
                                $carbonCopy = "";
                            } else {
                                $carbonCopy = "mr@green4net.de, tl@green4net.de, alert@g4npvplus.de";
                            }
                            $email_to = $eignerAlertEmail;
                            $email_from = "noreply@g4npvplus.de";
                            $message = $style . $eignerMeldung;
                            ($carbonCopy == "") ? $addHeader = "" : $addHeader = "CC: $carbonCopy\r\n";
                            echo sendMailWithoutAttachmend($email_to, $email_from, $subject, $message, $addHeader);
                        }
                        $eignerMeldung = "";

                    } else {
                        // keine PR Meldung die kritisch ist => mache nichts
                        // ToDo: Später sollen hier weiter Trigger für Fehlermeldungen kommen
                    }
                }
            } else {
                g4nLog("Da ist was Faul, keine Warnmeldung gefunden $sqlWarnmeldung", "alertSystem");
            }
        }
    } else {
            g4nLog("Anlage mit ID: $anlagenID ist gemutet ($anlageMute) oder hiden ($anlageHide)", "alertSystem");
    }
}


$conn->close();
echo "ende</pre>";


/**
 * @param array $content
 * @return string
 */
function printArrayAsTable(array $content)
{
    $_html = "<style>table, th, td {border: 1px solid black; }</style>";
    $_html .=  "<table>";
    $_counter = 0;
    foreach ($content as $key => $contentRow) {
        if ($_counter == 0) {
            $_html .= "<tr><th>Key</th>";
            foreach ($contentRow as $subkey => $subvalue) {
                $_html .= '<th>' . substr($subkey, 0, 20) . '</th>';
            }
            $_html .= "</tr>";
        }
        $_html .= "<tr><td>$key</td>";
        foreach ($contentRow as  $cell) {
            $_html .= "<td>$cell</td>";
        }
        $_html .= "</tr>";
        $_counter++;
    }
    $_html .= "</table><hr>";
    return $_html;
}