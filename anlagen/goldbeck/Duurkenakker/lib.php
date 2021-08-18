<?php
/**
 * Load Data LELYSTAD 1
 * mr 29-01-21
 */

function loadData($assignValueArray, $from, $to) {
    $output = '';
    $anlagenTabelle = DATABASE_ID;
    $anlagenId = ANLAGEN_ID;
    $systemKey = PLANT_KEY;
    $weatherDbIdent = WEATHER_DB_IDENT;

    $bulkMeaserments = getSystemsKeyBulkMeaserments($systemKey, $from, $to);

    if ($bulkMeaserments != false) {
        $output.= "Daten per API geladen<br>\n";
        $basics = $bulkMeaserments['basics'];
        $inverterArray = $bulkMeaserments['inverters'];
        $sensors        = $bulkMeaserments['sensors'];

        foreach ($inverterArray as $date => $inverter) {
            if (date("I") == "1") {
                //wir haben Sommerzeit
                $stamp = date('Y-m-d H:i', strtotime($date) - 3600);
            } else {
                // wir haben Winterzeit
                $stamp = date('Y-m-d H:i', strtotime($date));
            }
            $irrAnlageArray['G_M0'] = $basics[$date]['G_M0'];
            $irrAnlageArray['G_ME_TS02'] = $sensors[$date]['274767']['SRAD']; //$basics[$date]['G_M20']; // East
            $irrAnlageArray['G_MW_TS02'] = $sensors[$date]['274766']['SRAD']; //$basics[$date]['G_M21']; // West
            $irrAnlageArray['G_ME_TS04'] = $sensors[$date]['275418']['SRAD']; //$basics[$date]['G_M22']; // East
            $irrAnlageArray['G_MW_TS04'] = $sensors[$date]['275416']['SRAD']; //$basics[$date]['G_M23']; // West
            $irrAnlageArray['G_ME_TS05'] = $sensors[$date]['281677']['SRAD']; //$basics[$date]['G_M24']; // East
            $irrAnlageArray['G_MW_TS05'] = $sensors[$date]['281676']['SRAD']; //$basics[$date]['G_M25']; // West

            $tempAnlageArray['T_MW_TS02'] = $sensors[$date]['281566']['T1'];
            $tempAnlageArray['T_ME_TS02'] = $sensors[$date]['281567']['T1'];
            $tempAnlageArray['T_MW_TS04'] = $sensors[$date]['276313']['T1'];
            $tempAnlageArray['T_ME_TS04'] = $sensors[$date]['276314']['T1'];

            $tempAnlageArray['T_A_TS02'] = $sensors[$date]['281565']['T2'];
            $tempAnlageArray['T_A_TS04'] = $sensors[$date]['276312']['T2'];
            $tempAnlageArray['T_A_TS05'] = $sensors[$date]['281680']['T2'];

            $tempAmbient = ($sensors[$date]['281680']['T2'] + $sensors[$date]['281565']['T2'] + $sensors[$date]['276312']['T2'] / 3);
            $tempPanel   = ($sensors[$date]['281566']['T1'] + $sensors[$date]['281567']['T1'] + $sensors[$date]['276313']['T1'] / 3);

            #$windAnlageArray['239591']['E_W_D'] = $sensors[$date]['239591']['E_W_D'];
            #$windAnlageArray['239592']['E_W_S'] = $sensors[$date]['239592']['E_W_S'];
            $windSpeed = 0; #$sensors[$date]['239592']['E_W_S'];
            ($irrAnlageArray['G_ME_TS02'] > 0) ? $irrUpper = $irrAnlageArray['G_ME_TS02'] : $irrUpper = 0; // East
            ($irrAnlageArray['G_MW_TS02'] > 0) ? $irrLower = $irrAnlageArray['G_MW_TS02'] : $irrLower = 0; // West
            insertWeatherToWeatherDb($weatherDbIdent, $stamp, $irrUpper, $irrLower, $tempPanel, $tempAmbient, $windSpeed);
            #insertWindToWeatherDb('BX102', $stamp, $windSpeed); // Wind Daten für Lelystad 2 Zur verfügung stellen

            $eZEvu = round($basics[$date]['E_Z_EVU'], 0);
            $irrAnlage = json_encode($irrAnlageArray);
            $tempAnlage = json_encode($tempAnlageArray);
            #$windAnlage = json_encode($windAnlageArray);

            foreach ($assignValueArray as $assign) {
                $pvpInverter = $assign[0];
                $pvpGroupDc = $assign[1];
                $pvpGroupAc = $assign[2];
                $custInverterKennung = $assign[3];
                $currentDc = $inverter[$custInverterKennung]['I_DC'] + 0;
                $currentAc = $inverter[$custInverterKennung]['I_AC'] + 0;
                $voltageDc = $inverter[$custInverterKennung]['U_DC'] + 0;
                $powerDc = round($currentDc * $voltageDc / 1000 / 4, 2); // Umrechnung von Watt auf kW/h
                $powerAc = round($inverter[$custInverterKennung]['P_AC'] / 1000 / 4, 2); // Umrechnung von Watt auf kW/h
                $invKeyBasic = sprintf("E_Z_ST%'.02d", $assign[1]); // %'.02d = fülle mit nullen nach links auf, max. 2 Stellen (aus 1 wird 01, aus 10 wird 10)

                $temp = round(($inverter[$custInverterKennung]['T_WR']), 2);
                $cosPhi = $inverter[$custInverterKennung]['COS_PHI'] + 0;

                $dcCurrentMpp = "{}";
                $dcVoltageMpp = "{}";
                //echo "($stamp, $pvpGroupAc, $pvpGroupDc, $pvpInverter, $powerAc, $powerDc, $voltageDc, $currentDc, $temp, $anlagenId, $anlagenTabelle, $cosPhi, $eZEvu, $dcCurrentMpp, $dcVoltageMpp, $irrAnlage, $tempAnlage)<br>";
                insertDataIntoPvIstAcV3($stamp, $pvpGroupAc, $pvpGroupDc, $pvpInverter, $powerAc, $powerDc, $voltageDc, $currentDc, $temp, $anlagenId, $anlagenTabelle, $cosPhi, $eZEvu, $dcCurrentMpp, $dcVoltageMpp, $irrAnlage, $tempAnlage);
            }
        }
    }
    $output .= "from: ".date('Y-m-d H:i', $from)." to: ".date('Y-m-d H:i', $to)."<br>\n";

    return $output;
}
