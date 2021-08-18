<?php
/***************************************
 * green4net.de - Matthias Reinhardt
 * createMonthlyReport.php - 2020/06/30
 * G4N-PVplus4
 ***************************************/

require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';
require_once '../pvp_v1/incl/functions/report.php';
require_once '../pvp_v1/incl/functionsNew/dataGetterDatabase.php';
require_once '../pvp_v1/module/TCPDF-6.3.5/tcpdf.php';

echo "<pre>";

$conn = connect_to_database();

$currentTime = g4nTimeCET();
$yesterday = $currentTime - 86400;
$currentTimeSql = g4nTimeCET('SQL');
$reportMonth = date('m', $yesterday);
$reportMonthText = date('F', $yesterday);
$reportYear = date('Y', $yesterday);
$lastDayMonth = date('t', $yesterday);
$from = "$reportYear-$reportMonth-01 00:00";
$to   = "$reportYear-$reportMonth-$lastDayMonth 23:59";

if (true) { // true für debuging mit festem Zeitraum
    $from = "2020-06-01 00:00"; // zum testen und debuggen
    $to   = "2020-06-30 23:59"; // zum testen und debuggen
    $reportMonth = '06';
    $reportYear  = '2020';
}


echo "Month: $reportMonth - Year: $reportYear - $from to $to<br>";

$dbEigner           = $GLOBALS['databases']['eigner'];
$dbAnlage           = $GLOBALS['databases']['dbAnlage'];
$dbAnlageBericht    = $GLOBALS['databases']['dbAnlageBericht'];
$dbAnlageBerichte   = $GLOBALS['databases']['dbAnlageBerichte'];
$dbAnlagePr         = $GLOBALS['databases']['dbAnlagePr'];

