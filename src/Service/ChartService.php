<?php

namespace App\Service;

use App\Repository\InvertersRepository;
use PDO;
use DateTime;
use App\Entity\Anlage;
use App\Entity\AnlageForecast;
use App\Entity\AnlagenPR;
use App\Helper\G4NTrait;
use App\Repository\AnlageAvailabilityRepository;
use App\Repository\AnlagenStatusRepository;
use App\Repository\ForecastRepository;
use App\Repository\PVSystDatenRepository;
use App\Repository\PRRepository;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Intl\Timezones;
use Symfony\Component\Validator\Constraints\Timezone;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;


class ChartService
{
    use G4NTrait;

    private Security $security;
    private AnlagenStatusRepository $statusRepository;
    private AnlageAvailabilityRepository $availabilityRepository;
    private PRRepository $prRepository;
    private PVSystDatenRepository $pvSystRepository;
    private ForecastRepository $forecastRepo;
    private InvertersRepository $invertersRepo;
    private FunctionsService $functions;

    public function __construct(Security $security,
                                AnlagenStatusRepository $statusRepository,
                                AnlageAvailabilityRepository $availabilityRepository,
                                PRRepository $prRepository,
                                PVSystDatenRepository $pvSystRepository,
                                ForecastRepository $forecastRepo,
                                InvertersRepository $invertersRepo,
                                FunctionsService $functions)
    {
        $this->security = $security;
        $this->statusRepository = $statusRepository;
        $this->availabilityRepository = $availabilityRepository;
        $this->prRepository = $prRepository;
        $this->pvSystRepository = $pvSystRepository;
        $this->forecastRepo = $forecastRepo;
        $this->invertersRepo = $invertersRepo;
        $this->functions = $functions;
    }

    /**
     * @param $form
     * @param Anlage|null $anlage
     * @return array
     */
    public function getGraphsAndControl($form, ?Anlage $anlage)
    {
        $resultArray = [];
        $resultArray['data'] = '';
        $resultArray['showEvuDiag'] = 0;
        $resultArray['showCosPhiDiag'] = 0;
        $resultArray['showCosPhiPowerDiag'] = 0;
        $resultArray['actSum'] = 0;
        $resultArray['expSum'] = 0;
        $resultArray['evuSum'] = 0;
        $resultArray['cosPhiSum'] = 0;
        $resultArray['headline'] = '';
        $resultArray['series1']['name'] = "";
        $resultArray['series1']['tooltipText'] = "";
        $resultArray['series2']['name'] = "";
        $resultArray['series2']['tooltipText'] = "";
        $resultArray['seriesx']['name'] = "";
        $resultArray['seriesx']['tooltipText'] = "";
        $resultArray['offsetLegende'] = 0;
        $resultArray['rangeValue'] = 0;
        $resultArray['maxSeries'] = 0;
        $resultArray['hasLink'] = false;
        $currentYear = date("Y");
        $currentMonth = date("m");
        $currentDay = date("d");

        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
        if($form['backFromMonth']){
            $form['from'] =  date("Y-m-d 00:00", (strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay) - (86400 * ($form['optionDate'] - 1))));
            $form['to'] =  date("Y-m-d 23:59", strtotime($currentYear.'-'.$currentMonth.'-'.$currentDay));
        }

        $from = self::timeShift($anlage, $form['from'],true);
        $to = self::timeShift($anlage, $form['to'],true);

