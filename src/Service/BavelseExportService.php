<?php


namespace App\Service;


use App\Entity\Anlage;
use App\Entity\AnlageAcGroups;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\PRRepository;
use DateTime;
use PDO;

class BavelseExportService
{
    use G4NTrait;

    private FunctionsService $functions;
    private PRRepository $PRRepository;
    private AnlageAvailabilityRepository $anlageAvailabilityRepo;

    public function __construct(FunctionsService $functions, PRRepository $PRRepository,AnlageAvailabilityRepository $anlageAvailabilityRepo)
    {

        $this->functions = $functions;
        $this->PRRepository = $PRRepository;
        $this->anlageAvailabilityRepo = $anlageAvailabilityRepo;
    }

    public function gewichtetTagesstrahlung(Anlage $anlage, DateTime $from = null, DateTime $to = null):string
    {
        $help = '<tr><th></th>';
        $output = "<b>" . $anlage->getAnlName() . "</b><br>";
        $output .= "<div class='table-scroll'><table><thead><tr><th>Datum</th>";
        foreach ($anlage->getAcGroups() as $groupAC) {
            $output .= "<th>" . $groupAC->getAcGroupName() . "</th><th></th><th></th>";
            $help   .= "<th><small>Irr [kWh/qm]</small></th><th></th><th><small>gewichtete TheoPower mit TempCorr [kWh]</small></th>";
        }
        $output .= "<td>Verfügbarkeit</td><td>gewichtete Strahlung</td><td>gewichtete TheoPower ohne TempCorr</td><td>gewichtete TheoPower mit TempCorr</td></tr>";
        $help   .= "<td>[%]</td><td>[kWh/qm]</td><td>[kWh]</td><td></td></tr>";
        $output .= $help . "</thead><tbody>";
        if ($from === null) $from = date_create('2021-05-27');
        if ($to === null)   $to   = date_create('2021-06-09');

        /** @var AnlageAcGroups $groupAC */
        /** @var DateTime $from */
        /** @var DateTime $to */
        for ($stamp = $from->format('U'); $stamp <= $to->format('U'); $stamp += 86400) {
            $gewichteteStrahlung = $gewichteteTheoPower = $gewichteteTheoPower2 = 0;
            $output .= "<tr>";
            $output .= "<td>".date('Y-m-d', $stamp)."</td>";

            // für jede AC Gruppe ermittele Wetterstation, lese Tageswert und gewichte diesen
            foreach ($anlage->getAcGroups() as $groupAC) {
                $weather = $this->functions->getWeather($groupAC->getWeatherStation(), date( 'Y-m-d 00:00', $stamp), date('Y-m-d 23:59', $stamp), null, null);
                $acPower = $this->functions->getSumAcPowerByGroup($anlage, date( 'Y-m-d 00:00', $stamp), date('Y-m-d 23:59', $stamp), $groupAC->getAcGroup());

                if ($groupAC->getIsEastWestGroup()) {
                    if ($weather['upperIrr'] > 0 && $weather['lowerIrr'] > 0) {
                        $irradiation = ($weather['upperIrr'] + $weather['lowerIrr']) / 2;
                    } elseif ($weather['upperIrr'] > 0) {
                        $irradiation = $weather['upperIrr'];
                    } else {
                        $irradiation = $weather['lowerIrr'];
                    }
                } else {
                    $irradiation = $weather['upperIrr'];
                }
                // TheoPower gewichtet berechnen
                $output .= "<td><small>" . round($weather['upperIrr'] / 1000 / 4,2) . "</small></td><td><small>" . round($weather['lowerIrr'] / 1000 / 4,2) . "</small></td><td><small>".round($acPower['powerTheo'],2)."</small></td>";

                // Temepratur Correction pro Tag berechnen
                #$tempCorrection = $this->functions->tempCorrection( $anlage, $groupAC->getTCellAvg(), $weather['windSpeed'], $weather['airTemp'] , $irradiation / $weather['anzahl']);
                // Aufsummieren der gewichteten Werte zum gesamt Wert
                #$gewichteteTheoPower    += $groupAC->getGewichtungAnlagenPR() * $anlage->getKwPeak() * $irradiation * $tempCorrection;
                $gewichteteTheoPower   += $acPower['powerTheo'];
                $gewichteteStrahlung    += $groupAC->getGewichtungAnlagenPR() * $irradiation;
                $availability = $this->anlageAvailabilityRepo->sumAvailabilityPerDay($anlage->getAnlId(), date('Y-m-d', $stamp));
            }
            $output .= "<td>".round($availability,2)."</td>";
            $output .= "<td>".round($gewichteteStrahlung / 1000 / 4,2)."</td>";
            #$output .= "<td>".round($gewichteteTheoPower2,2)."</td>";
            $output .= "<td>".round($gewichteteTheoPower,2)."</td></tr>";
        }
        $output .= "</tbody></table></div>";
        return $output;
    }