$owners = getAllOwners();
foreach ($owners as $ownerKey => $owner) {
    $plants = getAllPlants($owner['eignerId']);

    foreach ($plants as $plantKey => $plant) {
        $anlName = $plant['anlName'];
        $anlLeistung = $plant['anlLeistung'];
        $anlIntnr = $plant['anlIntnr'];
        $anlGeoLat = $plant['anlGeoLat'];
        $anlGeoLon = $plant['anlGeoLon'];
        $aid = $plant['anlId'];

        if (true) {
            echo "$anlName<br>";
            $reportHeader = '
                <table style="width:650px">
                    <tr>
                        <td align="right" style="vertical-align:center; text-align: right;"><img src="https://g4npvplus.de/images/form/image007.png" style="float:right; height:55px; width:205px" /></td>
                    </tr>
                    <tr>
                        <td style="vertical-align:center"><img src="https://g4npvplus.de/images/form/image001.png" width="650px" height="9px" /></td>
                    </tr>
                </table>
                <p>&nbsp;</p>
                <table border="0">
                    <td style="vertical-align:top; width:10px"><img src="https://g4npvplus.de/images/form/image001.png" style="float:left; height:180px; margin-right:10px; width:9px"/></td>
                    <td width="10px">&nbsp;</td>
                    <td style="height:5px; vertical-align:top">
                        <h1>Monthly Report</h1>
                        <p>'.$reportMonthText.' '.$from.'&nbsp;/&nbsp;'.$to.' '.$reportYear.'</p>
                        <p>Plant Geo Lat.&nbsp;'.$anlGeoLat.'<br>Plant Geo Lon.&nbsp;'.$anlGeoLon.'</p>
                        <p>PV-Plant Name:&nbsp;&nbsp;<strong>'.$anlName.'</strong><br>
                        PV-Plant Size:&nbsp;&nbsp;&nbsp;&nbsp;<strong>'.$anlLeistung.'&nbsp;kWp</strong><br>
                        Ident.-Nr.:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>'.$anlIntnr.'</strong></p>
                    </td>
                </table>
                <p>&nbsp;</p>
            ';

            $sqlm    = "SELECT `anl_id`, DATE_FORMAT(`pr_stamp_ist`,'%d-%m-%Y') AS stamp, `pr_act`, `pr_exp`, `pr_diff`, `pr_diff_poz`, `irradiation`, `pr_act_poz`, `pr_exp_poz`, `panneltemp` 
                    FROM $dbAnlagePr WHERE `anl_id` = '$aid' AND MONTH(`pr_stamp_ist`) = '$reportMonth' AND YEAR(`pr_stamp_ist`) = '$reportYear' GROUP BY `pr_stamp_ist` 
                    ORDER BY `pr_stamp_ist` ASC";
            $sqlmaxm = "SELECT `pr_stamp_ist`, avg(`pr_exp`) AS maxi FROM $dbAnlagePr 
                    WHERE `anl_id` = '$aid' and  MONTH(`pr_stamp_ist`) = '$reportMonth' and YEAR(`pr_stamp_ist`) = '$reportYear' 
                    GROUP by `pr_stamp_ist` 
                    ORDER BY `maxi` DESC LIMIT 1";

            $reportContent = reports_maker($sqlm, $sqlmaxm, "new", "monthly");

            // Erstellung des PDF Dokuments
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Dokumenteninformationen
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor("green4net");
            $pdf->SetTitle('Report ');
            $pdf->SetSubject('Report ');

            $pdf->SetMargins(10, 5, 5, true);
            $pdf->SetHeaderMargin(8);
            $pdf->SetFooterMargin(8);

            // Auswahl des Font
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            // Call before the addPage() method
            $pdf->SetPrintHeader(false);
            // Automatisches Autobreak der Seiten
            $pdf->SetAutoPageBreak(true, PDF_MARGIN_BOTTOM);

            // Image Scale
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            // Schriftart
            $pdf->SetFont('Helvetica', '', 9);

            // Neue Seite
            $pdf->AddPage();

            // Fügt den HTML Code in das PDF Dokument ein
            $pdfhtml = $reportHeader . $reportContent;

            $pdf->writeHTML($pdfhtml, true, false, true, false, '');
            ob_end_clean();

            $filename = "MonthlyReport_".$reportYear."_".$reportMonth."_".$anlName.".pdf";
            $pdfFile  = $pdf->Output($filename, 'E');

            $mailTo = "alert@g4npvplus.de";
            $mailTo = "mr@green4net.de";
            $mailfrom = "noreply@g4npvpuls.de";
            $mailSubject = "MonthlyReport $reportYear/$reportMonth - $anlName";
            $mailMessage = "<p>Please see the attachment.</p>";

            $mime_boundary = "-----=" . md5(uniqid(microtime(), true));
            $encoding = mb_detect_encoding($mailMessage, "utf-8, iso-8859-1, cp-1252");

            $header  = 'From: "'.addslashes($mailfrom).'" <'.$mailfrom.">\r\n";
            $header .= "MIME-Version: 1.0\r\n";
            $header .= "Content-Type: multipart/mixed; charset=\"$encoding\"\r\n";
            $header .= " boundary=\"".$mime_boundary."\"\r\n";

            $content  = "This is a multi-part message in MIME format.\r\n\r\n";
            $content .= "--".$mime_boundary."\r\n";
            $content .= "Content-Type: text/html; charset=\"$encoding\"\r\n";
            $content .= "Content-Transfer-Encoding: 8bit\r\n";
            $content .= $mailMessage."\r\n";
            $content .= "--".$mime_boundary."\r\n";
            $content .= $pdfFile."\r\n";
            $content .= "--".$mime_boundary."--";

            echo mail($mailTo, $mailSubject, $content, $header);

            echo '<hr>';
        }



        /*
        $sql_insert = "INSERT INTO $dbAnlageBericht (`eigner_id`,`anl_id`,`report_ist`,`report_month`,`report_year`,`report_code`) VALUES ('$eide','$aid','MR','$r_monat','$r_jahr','$inhalt')";
        $conn->query($sql_insert);
        $repid = $conn->insert_id; // lese die gerade erzeugte Report ID aus
        $sqlSelectPlants = "SELECT anl_name FROM web32_db3.db_anlage WHERE anl_id=$aid LIMIT 1";
        $plantsList = $conn->query($sqlSelectPlants);
        $row = $plantsList->fetch_assoc();
        $anlName = "MR " . $row['anl_name'] . "_" . $r_monat . "_" . $r_jahr;
        $sql_rep_insert = 'INSERT INTO `db_anl_berichte` (`eigner_id`,`anl_id`,`br_create_date`,`rep_id`,`br_name`,`br_ist`,`br_checkin`) VALUES ("' . $eide . '","' . $aid . '", CURRENT_TIMESTAMP, "' . $repid . '", "' . $anlName . '", "MR", "1")';
        $conn->query($sql_rep_insert);
        */
    }
}
echo "fertig</pre>";
