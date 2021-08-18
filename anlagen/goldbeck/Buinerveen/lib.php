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
            $irrAnlageArray['G_H021'] = $basics[$date]['G_H021'];
            $irrAnlageArray['G_M021'] = $basics[$date]['G_M021'];
            $irrAnlageArray['G_H041'] = $basics[$date]['G_H041'];
            $irrAnlageArray['G_M041'] = $basics[$date]['G_M041'];
            $irrAnlageArray['G_H111'] = $basics[$date]['G_H111'];
            $irrAnlageArray['G_M111'] = $basics[$date]['G_M111'];

            $tempAnlageArray['239877'] = $sensors[$date]['239877']['T'];
            $tempAnlageArray['239876'] = $sensors[$date]['239876']['T'];
            $tempAnlageArray['239594'] = $sensors[$date]['239594']['T'];
            $tempAnlageArray['239595'] = $sensors[$date]['239595']['T'];
            $tempAmbient = ($sensors[$date]['239594']['T'] + $sensors[$date]['239595']['T'] + $sensors[$date]['239877']['T'] + $sensors[$date]['239876']['T']) / 4;
            $tempPanel   = 0;



            #$windAnlageArray['239591']['E_W_D'] = $sensors[$date]['239591']['E_W_D'];
            #$windAnlageArray['239592']['E_W_S'] = $sensors[$date]['239592']['E_W_S'];
            $windSpeed = 0; #$sensors[$date]['239592']['E_W_S'];
            ($irrAnlageArray['G_M0'] > 0) ? $irrUpper = $irrAnlageArray['G_M0'] : $irrUpper = 0;
            #($irrAnlageArray['G_M11'] > 0) ? $irrLower = $irrAnlageArray['G_M11'] : $irrLower = 0;
            $irrLower = 0;
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
                insertDataIntoPvIstAcV3($stamp, $pvpGroupAc, $pvpGroupDc, $pvpInverter, $powerAc, $powerDc, $voltageDc, $currentDc, $temp, $anlagenId, $anlagenTabelle, $cosPhi, $eZEvu, $dcCurrentMpp, $dcVoltageMpp, $irrAnlage, $tempAnlage);
            }
        }
    }
    $output .= "from: ".date('Y-m-d H:i', $from)." to: ".date('Y-m-d H:i', $to)."<br>\n";

    return $output;
}