    public function getRawData(Anlage $anlage, DateTime $from = null, DateTime $to = null):string
    {
        $conn = self::getPdoConnection();
        $output = '';
        $fromSql = $from->format('Y-m-d 00:00');
        $toSql = $from->format('Y-m-d 23:59');

        $sql = "SELECT a.stamp as a_stamp, b.* FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameIst() . ") b ON a.stamp = b.stamp) WHERE a.stamp >= '$fromSql' AND a.stamp <= '$toSql';";
        $sql = "SELECT * FROM " . $anlage->getDbNameIst() . " WHERE stamp >= '$fromSql' AND stamp <= '$toSql';";

        $res = $conn->query($sql);
        if ($res->rowCount() > 0) {
            $rows = $res->fetchAll(PDO::FETCH_ASSOC);
            $fp = fopen("daten ".$from->format('Y-m-d').".csv", 'a');
            $export['timestamp']  = 'Timestamp';
            $export['section']  = 'Section';
            $export['inverter']  = 'Inverter';
            $export['p_ac']  = 'Power_AC';
            $export['temp_corr']  = 'Temp_Corr';
            $export['theo_power']  = 'Theo_Power';
            for ($n = 1; $n <= 32; $n++) {
                $key = sprintf("GM_%'.02d", $n);
                $export[$key]  = $key;
            }
            for ($n = 1; $n <= 32; $n++) {
                $key = sprintf("TM_%'.02d", $n);
                $export[$key]  = $key;
            }
            for ($n = 1; $n <= 3; $n++) {
                $key = sprintf("TA_%'.02d", $n);
                $export[$key]  = $key;
            }
            $export['wind_speed']  = 'Wind_Speed';
            $export['wind_direction']  = 'Wind_Direction';
            fputcsv($fp, $export);
            unset($export);
            foreach ($rows as $rowsKey => $row) {
                $export['timestamp']        = $row['stamp'];
                $export['section']          = (float)$row['group_ac'];
                $export['inverter']         = (float)$row['unit'];
                $export['p_ac']             = (float)$row['wr_pac'];
                $export['temp_corr']        = (float)$row['temp_corr'];
                $export['theo_power']       = (float)$row['theo_power'];
                $irrArray                   = json_decode($row['irr_anlage'], true);
                $tempAnlage                 = json_decode($row['temp_anlage'], true);
                $windAnlage                 = json_decode($row['wind_anlage'], true);
                //dump($irrArray);
                for ($n = 1; $n <= 32; $n++) {
                    $key = sprintf("GM_%'.02d", $n);
                    $export[$key]           = $irrArray[$key];
                }
                for ($n = 1; $n <= 32; $n++) {
                    $key = sprintf("TM_%'.02d", $n);
                    $export[$key]           = $tempAnlage[$key];
                }
                $export["TA_TS01"]           = $tempAnlage["TA_TS01"];
                $export["TA_TS05"]           = $tempAnlage["TA_TS05"];
                $export["TA_TS10"]           = $tempAnlage["TA_TS10"];

                $export['wind_speed']       = $windAnlage["WS_TS06"];
                $export['wind_direction']   = $windAnlage["WD_TS06"];
                fputcsv($fp, $export);
                unset($export);
            }
            fclose($fp);
        }
        unset($res);

        return $output;
    }


}


