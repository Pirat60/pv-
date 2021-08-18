<?php
/***************************************
 * green4net.de - Matthias Reinhardt
 * sendAlertMessages.php - 2020/06/26
 * G4N-PVplus4
 ***************************************/

require_once '../pvp_v1/incl/config.php';
require_once '../pvp_v1/incl/functions.php';
require_once '../pvp_v1/incl/functions/emailAlert.php';
require_once '../pvp_v1/incl/functions/buildCharts.php';
require_once '../pvp_v1/incl/functionsNew/dataGetterDatabase.php';

echo "<pre>Start<br>";

$conn = connect_to_database();

$currentTimeStamp = g4nTimeCET();
$yesterdayTimeStamp = $currentTimeStamp - 24 * 60 * 60;

$messageTrenner = "<br>------------------------------<br><br>";

$owners = getAllOwners();
foreach ($owners as $ownerKey => $owner) {
    $plants = getAllPlants($owner['eignerId']);

    foreach ($plants as $plantKey => $plant) {
        $status_last = getPlantStatus($plant['anlId'], 'last');
        $status_before = getPlantStatus($plant['anlId'], 'second');

        // g4n Alerts (Weather and Data IO)
        $errorMessageIo = "";
        $sendMail = false;
        // Plant Data IO Error
        if ($status_last['lastDataStatus'] == 'alert' && $status_before['lastDataStatus'] == 'alert') {
            // Todo: Eintrag in error Log erstellen
            $errorMessageIo .= "Bei der Anlage " . $plant['anlName'] . "liegt ein 'Data IO' Fehler an. (letzter Datensatz von: " . $status_last['lastDataIo'];
            $errorMessageIo .= $messageTrenner;
            $sendMail = true;
        }
        // Weather IO Error
        if ($status_last['lastWeatherStatus'] == 'alert' && $status_before['lastWeatherStatus'] == 'alert') {
            // Todo: Eintrag in error Log erstellen
            $errorMessageIo .= "Bei der Anlage " . $plant['anlName'] . "liegt ein 'Weather IO' Fehler an. (letzter Datensatz von: " . $status_last['lastWeatherIo'];
            $errorMessageIo .= $messageTrenner;
            $sendMail = true;
        }

        if ($plant['anlId'] == '81') {
            //print_r($status_last);
            //print_r($status_before);
        }
        if ($sendMail) {
            $recipient = "alert@g4npvplus.de";
            $recipient = "mr@green4net.de"; // für Testzwecke
            $sender = "noreply@g4npvplus.de";
            $subject = "IO Error bei Anlage " . $plant['anlName'];

            //sendMailWithoutAttachmend($recipient, $sender, $subject, $errorMessageIo );
        }



        // Owner Alerts
        $errorMessageOwner = "";
        $sendMail = false;
        // DC Produktion
        if ($status_last['dcErrorCode'] == '2' && $status_before['dcErrorCode'] == '2') {
            $errorMessageOwner .= "Bei der Anlage " . $plant['anlName'] . " liegt ein 'DC Power' Fehler an. (letzter gemeinsamer Datensatz von: " . $status_last['stampLastBoth'] . "<br>";
            $errorMessageOwner .= "&nbsp;&nbsp;-- dc actual: " . $status_last['dcActBoth'] . " dc expected: " . $status_last['dcExpBoth'] . " Verlust: " . $status_last['dcLostPercent'] . "%";
            $errorMessageOwner .= $messageTrenner;
            $sendMail = true;
        }
        // AC Produktion
        if ($status_last['acErrorCode'] == '2' && $status_before['acErrorCode'] == '2') {
            $errorMessageOwner .= "Bei der Anlage " . $plant['anlName'] . " liegt ein 'AC Power' Fehler an. (letzter gemeinsamer Datensatz von: " . $status_last['stampLastBoth'] . "<br>";
            $errorMessageOwner .= "&nbsp;&nbsp;-- ac actual: " . $status_last['acActBoth'] . " ac expected: " . $status_last['acExpBoth'] . " Verlust: " . $status_last['acLostPercent'] . "%";
            $errorMessageOwner .= $messageTrenner;
            $sendMail = true;
        }
        if ($status_last['invAnzAlert'] > 0 && $status_before['invAnzAlert'] > 0) {
            $errorMessageOwner .= "Bei der Anlage " . $plant['anlName'] . " liegt ein 'Inverter' Fehler an. <br>";
            $errorMessageOwner .= $status_last['invErrorMessage'];
            $errorMessageOwner .= $messageTrenner;
            $sendMail = true;
        }
        if (($status_last['stringIAlerts'] > 0 && $status_before['stringIAlerts'] > 0) || ($status_last['stringUAlerts'] > 0 && $status_before['stringUAlerts'] > 0)){
            $errorMessageOwner .= "Bei der Anlage " . $plant['anlName'] . " liegt ein 'String' Fehler an. <br>";
            $errorMessageOwner .= $status_last['stringErrorMessage'];
            $errorMessageOwner .= $messageTrenner;
            $sendMail = true;
        }

        if ($sendMail) {
            $recipient = "alert@g4npvplus.de";
            $recipient = "mr@green4net.de"; // für Testzwecke
            $sender = "noreply@g4npvplus.de";
            $subject = "Fehlermeldungen bei Anlage " . $plant['anlName'];

            sendMailWithoutAttachmend($recipient, $sender, $subject, $errorMessageOwner );
        }
    }

}
echo "Ende :-(";