        if ($anlage) {
            $showEvuDiag = $anlage->getShowEvuDiag();
            $showCosPhiPowerDiag = $anlage->getShowCosPhiPowerDiag();

            switch ($form['selectedChart']) {
                case ("ac_single"):

                    $dataArray = $this->getActExpAC($anlage, $from, $to);
                    #dd($dataArray['chart']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['showEvuDiag'] = $showEvuDiag;
                        $resultArray['showCosPhiPowerDiag'] = $showCosPhiPowerDiag;
                        $resultArray['actSum'] = $dataArray['actSum'];
                        $resultArray['expSum'] = $dataArray['expSum'];
                        $resultArray['evuSum'] = $dataArray['evuSum'];
                        $resultArray['cosPhiSum'] = $dataArray['cosPhiSum'];
                        $resultArray['headline'] = 'AC production [kWh] ??? actual and expected';
                    }
                    break;
                case ("ac_act_group"):
                    $dataArray = $this->getAcExpGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production by Group [kWh] ??? Actual and Expected';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected ";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case ("ac_grp_power_diff"): // AC - Inverter
                    $dataArray = $this->getGroupPowerDifferenceAC($anlage, $from, $to);
                    #dd($dataArray['chart']);
                    if ($dataArray != false) {
                        //dd($dataArray);
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['hasLink'] = false;
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Inverter Production [V]';
                        $resultArray['series1']['name'] = "Actual Inverter ";
                        $resultArray['series1']['tooltipText'] = "Actual Inverter [V] Group ";
                    }
                    break;
                case ("ac_act_voltage"):
                    $dataArray = $this->getActVoltageGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Voltage [V] ??? Actual';
                        $resultArray['series0']['name'] = "Actual";
                        $resultArray['series0']['tooltipText'] = "Actual ";
                        $resultArray['series1']['name'] = "Actual_P1";
                        $resultArray['series1']['tooltipText'] = "Actual Phase 1";
                        $resultArray['series2']['name'] = "Actual_P2";
                        $resultArray['series2']['tooltipText'] = "Actual Phase 2";
                        $resultArray['series3']['name'] = "Actual_P3";
                        $resultArray['series3']['tooltipText'] = "Actual Phase 3";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                    }
                    break;
                case ("ac_act_current"):
                    $dataArray = $this->getActCurrentGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Current [A] ??? Actual';
                        $resultArray['series0']['name'] = "Actual";
                        $resultArray['series0']['tooltipText'] = "Actual ";
                        $resultArray['series1']['name'] = "Actual_P1";
                        $resultArray['series1']['tooltipText'] = "Actual Phase 1";
                        $resultArray['series2']['name'] = "Actual_P2";
                        $resultArray['series2']['tooltipText'] = "Actual Phase 2";
                        $resultArray['series3']['name'] = "Actual_P3";
                        $resultArray['series3']['tooltipText'] = "Actual Phase 3";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                    }
                    break;
                case ("ac_act_frequency"):
                    $dataArray = $this->getActFrequncyGroupAC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'AC Production Frequency [HZ] ??? Actual';
                        $resultArray['series1']['name'] = "Actual";
                        $resultArray['series1']['tooltipText'] = "Actual ";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                    }
                    break;
                case ("dc_single"):
                    $dataArray = $this->getActExpDC($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['actSum'] = $dataArray['actSum'];
                        $resultArray['expSum'] = $dataArray['expSum'];
                        $resultArray['headline'] = 'DC Production [kWh] ??? Actual and Expected';
                    }
                    break;
                case ("dc_act_group"):
                    $dataArray = $this->getActExpGroupDC($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        //$resultArray['label'] = $dataArray['label'];
                        $resultArray['headline'] = 'DC Production by Group [kWh]';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected ";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Inverter ";
                        $resultArray['inverterArray'] = json_encode($dataArray['inverterArray']);
                    }
                    break;
                case ("dc_grp_power_diff"): // DC - Inverter (DC - Inverter Group)
                    $dataArray = $this->getGroupPowerDifferenceDC($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['hasLink'] = true;
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected [kWh]";
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Actual Inverter [kWh] Group ";
                    }
                    break;
                case ("dc_inv_power_diff"):
                    $dataArray = $this->getInverterPowerDifference($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['rangeValue'] = $dataArray['rangeValue'];
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Inverter Production [kWh]';
                        $resultArray['series1']['name'] = "Expected";
                        $resultArray['series1']['tooltipText'] = "Expected [kWh]";
                        $resultArray['seriesx']['name'] = "Actual Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Actual Inverter [kWh] Group ";
                    }
                    break;
                case ("dc_current_group"):
                    $dataArray = $this->getCurrentGroupDc($anlage, $from, $to, $form['selectedSet']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['label'] = $dataArray['label'];
                        $resultArray['headline'] = 'DC Current [A] - all Groups';
                        $resultArray['series1']['name'] = "Expected Group";
                        $resultArray['series1']['tooltipText'] = "Expected Group ";
                        $resultArray['seriesx']['name'] = "Group ";
                        $resultArray['seriesx']['tooltipText'] = "Group ";
                    }
                    break;
                case ("dc_current_inverter"):
                    $dataArray = $this->getCurrentInverter($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['series1']['name'] = "Expected ";
                        $resultArray['series1']['tooltipText'] = "Expected current [[A]]";
                        $resultArray['offsetLegende'] = $dataArray['offsetLegend'];
                        $resultArray['seriesx']['name'] = "Inverter ";
                        $resultArray['seriesx']['tooltipText'] = "Act current [A]";
                    }
                    break;
                case ("dc_current_mpp"):
                    $dataArray = $this->getCurrentMpp($anlage, $from, $to, $form['selectedInverter']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Current [A]';
                        $resultArray['seriesx']['name'] = "String ";
                        $resultArray['seriesx']['tooltipText'] = "Actuale current [A]";
                    }
                    break;
                case ("dc_voltage_groups"):
                    $dataArray = $this->getVoltageGroups($anlage, $from, $to, $form['selectedSet']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Group Electricity [V]';
                        $resultArray['seriesx']['name'] = "Group ";
                        $resultArray['seriesx']['tooltipText'] = "Group electricity [V]";
                    }
                    break;
                case ("dc_voltage_mpp"):
                    $dataArray = $this->getVoltageMpp($anlage, $from, $to, $form['selectedInverter']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'DC Voltage [V]';
                        $resultArray['seriesx']['name'] = "String ";
                        $resultArray['seriesx']['tooltipText'] = "Voltage [V]";
                    }
                    break;
                case ("inverter_performance"):
                    $dataArray = $this->getInverterPerformance($anlage, $from, $to, $form['selectedGroup']);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Inverter Performance';
                        $resultArray['series1']['name'] = "";
                        $resultArray['series1']['tooltipText'] = "";
                        $resultArray['seriesx']['name'] = "";
                        $resultArray['seriesx']['tooltipText'] = "";
                    }
                    break;
                case ("irradiation"):
                    $dataArray = $this->getIrradiation($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [W/qm]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident upper table" ;
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident upper table") . " [W/qm]";
                        $resultArray['series2']['name'] = ($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : "Incident lower table";
                        $resultArray['series2']['tooltipText'] = (($anlage->getWeatherStation()->getLabelLower() != '') ? $anlage->getWeatherStation()->getLabelLower() : "Incident lower table") . " [W/qm]";
                    }
                    break;
                case ("irradiation_one"):
                    $dataArray = $this->getIrradiation($anlage, $from, $to, 'upper');
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Irradiation [W/qm]';
                        $resultArray['series1']['name'] = ($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident" ;
                        $resultArray['series1']['tooltipText'] = (($anlage->getWeatherStation()->getLabelUpper() != '') ? $anlage->getWeatherStation()->getLabelUpper() : "Incident") . " [W/qm]";
                    }
                    break;
                case ("irradiation_plant"):
                    $dataArray = $this->getIrradiationPlant($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['maxSeries'] = $dataArray['maxSeries'];
                        $resultArray['headline'] = 'Irradiation [w/qm]';
                        $resultArray['series1']['name'] = "Irr G4N";
                        $resultArray['series1']['tooltipText'] = "G4N";
                        $resultArray['seriesx']['name'] = "Irradiation ";
                        $resultArray['seriesx']['tooltipText'] = "Irradiation [w/qm]";
                        $resultArray["nameX"] = json_encode($dataArray["nameX"]);
                    }
                    break;
                case ("temp"):
                    $dataArray = $this->getAirAndPanelTemp($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Air and Panel Temperature [??C]';
                        $resultArray['series1']['name'] = "Air temperature";
                        $resultArray['series1']['tooltipText'] = "Air temperature [??C]";
                        $resultArray['series2']['name'] = "Panel temperature";
                        $resultArray['series2']['tooltipText'] = "Panel temperature [??C]";
                    }
                    break;
                case ("pr_and_av"):
                    $dataArray = $this->getPRandAV($anlage, $from, $to);
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Performance Ratio and Availability';
                        $resultArray['series1']['name'] = "";
                        $resultArray['series1']['tooltipText'] = "";
                        $resultArray['series2']['name'] = "";
                        $resultArray['series2']['tooltipText'] = "";
                    }
                    break;
                case ("status_log"):
                    $resultArray['headline'] = 'Show status Log';
                    $resultArray['status'] = $this->statusRepository->findStatusAnlageDate($anlage, $from, $to);
                    break;
                case ("availability"):
                    $resultArray['headline'] = 'Show availability';
                    $resultArray['availability'] = $this->availabilityRepository->findAvailabilityAnlageDate($anlage, $from, $to);
                    break;
                case ("pvsyst"):
                    $resultArray['headline'] = 'Show PR & pvSyst';
                    $resultArray['pvSysts'] = $this->getpvSyst($anlage, $from, $to);
                    break;
                case ("forecast"):
                    if ($anlage->getUsePac()) {
                        $dataArray = $this->getForecastFac($anlage, $to);
                    } else {
                        $dataArray = $this->getForecastClassic($anlage, $to);
                    }
                    if ($dataArray != false) {
                        $resultArray['data'] = json_encode($dataArray['chart']);
                        $resultArray['headline'] = 'Forecast';
                        $resultArray['series1']['name'] = "";
                        $resultArray['series1']['tooltipText'] = "";
                    }
                    break;
                default:
                    $resultArray['headline'] = 'Something was wrong ' . $form['selectedChart'];
            }
        }

        return $resultArray;
    }

    ###########################################

    /**
     * Erzeugt Daten f??r das normale Soll/Ist AC Diagramm
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array|false
     * AC - Actual & Expected, Plant
     */
    public function getActExpAC(Anlage $anlage, $from, $to)
    {
        $conn = self::getPdoConnection();

        //$sql_a = "SELECT a.stamp, sum(b.exp_kwh) as soll FROM (db_dummysoll a left JOIN " . $anlage->getDbNameAcSoll() . " b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP by a.stamp";
        $sql_a = "SELECT a.stamp as stamp, sum(b.ac_exp_power) as soll FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP by a.stamp";
        $res = $conn->query($sql_a);
        $actSum = 0;
        $expSum = 0;
        $evuSum = 0;
        $cosPhiSum = 0;
        $dataArray = [];
        //add irradiation
        $showOnlyUpperIrr = $anlage->getShowOnlyUpperIrr();
        if($showOnlyUpperIrr){
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to, 'upper');
            $mode = 'upper';
        }else{
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to);
            $mode = 'all';
        }
        // end add irradiation
        // add Temp
        $panelTemparray = $this->getAirAndPanelTemp($anlage, $from, $to);

        if ($res->rowCount() > 0) {
            $counter = 0;
            while ($rowExp = $res->fetch(PDO::FETCH_ASSOC)) {
                $soll = round($rowExp["soll"], 2);
                $expdiff = $soll - $soll * 10 / 100;# -10% good
                $expdiff = round($expdiff, 2);

                $stamp = $rowExp["stamp"];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $acIst = 0;
                $eZEvu = 0;
                $cosPhi = 0;
                $sql_b = "SELECT stamp, sum(wr_pac) as acIst, e_z_evu as eZEvu, wr_cos_phi_korrektur as cosPhi FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' GROUP by stamp LIMIT 1";
                $resultB = $conn->query($sql_b);
                if ($resultB->rowCount() > 0) {
                    $row = $resultB->fetch(PDO::FETCH_ASSOC);
                    $eZEvu = $row["eZEvu"];
                    $cosPhi = abs($row["cosPhi"]);
                    $evuSum += $eZEvu;
                    $cosPhiSum += $cosPhi * $acIst;
                    $acIst = $row["acIst"];

                }
                $acIst = self::checkUnitAndConvert($acIst, $anlage->getAnlDbUnit());
                ($acIst > 0) ? $actout = round($acIst, 2) : $actout = 0; // neagtive Werte auschlie??en
                ($soll > 0) ?: $soll = 0; // neagtive Werte auschlie??en
                $actSum += $actout;
                $expSum += $soll;
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $stamp = self::timeShift($anlage, $rowExp["stamp"]);

                $dataArray['chart'][$counter]['date'] = $stamp;
                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['exp'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                }
                if (!($actout == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['act'] = $actout;
                    if ($anlage->getShowEvuDiag()) $dataArray['chart'][$counter]['eZEvu'] = $eZEvu;
                    if ($anlage->getShowCosPhiPowerDiag()) $dataArray['chart'][$counter]['cosPhi'] = $cosPhi * $actout;
                }
                switch ($mode) {
                    case 'all':
                        $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                    break;
                    case 'upper':
                        $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                }
                $dataArray['chart'][$counter]['panelTemp'] = $panelTemparray['chart'][$counter]["val2"];
                $counter++;
            }

            $dataArray['actSum'] = round($actSum, 2);
            $dataArray['expSum'] = round($expSum, 2);
            $dataArray['evuSum'] = round($evuSum, 2);
            $dataArray['cosPhiSum'] = round($cosPhiSum, 2);
            $conn = null;
            return $dataArray;
        } else {
            $conn = null;

            return false;
        }
    }

    /**
     * Erzeugt Daten f??r das Soll/Ist AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual & Expected, Groups
     */
    private function getAcExpGroupAC(Anlage $anlage, $from, $to, int $group = 1) : array
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        //add irradiation
        $showOnlyUpperIrr = $anlage->getShowOnlyUpperIrr();
        if($showOnlyUpperIrr){
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to, 'upper');
            $mode = 'upper';
        }else{
            $dataArrayIrradiation = $this->getIrradiation($anlage, $from, $to);
            $mode = 'all';
        }
        // end add irradiation
        // add Temp
        $panelTemparray = $this->getAirAndPanelTemp($anlage, $from, $to);

        $dataArray['maxSeries'] = 0;
        $inverterArray = $this->functions->getInverterNameArray($anlage);
        $dataArray['inverterArray'] = $inverterArray;
        $acGroups = $anlage->getGroupsAc();
        $sqlExpected = "SELECT a.stamp, sum(b.ac_exp_power) as soll 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";
        //dump($sqlExpected);
        $result = $conn->query($sqlExpected);
        $maxInverter = 0;
        if ($result->rowCount() > 0) {
            $counter = 0;
            $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
            $dataArray['label'] = $acGroups[$group]['GroupName'];
            while ($rowExp = $result->fetch(PDO::FETCH_ASSOC)) {
                $stamp = $rowExp["stamp"];
                ($rowExp['soll'] == null) ? $expected = 0 : $expected = $rowExp['soll'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $sqlIst = "SELECT sum(wr_pac) as actPower FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' AND group_ac = '$group' GROUP BY unit";
                $resultIst = $conn->query($sqlIst);
                $counterInv = 0;
                if ($resultIst->rowCount() > 0) {
                    while ($rowIst = $resultIst->fetch(PDO::FETCH_ASSOC)) {
                        $counterInv++;
                        if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                        $actPower = $rowIst['actPower'];
                        ($actPower > 0) ? $actPower = round(self::checkUnitAndConvert($actPower, $anlage->getAnlDbUnit()), 2) : $actPower = 0; // neagtive Werte auschlie??en
                        if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            //$dataArray['chart'][$counter]["val$counterInv"] = $actPower;
                            $dataArray['chart'][$counter][$inverterArray[$counterInv+$dataArray['offsetLegend']]] = $actPower;
                        }
                        if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv;
                    }
                } else {
                    for($counterInv = 0; $counterInv <= $maxInverter; $counterInv++) {
                        $dataArray['chart'][$counter]["val$counterInv"] = 0;
                    }
                }
                ($counterInv > 0) ? $dataArray['chart'][$counter]['exp'] = $expected / $counterInv : $dataArray['chart'][$counter]['exp'] = $expected;
                switch ($mode) {
                    case 'all':
                        $dataArray['chart'][$counter]["irradiation"] = ($dataArrayIrradiation['chart'][$counter]['val1'] + $dataArrayIrradiation['chart'][$counter]['val2'])/2;
                        break;
                    case 'upper':
                        $dataArray['chart'][$counter]["irradiation"] = $dataArrayIrradiation['chart'][$counter]['val1'];
                }
                $dataArray['chart'][$counter]['panelTemp'] = $panelTemparray['chart'][$counter]["val2"];
                $counter++;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * erzeugt Daten f??r Gruppen Leistungs Unterschiede Diagramm (Group Power Difference)
     * AC - Inverter
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     *
     * AC - Inverter
     */
    private function getGroupPowerDifferenceAC(Anlage $anlage, $from, $to)
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();

        // Strom f??r diesen Zeitraum und diese Gruppe
        $sql_soll = "SELECT stamp, sum(ac_exp_power) as soll, group_ac as inv_group FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_ac ORDER BY group_ac * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt
        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->rowCount() > 0) {

            $dataArray['maxSeries'] = 0;
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $dataArray['rangeValue'] = round($row["soll"], 2);
                $invGroupSoll = $row["inv_group"];
                $dataArray['chart'][$counter] = [
                    "category" => "Group: " . $acGroups[$invGroupSoll]['GroupName'],
                    "link" => $invGroupSoll,
                    "exp" => round($row["soll"], 2),
                ];
                $sqlInv = "SELECT sum(wr_pac) as acinv, group_ac as inv_group FROM " . $anlage->getDbNameIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$invGroupSoll' GROUP BY inv";
                $resultInv = $conn->query($sqlInv);
                if ($resultInv->rowCount() > 0) {
                    $wrcounter = 0;
                    while ($rowInv = $resultInv->fetch(PDO::FETCH_ASSOC)) {
                        $wrcounter++;
                        $dataArray['chart'][$counter]['act'] = self::checkUnitAndConvert($rowInv['acinv'], $anlage->getAnlDbUnit());
                        if ($wrcounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $wrcounter;
                    }
                }
                $counter++;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r Ist Spannung AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual, Groups
     */
    private function getActVoltageGroupAC(Anlage $anlage, $from, $to, $group = 1)
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        // Spannung f??r diesen Zeitraum und diese Gruppe
        #$sql_ist = "SELECT stamp, u_ac as uac_ist, group_ac as inv_group FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_ac ORDER BY group_ac * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt
        $sql_ist = "SELECT a.stamp, b.u_ac as uac_ist, u_ac_p1, u_ac_p2, u_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";

        $result = $conn->query($sql_ist);
        $counter = 0;
        $counterInv = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        #dd($dataArray);
        if ($result->rowCount() > 0) {

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $counterInv++;
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                $invGroupIst = $row["inv_group"];
                $stamp = $row["stamp"];

                $dataArray['chart'][$counter] = [
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    "date" => self::timeShift($anlage, $stamp),
                    "act" => round($row["uac_ist"], 2),
                    "u_ac_p1" => round($row["u_ac_p1"], 2),
                    "u_ac_p2" => round($row["u_ac_p2"], 2),
                    "u_ac_p3" => round($row["u_ac_p3"], 2),
                ];

                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r Ist Strom AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual, Groups
     */
    private function getActCurrentGroupAC(Anlage $anlage, $from, $to, $group = 1)
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        // Strom f??r diesen Zeitraum und diese Gruppe
        $sql_ist = "SELECT a.stamp, b.i_ac as iac_ist, i_ac_p1, i_ac_p2, i_ac_p3 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";

        $result = $conn->query($sql_ist);
        $counter = 0;
        $counterInv = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        if ($result->rowCount() > 0) {

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $counterInv++;
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                $invGroupIst = $row["inv_group"];
                $stamp = $row["stamp"];
                $dataArray['chart'][$counter] = [
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    "date" => self::timeShift($anlage, $stamp),
                    "act" => round($row["iac_ist"], 2),
                    "i_ac_p1" => round($row["i_ac_p1"], 2),
                    "i_ac_p2" => round($row["i_ac_p2"], 2),
                    "i_ac_p3" => round($row["i_ac_p3"], 2),
                ];

                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r Ist Frequenz AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * AC - Actual, Groups
     */
    private function getActFrequncyGroupAC(Anlage $anlage, $from, $to, $group = 1)
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $acGroups = $anlage->getGroupsAc();
        // Frequenz f??r diesen Zeitraum und diese Gruppe
        $sql_ist = "SELECT a.stamp, b.frequency as frequency_ist 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE group_ac = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";

        $result = $conn->query($sql_ist);
        $counter = 0;
        $counterInv = 0;
        $dataArray['maxSeries'] = 0;
        $dataArray['offsetLegend'] = $acGroups[$group]['GMIN'] - 1;
        $dataArray['label'] = $acGroups[$group]['GroupName'];
        if ($result->rowCount() > 0) {

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $counterInv++;
                if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                $invGroupIst = $row["inv_group"];
                $stamp = $row["stamp"];
                $dataArray['chart'][$counter] = [
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    "date" => self::timeShift($anlage, $stamp),
                    "act" => round($row["frequency_ist"], 2),
                ];

                $counter++;
            }
        }
        $conn = null;
        return $dataArray;
    }

    /**
     * DC Diagramme
     * Erzeugt Daten f??r das normale Soll/Ist DC Diagramm
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     * DC - Actual & Expected, Plant
     */
    private function getActExpDC(Anlage $anlage, $from, $to)
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $sqlDcSoll = "SELECT a.stamp as stamp, sum(b.soll_pdcwr) as soll FROM (db_dummysoll a left JOIN " . $anlage->getDbNameDcSoll() . " b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' GROUP by a.stamp";

        $resulta = $conn->query($sqlDcSoll);
        $actSum = 0;
        $expSum = 0;
        if ($resulta->rowCount() > 0) {
            $counter = 0;
            while ($roa = $resulta->fetch(PDO::FETCH_ASSOC)){
                $dcist = 0;
                $stamp = $roa["stamp"];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $soll = round($roa["soll"], 2);
                $expdiff = round($soll - $soll * 10 / 100, 2); //-10% good
                if ($anlage->getUseNewDcSchema()) {
                    $sql_b = "SELECT stamp, sum(wr_pdc) as dcist FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' GROUP by stamp LIMIT 1";
                } else {
                    $sql_b = "SELECT stamp, sum(wr_pdc) as dcist FROM " . $anlage->getDbNameIst() . " WHERE stamp = '$stampAdjust' GROUP by stamp LIMIT 1";
                }
                $resultb = $conn->query($sql_b);
                if ($resultb->rowCount() > 0) {
                    while ($rob = $resultb->fetch(PDO::FETCH_ASSOC)) {
                        $dcist = self::checkUnitAndConvert($rob["dcist"], $anlage->getAnlDbUnit());
                    }
                }

                ($dcist > 0) ? $dcist = round($dcist, 2) : $dcist = 0; // neagtive Werte auschlie??en
                $actSum += $dcist;
                $expSum += $soll;
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($soll == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['exp'] = $soll;
                    $dataArray['chart'][$counter]['expgood'] = $expdiff;
                }
                if (!($dcist == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['act'] = $dcist;
                }
                $counter++;
            }
            $dataArray['actSum'] = round($actSum, 2);
            $dataArray['expSum'] = round($expSum, 2);
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r das Soll/Ist AC Diagramm nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     * DC- Actual & Expected, Groups
     */
    private function getActExpGroupDC(Anlage $anlage, $from, $to, $group = 1)
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        $inverterArray = $this->functions->getInverterNameArray($anlage);
        $dataArray['inverterArray'] = $inverterArray;
        $dcGroups = $anlage->getGroupsDc();
        $sqlExpected = "SELECT a.stamp, sum(b.soll_pdcwr) as soll 
                        FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE wr_num = '$group') b ON a.stamp = b.stamp) 
                        WHERE a.stamp BETWEEN '$from' AND '$to' GROUP by a.stamp";
        $result = $conn->query($sqlExpected);
        $maxInverter = 0;
        if ($result->num_rows > 0) {
            $counter = 0;
            $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
            while ($rowExp = $result->fetch_assoc()) {
                $stamp = $rowExp['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                $anzInvPerGroup = $dcGroups[$group]['GMAX'] - $dcGroups[$group]['GMIN'] + 1;
                ($anzInvPerGroup > 0) ? $expected = $rowExp['soll'] / $anzInvPerGroup : $expected = $rowExp['soll'];
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                if (!($expected == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]['exp'] = $expected;
                }
                if ($anlage->getUseNewDcSchema()) {
                    $sqlIst = "SELECT sum(wr_pdc) as actPower FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$group' GROUP BY wr_num";
                } else {
                    $sqlIst = "SELECT sum(wr_pdc) as actPower FROM " . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND group_dc = '$group' GROUP BY unit";
                }
                $resultIst = $conn->query($sqlIst);
                $counterInv = 0;
                if ($resultIst->num_rows > 0) {
                    while ($rowIst = $resultIst->fetch_assoc()) {
                        $counterInv++;
                        if ($counterInv > $maxInverter) $maxInverter = $counterInv;
                        $actPower = self::checkUnitAndConvert($rowIst['actPower'], $anlage->getAnlDbUnit());
                        if (!($actPower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                           //$dataArray['chart'][$counter]["val$counterInv"] = $actPower;
                            $dataArray['chart'][$counter][$inverterArray[$counterInv+$dataArray['offsetLegend']]] = $actPower;
                        }
                        if ($counterInv > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counterInv;
                    }
                } else {
                    for($counterInv = 0; $counterInv <= $maxInverter; $counterInv++) {
                        $dataArray['chart'][$counter]["val$counterInv"] = 0;
                    }
                }
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * erzeugt Daten f??r Gruppen Leistungs Unterschiede Diagramm (Group Power Difference)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     * DC - Inverter / DC - Inverter Group // dc_grp_power_diff
     */
    private function getGroupPowerDifferenceDC(Anlage $anlage, $from, $to)
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $istGruppenArray = [];
        $dcGroups = $anlage->getGroupsDc();
        // IST Strom f??r diesen Zeitraum nach Gruppen gruppiert
        if ($anlage->getUseNewDcSchema()) {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, wr_group as inv_group FROM " . $anlage->getDbNameDCIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY wr_group ;";
        } else {
            $sqlIst = "SELECT sum(wr_pdc) as power_dc_ist, group_dc as inv_group FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' GROUP BY group_dc ;";
        }
        $resultIst = $conn->query($sqlIst);
        while ($rowIst = $resultIst->fetch_assoc()) { // Speichern des SQL ergebnisses in einem Array, Gruppe ist assosiativer Array Index
            $istGruppenArray[$rowIst['inv_group']] = $rowIst['power_dc_ist'];
        }
        // SOLL Strom f??r diesen Zeitraum nach Gruppen gruppiert
        $sql_soll = "SELECT stamp, sum(soll_pdcwr) as soll, wr_num as inv_group FROM " . $anlage->getDbNameDcSoll() . " 
                         WHERE stamp BETWEEN '$from' AND '$to' GROUP BY wr_num ORDER BY wr_num * 1"; // 'wr_num * 1' damit die Sortierung als Zahl und nicht als Text erfolgt

        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->num_rows > 0) {
            $dataArray['maxSeries'] = 0;
            while ($row = $result->fetch_assoc()) {
                $dataArray['rangeValue'] = round($row["soll"], 2);
                $invGroupSoll = $row["inv_group"];
                $dataArray['chart'][$counter] = [
                    "category" => "Group: " . $dcGroups[$invGroupSoll]['GroupName'],
                    "link" => $invGroupSoll,
                    "exp" => round($row["soll"], 2),
                ];
                $dataArray['chart'][$counter]['act'] = round(self::checkUnitAndConvert($istGruppenArray[$invGroupSoll], $anlage->getAnlDbUnit()), 2);
                if ($counter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $counter;
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * erzeugt Daten f??r Inverter Leistungs Unterschiede Diagramm (Inverter Power Difference)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $group
     * @return array
     * DC - Inverter // dc_inv_power_diff
     */
    private function getInverterPowerDifference(Anlage $anlage, $from, $to, $group)
    {
        $conn = self::connectToDatabase();
        $dataArray = [];

        if (self::isDateToday($to)) {
            // letzten Eintrag in IST DB ermitteln
            $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameIst() . " WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
            if ($res) {
                $rowTemp = $res->fetch_assoc();
                $lastRecStampAct = strtotime($rowTemp['stamp']);
                $res->free();


                // letzten Eintrag in  Weather DB ermitteln
                $res = $conn->query("SELECT stamp FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp > '$from' ORDER BY stamp DESC LIMIT 1");
                if ($res) {
                    $rowTemp = $res->fetch_assoc();
                    $lastRecStampExp = strtotime($rowTemp['stamp']);
                    $res->free();
                    ($lastRecStampAct <= $lastRecStampExp) ? $toLastBoth = self::formatTimeStampToSql($lastRecStampAct) : $toLastBoth = self::formatTimeStampToSql($lastRecStampExp);
                    $to = $toLastBoth;
                }
            }
        }

        // Leistung f??r diesen Zeitraum und diese Gruppe
        $sql_soll = "SELECT stamp, sum(soll_pdcwr) as soll FROM " . $anlage->getDbNameDcSoll() . " WHERE stamp BETWEEN '$from' AND '$to' AND wr_num = '$group' GROUP BY wr LIMIT 1";
        $result = $conn->query($sql_soll);
        $counter = 0;
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $dataArray['rangeValue'] = round($row["soll"], 2);
                $dataArray['chart'][] = [
                    "category" => 'expected',
                    "val" => round($row["soll"], 2),
                    "color" => '#fdd400',
                ];
                if ($anlage->getUseNewDcSchema()) {
                    $sqlInv = "SELECT sum(wr_pdc) as dcinv, wr_num AS inverter FROM " . $anlage->getDbNameDCIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND wr_group = '$group' GROUP BY wr_num";
                } else {
                    $sqlInv = "SELECT sum(wr_pdc) as dcinv, unit AS inverter FROM " . $anlage->getDbNameAcIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' GROUP BY unit";
                }
                $resultInv = $conn->query($sqlInv);
                if ($resultInv->num_rows > 0) {
                    $wrcounter = 0;
                    while ($rowInv = $resultInv->fetch_assoc()) {
                        $wrcounter++;
                        $inverter = $rowInv['inverter'];
                        $dataArray['chart'][] = [
                            "category" => "Inverter #$inverter",
                            "val" => self::checkUnitAndConvert($rowInv['dcinv'], $anlage->getAnlDbUnit()),
                            "link" => "$inverter",
                        ];
                        if ($wrcounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $wrcounter;
                    }
                }
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r das DC Strom Diagram Diagramm, eine Linie je Gruppe
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $set
     * @return array
     * dc_current_group
     */
    private function getCurrentGroupDc(Anlage $anlage, $from, $to, $set = 1)
    {
        $conn = self::connectToDatabase();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];

        // Strom f??r diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp BETWEEN '$from' AND '$to'";
        $result = $conn->query($sql_time);
        if ($result->num_rows > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch_assoc()) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlDbUnit());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $gruppenProSet = 1;
                foreach ($dcGroups as $dcGroupKey => $dcGroup) {
                    if($dcGroupKey > (($set - 1) * 10) && $dcGroupKey <= ($set * 10) ) {
                        // ermittle SOLL Strom nach Gruppen f??r diesen Zeitraum
                        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
                        if ($anlage->getUseNewDcSchema()) {
                            $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$dcGroupKey'";
                        } else {
                            $sql = "SELECT sum(wr_idc) as istCurrent FROM " . $anlage->getDbNameACIst() . " WHERE stamp = '$stampAdjust' AND group_dc = '$dcGroupKey'";
                        }
                        $resultIst = $conn->query($sql);
                        if ($resultIst->num_rows > 0) {
                            $rowIst = $resultIst->fetch_assoc();
                            $currentIst = round($rowIst['istCurrent'], 2);
                            if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$gruppenProSet"] = $currentIst;
                            }
                        }
                        $dataArray['maxSeries'] = $gruppenProSet;
                        $dataArray['label'][$dcGroupKey] = $dcGroup['GroupName'];
                        $gruppenProSet++;
                    }
                }
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r das DC Strom Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $group
     * @return array
     *  // dc_current_inverter
     */
    private function getCurrentInverter(Anlage $anlage, $from, $to, $group = 1)
    {
        $conn = self::connectToDatabase();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom f??r diesen Zeitraum und diesen Inverter
        $sql_strom = "SELECT a.stamp as stamp, b.soll_imppwr as sollCurrent FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDcSoll() . " WHERE wr_num = '$group') b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' AND '$to' GROUP BY a.stamp ORDER BY a.stamp";
        $result = $conn->query($sql_strom);
        if ($result->num_rows > 0) {
            $counter = 0;
            $dataArray['offsetLegend'] = $dcGroups[$group]['GMIN'] - 1;
            while ($row = $result->fetch_assoc()) {
                $stamp = $row['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $currentExp = round($row['sollCurrent'], 2);
                if($currentExp === null) $currentExp = 0;
                if (!($currentExp == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]["soll"] = $currentExp;
                }
                $mppCounter = 0;

                for ($inverter = $dcGroups[$group]['GMIN']; $inverter <= $dcGroups[$group]['GMAX']; $inverter++) {
                    $mppCounter++;
                    if ($anlage->getUseNewDcSchema()) {
                        $sql = "SELECT wr_idc as istCurrent FROM " . $anlage->getDbNameDCIst() . " WHERE stamp = '$stampAdjust' AND wr_num = '$inverter'";
                    } else {
                        $sql ="SELECT wr_idc as istCurrent FROM " . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND unit = '$inverter'";
                    }
                    $resultIst = $conn->query($sql);
                    if ($resultIst->num_rows > 0) {
                        $rowIst = $resultIst->fetch_assoc();
                        $currentIst = round($rowIst['istCurrent'], 2);
                        if (!($currentIst == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter]["val$mppCounter"] = $currentIst;
                        }
                    }
                }
                if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;
                $counter++;
            }
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r das DC Strom Diagram Diagramm, eine Linie je MPP gruppiert nach Inverter
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $inverter
     * @return array|false
     *  // dc_current_mpp
     */
    private function getCurrentMpp(Anlage $anlage, $from, $to, $inverter = 1)
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom f??r diesen Zeitraum und diesen Inverter
        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC Tabelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
        if ($anlage->getUseNewDcSchema()) {
            $sql_strom = "SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameDCIst() . " WHERE wr_num = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        } else {
            $sql_strom = "SELECT a.stamp as stamp, b.wr_mpp_current AS mpp_current FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        }
        $result = $conn->query($sql_strom);
        if ($result != false) {
            if ($result->num_rows > 0) {
                $counter = 0;
                while ($row = $result->fetch_assoc()) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    //$stamp = $row['stamp'];
                    $mppCurrentJson = $row['mpp_current'];
                    if ($mppCurrentJson != '') {
                        $mppCurrentArray = json_decode($mppCurrentJson);
                        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                        $mppCounter = 1;
                        foreach ($mppCurrentArray as $mppCurrentItem => $mppCurrentValue) {
                            if (!($mppCurrentValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$mppCounter"] = $mppCurrentValue;
                            }
                            $mppCounter++;
                        }
                        if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;
                        $counter++;
                    }
                }
            }
            $conn->close();

            return $dataArray;
        } else {
            $conn->close();

            return false;
        }
    }

    /**
     * Erzeugt Daten f??r das DC Spannung Diagram Diagramm, eine Linie je Inverter gruppiert nach Gruppen
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $set
     * @return array
     *  // dc_current_inverter
     */
    private function getVoltageGroups(Anlage $anlage, $from, $to, int $set = 1):array
    {
        $conn = self::connectToDatabase();
        $dcGroups = $anlage->getGroupsDc();
        $dataArray = [];
        // Spannung f??r diesen Zeitraum und diese Gruppe
        $sql_time = "SELECT stamp FROM db_dummysoll WHERE stamp BETWEEN '$from' AND '$to'";
        $result = $conn->query($sql_time);
        if ($result->num_rows > 0) {
            $counter = 0;
            while ($rowSoll = $result->fetch_assoc()) {
                $stamp = $rowSoll['stamp'];
                $stampAdjust = self::timeAjustment($stamp, (float)$anlage->getAnlZeitzone());
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                $gruppenProSet = 1;
                foreach ($dcGroups as $dcGroupKey => $dcGroup) {
                    if($dcGroupKey > (($set - 1) * 10) && $dcGroupKey <= ($set * 10) ) {
                        // ermittle Spannung f??r diese Zeit und diese Gruppe
                        if ($anlage->getUseNewDcSchema()) {
                            $sql ="SELECT AVG(wr_udc) as actVoltage FROM " . $anlage->getDbNameDcIst() . " WHERE stamp = '$stampAdjust' AND wr_group = '$dcGroupKey'";
                        } else {
                            $sql ="SELECT AVG(wr_udc) as actVoltage FROM " . $anlage->getDbNameAcIst() . " WHERE stamp = '$stampAdjust' AND group_ac = '$dcGroupKey'";
                        }
                        $resultIst = $conn->query($sql);
                        if ($resultIst->num_rows == 1) {
                            $rowIst = $resultIst->fetch_assoc();
                            $voltageAct = round($rowIst['actVoltage'], 2);
                            if (!($voltageAct == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$gruppenProSet"] = $voltageAct;
                            }
                        }
                        $dataArray['label'][$dcGroupKey] = $dcGroup['GroupName'];
                        $dataArray['maxSeries'] = $gruppenProSet; //count($dcGroups);
                        $gruppenProSet++;
                    }
                }
                $counter++;
            }
        }
        $conn->close();
        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r das DC Spannungs Diagram Diagramm, eine Linie je MPP gruppiert nach Inverter
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param int $inverter
     * @return array|false
     *  // dc_voltage_mpp
     */
    private function getVoltageMpp(Anlage $anlage, $from, $to, $inverter = 1)
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;

        // Strom f??r diesen Zeitraum und diesen Inverter
        // ACHTUNG Strom und Spannungs Werte werden im Moment (Sep2020) immer in der AC TAbelle gespeichert, auch wenn neues 'DC IST Schema' genutzt wird.
        $sql_voltage = "SELECT a.stamp as stamp, b.wr_mpp_voltage AS mpp_voltage FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameAcIst() . " WHERE unit = '$inverter') b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to'";
        $result = $conn->query($sql_voltage);
        if ($result != false) {
            if ($result->num_rows > 0) {
                $counter = 0;
                while ($row = $result->fetch_assoc()) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    $mppVoltageJson = $row['mpp_voltage'];
                    if ($mppVoltageJson != '') {
                        $mppvoltageArray = json_decode($mppVoltageJson);
                        //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                        $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                        $mppCounter = 1;
                        foreach ($mppvoltageArray as $mppVoltageItem => $mppVoltageValue) {
                            if (!($mppVoltageValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                $dataArray['chart'][$counter]["val$mppCounter"] = $mppVoltageValue;
                            }
                            $mppCounter++;
                        }
                        if ($mppCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $mppCounter;
                        $counter++;
                    }
                }
            }
            $conn->close();

            return $dataArray;
        } else {
            $conn->close();

            return false;
        }
    }

    /**
     * erzeugt Daten f??r Inverter Performance Diagramm (DC vs AC Leistung der Inverter)
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param $group
     * @return array
     *  // inverter_performance
     */
    private function getInverterPerformance(Anlage $anlage, $from, $to, $group)
    {
        $conn = self::connectToDatabase();
        $dataArray = [];
        $sql = "SELECT stamp, sum(wr_pac) AS power_ac, sum(wr_pdc) AS power_dc, unit AS inverter  FROM " . $anlage->getDbNameIst() . " WHERE stamp BETWEEN '$from' AND '$to' AND group_ac = '$group' GROUP by unit";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $inverter = $row['inverter'];
                $powerDc = self::checkUnitAndConvert($row['power_dc'], $anlage->getAnlDbUnit());
                $powerAc = self::checkUnitAndConvert($row['power_ac'], $anlage->getAnlDbUnit());
                $dataArray['chart'][] = [
                    "inverter" => "Inverter #$inverter",
                    "valDc" => $powerDc,
                    "valAc" => $powerAc,
                ];
            }
            $dataArray['maxSeries'] = 0;
            $dataArray['startCounterInverter'] = 10;
        }
        $conn->close();

        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r das Strahlungsdiagramm Diagramm
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @param string $mode
     * @return array
     *  // irradiation
     */
    private function getIrradiation(Anlage $anlage, $from, $to, $mode = 'all')
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $sql2 = "SELECT a.stamp, b.gi_avg , b.gmod_avg  FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' ORDER BY a.stamp";
        $res = $conn->query($sql2);
        if ($res->rowCount() > 0) {
            $counter = 0;
            while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
                // upper pannel
                $irr_upper = str_replace(',', '.', $ro["gmod_avg"]);
                if (!$irr_upper) $irr_upper = 0;
                // lower pannel
                $irr_lower = str_replace(',', '.', $ro["gi_avg"]);
                if (!$irr_lower) $irr_lower = 0;
                $stamp = self::timeAjustment(strtotime($ro["stamp"]), (int)$anlage->getAnlZeitzoneIr());
                if ($anlage->getAnlIrChange() == "Yes") {
                    $swap = $irr_lower;
                    $irr_lower = $irr_upper;
                    $irr_upper = $swap;
                }
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]["date"] = self::timeShift($anlage, $stamp);
                if (!($irr_upper+$irr_lower == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    switch ($mode) {
                        case 'all':
                            $dataArray['chart'][$counter]["val1"] = $irr_upper; // upper pannel
                            $dataArray['chart'][$counter]["val2"] = $irr_lower; // lower pannel
                            break;
                        case 'upper':
                            $dataArray['chart'][$counter]["val1"] = $irr_upper; // upper pannel
                            break;
                        case 'lower':
                            $dataArray['chart'][$counter]["val1"] = $irr_lower; // upper pannel
                            break;
                    }
                }
                $counter++;
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeugt Daten f??r Temperatur Diagramm
     * @param $anlage
     * @param $from
     * @param $to
     * @return array
     *  //
     */
    private function getAirAndPanelTemp(Anlage $anlage, $from, $to)
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $counter = 0;
        $sql2 = "SELECT a.stamp, b.at_avg , b.pt_avg FROM (db_dummysoll a LEFT JOIN " . $anlage->getDbNameWeather() . " b ON a.stamp = b.stamp) WHERE a.stamp BETWEEN '$from' and '$to' ORDER BY a.stamp";
        $res = $conn->query($sql2);
        while ($ro = $res->fetch(PDO::FETCH_ASSOC)) {
            $atavg = $ro["at_avg"];
            if (!$atavg) {
                $atavg = 0;
            }
            $ptavg = $ro["pt_avg"];
            if (!$ptavg) {
                $ptavg = 0;
            }
            $atavg = str_replace(',', '.', $atavg);
            $ptavg = str_replace(',', '.', $ptavg);

            $stamp = $ro["stamp"];  #utc_date($stamp,$anintzzws);
            if ($ptavg != "#") {
                //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                $dataArray['chart'][$counter]["date"] = self::timeShift($anlage, $stamp);
                if (!($atavg + $ptavg == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                    $dataArray['chart'][$counter]["val1"] = $atavg; // upper pannel
                    $dataArray['chart'][$counter]["val2"] = $ptavg; // lower pannel
                }
            }
            $counter++;
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeuge Daten f??r die Stralung die direlt von der Anlage geliefert wir
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array|false
     *  // irradiation_plant
     */
    private function getIrradiationPlant(Anlage $anlage, $from, $to)
    {
        $conn = self::getPdoConnection();
        $dataArray = [];
        $dataArray['maxSeries'] = 0;
        // Strom f??r diesen Zeitraum und diesen Inverter
        $sql_irr_plant = "SELECT a.stamp as stamp, b.irr_anlage AS irr_anlage FROM (db_dummysoll a left JOIN (SELECT * FROM " . $anlage->getDbNameIst() . ") b ON a.stamp = b.stamp) WHERE a.stamp >= '$from' AND a.stamp <= '$to' group by a.stamp;";
        $result = $conn->query($sql_irr_plant);
        if ($result != false) {
            if ($result->rowCount() > 0) {
                $counter = 0;
                while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                    $stamp = self::timeAjustment($row['stamp'], (int)$anlage->getAnlZeitzone(), true);
                    //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
                    $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
                    $sqlWeather = "SELECT * FROM " . $anlage->getDbNameWeather() . " WHERE stamp = '$stamp'";
                    $resultWeather = $conn->query($sqlWeather);
                    if ($resultWeather->rowCount() == 1) {
                        $weatherRow = $resultWeather->fetch(PDO::FETCH_ASSOC);
                        if ($anlage->getAnlIrChange() == "Yes") {
                            $dataArray['chart'][$counter]['g4n'] = str_replace(',', '.', $weatherRow["gi_avg"]); // getauscht, nutze unterene Sensor
                        } else {
                            $dataArray['chart'][$counter]['g4n'] = str_replace(',', '.', $weatherRow["gmod_avg"]); // nicht getauscht, nutze oberen Sensor
                        }
                    } else {
                        if (!(self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                            $dataArray['chart'][$counter]['g4n'] = 0;
                        }
                    }
                    $irrAnlageJson = $row['irr_anlage'];
                    if ($irrAnlageJson != '') {
                        $irrAnlageArray = json_decode($irrAnlageJson);
                        $irrCounter = 1;
                        foreach ($irrAnlageArray as $irrAnlageItem => $irrAnlageValue) {
                            if (!($irrAnlageValue == 0 && self::isDateToday($stamp) && self::getCetTime() - strtotime($stamp) < 7200)) {
                                if (!isset($irrAnlageValue)) $irrAnlageValue = 0;
                                $dataArray['chart'][$counter]["val$irrCounter"] = round(($irrAnlageValue < 0) ? 0 : $irrAnlageValue, 0);
                                if (!isset($dataArray["nameX"][$irrCounter])) $dataArray["nameX"][$irrCounter] = $irrAnlageItem;
                            }
                            if ($irrCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $irrCounter;
                            $irrCounter++;
                        }
                        switch ($anlage->getAnlId()) {
                            case 84: // f??r Groningen
                                $gemittelteStrahlung = ($irrAnlageArray->G_H01 + $irrAnlageArray->G_H02 + $irrAnlageArray->G_H03 + $irrAnlageArray->G_H04) / 4;
                                $dataArray['chart'][$counter]["val$irrCounter"] = round(self::Hglobal2Hmodul($anlage, new DateTime($stamp), $gemittelteStrahlung), 0);
                                if (!isset($dataArray["nameX"][$irrCounter])) $dataArray["nameX"][$irrCounter] = 'XXX';
                                if ($irrCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $irrCounter;
                                break;
                            case 83: // f??r Veendam
                                $gemittelteStrahlung = $irrAnlageArray->G_M10;
                                $dataArray['chart'][$counter]["val$irrCounter"] = round(self::Hglobal2Hmodul($anlage, new DateTime($stamp), $gemittelteStrahlung), 0);
                                if (!isset($dataArray["nameX"][$irrCounter])) $dataArray["nameX"][$irrCounter] = 'XXX';
                                if ($irrCounter > $dataArray['maxSeries']) $dataArray['maxSeries'] = $irrCounter;
                                break;
                        }
                    }
                    $counter++;
                }
            }
        }
        $conn = null;

        return $dataArray;
    }

    /**
     * Erzeuge Daten f??r PR und AV
     * @param Anlage $anlage
     * @param $from
     * @param $to
     * @return array
     *  // pr_and_av
     */
    private function getPRandAV(Anlage $anlage, $from, $to)
    {
        $prs = $this->prRepository->findPrAnlageDate($anlage, $from, $to);
        $dataArray = [];
        $counter = 0;
        /** @var AnlagenPR $pr */
        foreach ($prs as $pr) {
            $stamp = $pr->getstamp()->format('Y-m-d');
            //Correct the time based on the timedifference to the geological location from the plant on the x-axis from the diagramms
            $dataArray['chart'][$counter]['date'] = self::timeShift($anlage, $stamp);
            if($anlage->getShowEvuDiag()) {
                $dataArray['chart'][$counter]['pr_act'] = $pr->getPrEvu();
            } else {
                $dataArray['chart'][$counter]['pr_act'] = $pr->getPrAct();
            }
            $av = $this->availabilityRepository->sumAvailabilityPerDay($anlage->getAnlId(), $stamp);
            $dataArray['chart'][$counter]['av'] = round($av, 2);
            $counter++;
        }

        return $dataArray;
    }

    private function getpvSyst(Anlage $anlage, $from, $to)
    {
        $dataArray = [];
        $prs = $this->prRepository->findPrAnlageDate($anlage, $from, $to);
        $counter = 0;
        /** @var AnlagenPR $pr */
        foreach ($prs as $pr) {
            $stamp = $pr->getstamp()->format('Y-m-d');
            $dataArray[$counter]['date'] = $stamp;
            $dataArray[$counter]['pr'] = $pr;
            $pvSyst = $this->pvSystRepository->sumByStamp($anlage, $stamp);
            $dataArray[$counter]['electricityGrid'] = round($pvSyst[0]['eGrid'] / 1000, 2); // durch 100 um auf kWh zu kommen
            $dataArray[$counter]['electricityInverter'] = round($pvSyst[0]['eInverter'] / 1000, 2); // durch 100 um auf kWh zu kommen
            $counter++;
        }

        return $dataArray;
    }

    private function getForecastFac(Anlage $anlage, $to)
    {
        $actPerWeek = [];
        $dataArray = [];
        /**/
        //FAC Date bzw letztes FAC Jahr berechnen
        $facDateForecast = clone $anlage->getFacDate();
        $facDateForecastMinusOneYear = clone $anlage->getFacDate();
        $facDateForecastMinusOneYear->modify('-1 Year');
        if ($facDateForecastMinusOneYear > self::getCetTime('object')) { //
            $facDateForecast->modify('-1 Year');
            $facDateForecastMinusOneYear->modify('-1 Year');
        }
        $facWeek = $facDateForecast->format('W'); // Woche des FAC Datums

        /** @var [] AnlageForecast $forecasts */
        $forecasts = $this->forecastRepo->findBy(['anlage' => $anlage]);
        $forecastArray = [];
        //Kopiere alle Forcast Werte in ein Array mit dem Index der Kalenderwoche
        foreach ($forecasts as $forecast) {
            $forecastArray[$forecast->getWeek()] = $forecast;
        }

        $conn = self::getPdoConnection();
        $sql = "SELECT (dayofyear(stamp)-mod(dayofyear(stamp),7))+1 AS startDayWeek, sum(e_z_evu) AS sumEvu  
                FROM " . $anlage->getDbNameAcIst() . " 
                WHERE stamp BETWEEN '" . $facDateForecastMinusOneYear->format('Y-m-d') . "' AND '" . $to . "' AND unit = 1 GROUP BY (dayofyear(stamp)-mod(dayofyear(stamp),7)) 
                ORDER BY stamp;";
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value) {
            $actPerWeek[$value['startDayWeek']] = $value['sumEvu'];
        }
        $conn = null;

        $forecastValue  = 0;
        $expectedWeek   = 0;
        $divMinus       = 0;
        $divPlus        = 0;
        $week = $facWeek;
        $year = $facDateForecastMinusOneYear->format('Y');
        for ($counter = 1; $counter <=52; $counter++) {
            if ($week >= 52) {
                $week = 1;
                $year++;
            } else {
                $week++;
            }

            $stamp = strtotime($year . 'W' . str_pad($forecastArray[$week]->getWeek(), 2, '0', STR_PAD_LEFT));
            //$dataArray['chart'][$counter]['date'] = date('Y-m-d', $stamp);

            if (isset($actPerWeek[$forecastArray[$week]->getDay()])) {
                $expectedWeek += $actPerWeek[$forecastArray[$week]->getDay()];
                $divMinus += $actPerWeek[$forecastArray[$week]->getDay()];
                $divPlus += $actPerWeek[$forecastArray[$week]->getDay()];
            } else {
                $expectedWeek += $forecastArray[$week]->getExpectedWeek();
                $divMinus += $forecastArray[$week]->getDivergensMinus();
                $divPlus += $forecastArray[$week]->getDivergensPlus();
            }
            $forecastValue += $forecastArray[$week]->getExpectedWeek();

            $dataArray['chart'][] = [
                'date'      => date('Y-m-d', $stamp),
                'forecast'  => $forecastValue,
                'expected'  => $expectedWeek,
                'divMinus'  => $divMinus,
                'divPlus'   => $divPlus,
            ];

        }

        return $dataArray;
    }

    private function getForecastClassic(Anlage $anlage, $to)
    {
        $actPerWeek = [];
        $dataArray = [];

        $conn = self::getPdoConnection();
        $sql = "SELECT (dayofyear(stamp)-mod(dayofyear(stamp),7))+1 AS startDayWeek, sum(e_z_evu) AS sumEvu  
                FROM ".$anlage->getDbNameAcIst()." 
                WHERE year(stamp) = '2020' AND unit = 1 GROUP BY (dayofyear(stamp)-mod(dayofyear(stamp),7)) 
                ORDER BY stamp;";
        $result = $conn->prepare($sql);
        $result->execute();
        foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $value){
            if ($value['startDayWeek'] < date('z', strtotime($to))) $actPerWeek[$value['startDayWeek']] = $value['sumEvu'];
        }
        $conn = null;

        /** @var [] AnlageForecast $forecasts */
        $forecasts = $this->forecastRepo->findBy(['anlage' => $anlage]);
        $counter = 0;
        $forecastValue  = 0;
        $expectedWeek   = 0;
        $divMinus       = 0;
        $divPlus        = 0;
        foreach ($forecasts as $forecast) {
            $year = date('Y', strtotime($to));
            $stamp = strtotime($year.'W'.str_pad($forecast->getWeek(), 2, '0', STR_PAD_LEFT));

            $dataArray['chart'][$counter]['date']       = date('Y-m-d', $stamp);

            if (isset($actPerWeek[$forecast->getDay()])) {
                $expectedWeek   += $actPerWeek[$forecast->getDay()];
                $divMinus       += $actPerWeek[$forecast->getDay()];
                $divPlus        += $actPerWeek[$forecast->getDay()];
            } else {
                $expectedWeek   += $forecast->getExpectedWeek();
                $divMinus       += $forecast->getDivergensMinus();
                $divPlus        += $forecast->getDivergensPlus();
            }
            $forecastValue += $forecast->getExpectedWeek();
            $dataArray['chart'][$counter]['forecast']   = $forecastValue;
            $dataArray['chart'][$counter]['expected']   = $expectedWeek;
            $dataArray['chart'][$counter]['divMinus']   = $divMinus;
            $dataArray['chart'][$counter]['divPlus']    = $divPlus;
            $counter++;
        }
        return $dataArray;
    }
}