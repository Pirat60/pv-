<?php

namespace App\Entity;

use App\Repository\AnlagenRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * DbAnlage
 *
 * @ORM\Table(name="anlage")
 * @ORM\Entity(repositoryClass="App\Repository\AnlagenRepository")
 */
class Anlage
{
    private string $dbAnlagenData = "pvp_data";

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private int $anlId;

    /**
     * @var string
     *
     * @ORM\Column(name="eigner_id", type="bigint", nullable=false)
     */
    private string $eignerId;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_type", type="string", length=25, nullable=false)
     */
    private string $anlType;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_dbase", type="string", length=25, nullable=false, options={"default"="web32_db2"})
     * @deprecated
     */
    private string $anlDbase = 'web32_db2';

    /**
     * @var DateTime
     *
     * @ORM\Column(name="anl_betrieb", type="date", nullable=false)
     */
    private DateTime $anlBetrieb;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_name", type="string", length=50, nullable=false)
     */
    private string $anlName;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_strasse", type="string", length=100, nullable=false)
     */
    private string $anlStrasse;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_plz", type="string", length=10, nullable=false)
     */
    private string $anlPlz;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_ort", type="string", length=100, nullable=false)
     */
    private string $anlOrt;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_intnr", type="string", length=50, nullable=false)
     */
    private string $anlIntnr;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private string $power = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerEast = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $powerWest = '0';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_info", type="text", length=65535, nullable=false)
     * @deprecated
     */
    private string $anlInfo = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_data_go_ws", type="string", length=10, nullable=false, options={"default"="No"})
     */
    private string $anlDataGoWs = 'No';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_modul_anz", type="string", length=50, nullable=false)
     */
    private string $anlModulAnz = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_modul_name", type="string", length=100, nullable=false)
     */
    private string $anlModulName = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_modul_leistung", type="string", length=50, nullable=false)
     */
    private string $anlModulLeistung = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_start_year", type="string", length=5, nullable=false)
     * @deprecated
     */
    private string $anlStartYear = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_db_ist", type="string", length=50, nullable=false)
     * @deprecated
     */
    private string $anlDbIst = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_db_ws", type="string", length=50, nullable=false)
     * @deprecated
     */
    private string $anlDbWs = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_same_ws", type="string", length=10, nullable=false, options={"default"="No"})
     * @deprecated
     */
    private string $anlSameWs = 'No';

    /**
     * @var boolean
     *
     * @ORM\Column(name="send_warn_mail", type="boolean")
     */
    private bool $sendWarnMail = false;

    /**
     * @var string
     *
     * @ORM\Column(name="anl_input_daily", type="string", length=10, nullable=false, options={"default"="No"})
     */
    private string $anlInputDaily = 'No';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_grupe", type="string", length=10, nullable=false, options={"default"="No"})
     * @deprecated
     */
    private string $anlGruppe = 'No';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_grupe_dc", type="string", length=10, nullable=false, options={"default"="No"})
     * @deprecated
     */
    private string $anlGruppeDc = 'No';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_zeitzone", type="string", length=50, nullable=false)
     */
    private string $anlZeitzone = '0';

    /**
     * @var string|null
     *
     * @ORM\Column(name="anl_db_unit", type="string", length=10, nullable=true, options={"default"="w"})
     */
    private ?string $anlDbUnit = 'w';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_wind_unit", type="string", length=10, nullable=false, options={"default"="km/h"})
     * @deprecated
     */
    private string $anlWindUnit = 'km/h';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_view", type="string", length=10, nullable=false, options={"default"="No"})
     * @deprecated
     */
    private string $anlView = 'No';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_hide_plant", type="string", length=10, nullable=false)
     */
    private string $anlHidePlant = 'No';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_geo_lat", type="string", length=30, nullable=false)
     */
    private string $anlGeoLat = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_geo_lon", type="string", length=30, nullable=false)
     */
    private string $anlGeoLon = '';

    /**
     * @var string
     *
     * @ORM\Column(name="anl_mute", type="string", length=10, nullable=false, options={"default"="No"})
     */
    private string $anlMute = 'No';

    /**
     * @var DateTime|null
     *
     * @ORM\Column(name="anl_mute_until", type="datetime", nullable=true)
     */
    private ?DateTime $anlMuteUntil;

    /**
     * @ORM\ManyToOne(targetEntity=Eigner::class, inversedBy="anlage")
     */
    private ?Eigner $eigner;

    /**
     * @ORM\OneToMany(targetEntity=AnlageAcGroups::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private Collection $acGroups;

    /**
     * @ORM\OneToMany(targetEntity=AnlageEventMail::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private Collection $eventMails;

    /**
     * @ORM\OneToMany(targetEntity=AnlagenReports::class, mappedBy="anlage", cascade={"remove"})
     */
    private Collection $anlagenReports;

    /**
     * @ORM\OneToMany(targetEntity=AnlageAvailability::class, mappedBy="anlage", cascade={"remove"})
     * @ORM\OrderBy({"inverter" = "ASC"})
     */
    private Collection $availability;

    /**
     * @ORM\OneToMany(targetEntity=AnlagenStatus::class, mappedBy="anlage", cascade={"remove"})
     */
    private Collection $status;

    /**
     * @ORM\OneToMany(targetEntity=AnlagenPR::class, mappedBy="anlage", cascade={"remove"})
     */
    private Collection $pr;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $useNewDcSchema = true;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $useCosPhi = false;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    private ?string $useCustPRAlgorithm;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showOnlyUpperIrr = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showStringCharts = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showAvailability = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showAvailabilitySecond = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showInverterPerformance = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showMenuReporting = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showMenuDownload = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showEvuDiag = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showCosPhiDiag = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showCosPhiPowerDiag = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showGraphDcCurrInv = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showGraphDcCurrGrp = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showGraphVoltGrp = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showGraphDcInverter = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showGraphIrrPlant = false;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showPR = false;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrLimitAvailability = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $contractualAvailability = '100';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $contractualPR = '100';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $contractualPower = '0';

    /**
     * @ORM\OneToMany(targetEntity=AnlageCase5::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private Collection $anlageCase5s;

    /**
     * @ORM\OneToMany(targetEntity=AnlagePVSystDaten::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private Collection $anlagePVSystDatens;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showPvSyst = false;

    /**
     * @ORM\ManyToOne(targetEntity=WeatherStation::class, inversedBy="anlagen", cascade={"persist"})
     */
    private ?weatherStation $weatherStation;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $pacDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $facDate;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $usePac = false;

    /**
     * @ORM\OneToMany(targetEntity=AnlageForecast::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private Collection $anlageForecasts;

    /**
     * @ORM\OneToMany(targetEntity=AnlageGroups::class, mappedBy="anlage", cascade={"persist", "remove"})
     * @ORM\OrderBy({"dcGroup" = "ASC"})
     */
    private Collection $groups;

    /**
     * @ORM\OneToMany(targetEntity=AnlageModules::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private Collection $modules;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isOstWestAnlage = false;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $minIrradiationAvailability = '50';

    /**
     * @ORM\OneToMany(targetEntity=TimesConfig::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private $timesConfigs;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $showForecast = false;

    /**
     * @ORM\OneToMany(targetEntity=AnlageGridMeterDay::class, mappedBy="anlage")
     */
    private $anlageGridMeterDays;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $useGridMeterDayData = false;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $country = '';

    /**
     * @ORM\OneToMany(targetEntity=OpenWeather::class, mappedBy="anlage")
     */
    private $openWeather;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $calcPR = false;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $pacDuration = '';

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $kwPeakPvSyst;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $designPR;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $facDateStart;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $pacDateEnd;


    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private string $lid;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $annualDegradation;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private ?string $pldPR;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $epcReportType = '';

    /**
     * @ORM\OneToMany(targetEntity=AnlagenPvSystMonth::class, mappedBy="anlage", cascade={"persist", "remove"})
     * @ORM\OrderBy({"month" = "ASC"})
     */
    private $anlagenPvSystMonths;

    /**
     * @ORM\OneToMany(targetEntity=AnlagenMonthlyData::class, mappedBy="anlage", cascade={"persist", "remove"})
     * @ORM\OrderBy({"year" = "ASC", "month" = "ASC"})
     */
    private $anlagenMonthlyData;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $transformerTee = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $guaranteeTee = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $pldYield = '';

    /**
     * @ORM\Column(type="string", length=30)
     */
    private string $projektNr = '';

    /**
     * @ORM\OneToMany(targetEntity=AnlageLegendReport::class, mappedBy="anlage", cascade={"persist", "remove"})
     */
    private $anlageLegendReports;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $Notes;

    /**
     * @ORM\OneToMany(targetEntity=AnlageMonth::class, mappedBy="anlage", orphanRemoval=true)
     */
    private $anlageMonth;

    /**
     * @ORM\OneToMany(targetEntity=AnlageInverters::class, mappedBy="anlage")
     */
    private $Inverters;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCorrCellTypeAvg = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCorrGamma = '-0.4';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCorrA = '-3.56';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCorrB = '-0.0750';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $tempCorrDeltaTCnd = '3.0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $pldNPValue = '';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $pldDivisor = '';

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $epcReportStart;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private ?DateTime $epcReportEnd;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $inverterStartVoltage = '540';

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $useLowerIrrForExpected = false;

    /**
     * @ORM\Column(type="text")
     */
    private $epcReportNote;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $sourceInvName;


    public function __construct()
    {
        $this->acGroups = new ArrayCollection();
        $this->availability = new ArrayCollection();
        $this->status = new ArrayCollection();
        $this->anlagenReports = new ArrayCollection();
        $this->pr = new ArrayCollection();
        $this->eventMails = new ArrayCollection();
        $this->anlageCase5s = new ArrayCollection();
        $this->anlagePVSystDatens = new ArrayCollection();
        $this->anlageForecasts = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->modules = new ArrayCollection();
        $this->timesConfigs = new ArrayCollection();
        $this->anlageGridMeterDays = new ArrayCollection();
        $this->openWeather = new ArrayCollection();
        $this->anlagenPvSystMonths = new ArrayCollection();
        $this->anlagenMonthlyData = new ArrayCollection();
        $this->anlageLegendReports = new ArrayCollection();
        $this->anlageMonth = new ArrayCollection();
        $this->Inverters = new ArrayCollection();
    }

    public function getAnlId(): ?string
    {
        return $this->anlId;
    }

    public function setAnlId($anlagenId): self
    {
        $this->anlId = $anlagenId;

        return $this;
    }

    public function getAnlagenId(): ?string
    {
        return $this->anlId;
    }

    public function getEignerId(): ?string
    {
        return $this->eignerId;
    }

    public function setEignerId(string $eignerId): self
    {
        $this->eignerId = $eignerId;

        return $this;
    }

    public function getAnlType(): ?string
    {
        return $this->anlType;
    }

    public function setAnlType(string $anlType): self
    {
        $this->anlType = $anlType;

        return $this;
    }

    public function getAnlDbase(): ?string
    {
        return $this->anlDbase;
    }

    public function setAnlDbase(string $anlDbase): self
    {
        $this->anlDbase = $anlDbase;

        return $this;
    }

    public function getAnlBetrieb(): ?\DateTime
    {
        return $this->anlBetrieb;
    }

    public function setAnlBetrieb(\DateTime $anlBetrieb): self
    {
        $this->anlBetrieb = $anlBetrieb;

        return $this;
    }

    public function getAnlName(): ?string
    {
        return $this->anlName;
    }

    public function setAnlName(string $anlName): self
    {
        $this->anlName = $anlName;

        return $this;
    }

    public function getAnlStrasse(): ?string
    {
        return $this->anlStrasse;
    }

    public function setAnlStrasse(string $anlStrasse): self
    {
        $this->anlStrasse = $anlStrasse;

        return $this;
    }

    public function getAnlPlz(): ?string
    {
        return $this->anlPlz;
    }

    public function setAnlPlz(string $anlPlz): self
    {
        $this->anlPlz = $anlPlz;

        return $this;
    }

    public function getAnlOrt(): ?string
    {
        return $this->anlOrt;
    }

    public function setAnlOrt(string $anlOrt): self
    {
        $this->anlOrt = $anlOrt;

        return $this;
    }

    public function getAnlIntnr(): ?string
    {
        return $this->anlIntnr;
    }

    public function setAnlIntnr(string $anlIntnr): self
    {
        $this->anlIntnr = $anlIntnr;

        return $this;
    }

    public function getPower(): ?string
    {
        return $this->power;
    }

    public function setPower(string $power): self
    {
        $this->power =  str_replace(',', '.', $power);

        return $this;
    }

    public function getKwPeak(): ?string
    {
        return $this->power;
    }

    public function setKwPeak(string $power): self
    {
        $this->power =  str_replace(',', '.', $power);

        return $this;
    }

    public function getPowerEast(): ?string
    {
        return $this->powerEast;
    }

    public function setPowerEast(string $powerEast): self
    {
        $this->powerEast =  str_replace(',', '.', $powerEast);

        return $this;
    }

    public function getPowerWest(): ?string
    {
        return $this->powerWest;
    }

    public function setPowerWest(string $powerWest): self
    {
        $this->powerWest =  str_replace(',', '.', $powerWest);

        return $this;
    }

    public function getAnlInfo(): ?string
    {
        return $this->anlInfo;
    }

    public function setAnlInfo(string $anlInfo): self
    {
        $this->anlInfo = $anlInfo;

        return $this;
    }

    public function getAnlDataGoWs(): ?string
    {
        return $this->anlDataGoWs;
    }

    public function setAnlDataGoWs(string $anlDataGoWs): self
    {
        $this->anlDataGoWs = $anlDataGoWs;

        return $this;
    }

    public function getAnlModulAnz(): ?string
    {
        return $this->anlModulAnz;
    }

    public function setAnlModulAnz(string $anlModulAnz): self
    {
        $this->anlModulAnz = $anlModulAnz;

        return $this;
    }

    public function getAnlModulName(): ?string
    {
        return $this->anlModulName;
    }

    public function setAnlModulName(string $anlModulName): self
    {
        $this->anlModulName = $anlModulName;

        return $this;
    }

    public function getAnlModulLeistung(): ?string
    {
        return $this->anlModulLeistung;
    }

    public function setAnlModulLeistung(string $anlModulLeistung): self
    {
        $this->anlModulLeistung = $anlModulLeistung;

        return $this;
    }

    public function getAnlStartYear(): ?string
    {
        return $this->anlStartYear;
    }

    public function setAnlStartYear(string $anlStartYear): self
    {
        $this->anlStartYear = $anlStartYear;

        return $this;
    }

    public function getAnlDbIst(): ?string
    {
        return $this->anlDbIst;
    }

    public function setAnlDbIst(string $anlDbIst): self
    {
        $this->anlDbIst = $anlDbIst;

        return $this;
    }

    public function getAnlDbWs(): ?string
    {
        return $this->anlDbWs;
    }

    public function setAnlDbWs(string $anlDbWs): self
    {
        $this->anlDbWs = $anlDbWs;

        return $this;
    }

    public function getAnlSameWs(): ?string
    {
        return $this->anlSameWs;
    }

    public function setAnlSameWs(string $anlSameWs): self
    {
        $this->anlSameWs = $anlSameWs;

        return $this;
    }

    public function getSendWarnMail(): ?bool
    {
        return $this->sendWarnMail;
    }

    public function setSendWarnMail(string $sendWarnMail): self
    {
        $this->sendWarnMail = $sendWarnMail;

        return $this;
    }

    public function getAnlInputDaily(): ?string
    {
        return $this->anlInputDaily;
    }

    public function setAnlInputDaily(string $anlInputDaily): self
    {
        $this->anlInputDaily = $anlInputDaily;

        return $this;
    }

    public function getAnlGruppe(): ?string
    {
        return $this->anlGruppe;
    }

    public function setAnlGruppe(string $anlGruppe): self
    {
        $this->anlGruppe = $anlGruppe;

        return $this;
    }

    public function getAnlGruppeDc(): ?string
    {
        return $this->anlGruppeDc;
    }

    public function getDcGroupsAktiv(): bool
    {
        return $this->anlGruppeDc === 'Yes';
    }

    public function setAnlGruppeDc(string $anlGruppeDc): self
    {
        $this->anlGruppeDc = $anlGruppeDc;

        return $this;
    }

    public function getAnlZeitzone(): ?string
    {
        return $this->anlZeitzone;
    }

    public function setAnlZeitzone(string $anlZeitzone): self
    {
        $this->anlZeitzone = $anlZeitzone;

        return $this;
    }

    public function getAnlZeitzoneWs(): ?string
    {
        if($this->getWeatherStation()) {
            return $this->getWeatherStation()->gettimeZoneWeatherStation();
        } else {
            return false;
        }
    }

    public function getAnlZeitzoneIr(): ?string
    {
        return $this->getWeatherStation()->gettimeZoneWeatherStation();
    }

    public function setAnlZeitzoneWs(string $anlZeitzoneWs): self
    {
        $weatherStation = $this->getWeatherStation();
        $weatherStation->settimeZoneWeatherStation($anlZeitzoneWs);

        return $this;
    }


    public function getAnlIrChange(): ?string
    {
        if($this->getWeatherStation()) {
            return $this->getWeatherStation()->getChangeSensor() ? '1' : '0';
        } else {
            return false;
        }
    }

    public function setAnlIrChange(string $anlIrChange): self
    {
        $weatherStation = $this->getWeatherStation();
        $weatherStation->setChangeSensor($anlIrChange);

        return $this;
    }

    public function getAnlDbUnit(): ?string
    {
        return $this->anlDbUnit;
    }

    public function setAnlDbUnit(?string $anlDbUnit): self
    {
        $this->anlDbUnit = $anlDbUnit;

        return $this;
    }

    public function getAnlWindUnit(): ?string
    {
        return $this->anlWindUnit;
    }

    public function setAnlWindUnit(string $anlWindUnit): self
    {
        $this->anlWindUnit = $anlWindUnit;

        return $this;
    }

    public function getAnlView(): ?string
    {
        return $this->anlView;
    }

    public function setAnlView(string $anlView): self
    {
        $this->anlView = $anlView;

        return $this;
    }

    public function getAnlHidePlant(): ?string
    {
        return $this->anlHidePlant;
    }

    public function setAnlHidePlant(string $anlHidePlant): self
    {
        $this->anlHidePlant = $anlHidePlant;

        return $this;
    }

    public function getAnlGeoLat(): ?string
    {
        return $this->anlGeoLat;
    }

    public function setAnlGeoLat(string $anlGeoLat): self
    {
        $this->anlGeoLat = $anlGeoLat;

        return $this;
    }

    public function getAnlGeoLon(): ?string
    {
        return $this->anlGeoLon;
    }

    public function setAnlGeoLon(string $anlGeoLon): self
    {
        $this->anlGeoLon = $anlGeoLon;

        return $this;
    }

    public function getAnlMute(): ?string
    {
        return $this->anlMute;
    }

    public function setAnlMute(string $anlMute): self
    {
        $this->anlMute = $anlMute;

        return $this;
    }

    public function getAnlMuteUntil(): ?DateTime
    {
        return $this->anlMuteUntil;
    }

    public function setAnlMuteUntil(DateTime $anlMuteUntil): self
    {
        $this->anlMuteUntil = $anlMuteUntil;

        return $this;
    }

    public function getEigner(): ?Eigner
    {
        return $this->eigner;
    }

    public function setEigner(?Eigner $eigner): self
    {
        $this->eigner = $eigner;

        return $this;
    }

    public function getDbNameIst()
    {
        return $this->dbAnlagenData. ".db__pv_ist_".$this->getAnlIntnr();
    }
    public function getDbNameAcIst()
    {
        return $this->dbAnlagenData. ".db__pv_ist_".$this->getAnlIntnr();
    }

    public function getDbNameIstDc()
    {
        return $this->dbAnlagenData. ".db__pv_dcist_".$this->getAnlIntnr();
    }
    public function getDbNameDCIst()
    {
        return $this->dbAnlagenData. ".db__pv_dcist_".$this->getAnlIntnr();
    }

    public function getDbNameAcSoll()
    {
        return $this->dbAnlagenData. ".db__pv_soll_".$this->getAnlIntnr();
    }

    /**
     * @return string
     * @deprecated
     */
    public function getDbNameAcSollOld()
    {
        return "db__pv_soll_".$this->getAnlIntnr();
    }

    public function getDbNameSoll()
    {
        return $this->dbAnlagenData. ".db__pv_soll_".$this->getAnlIntnr();
    }

    public function getDbNameDcSoll()
    {
        return $this->dbAnlagenData. ".db__pv_dcsoll_".$this->getAnlIntnr();
    }
    /**
     * @return string
     * @deprecated
     */
    public function getDbNameDcSollOld()
    {
        return "db__pv_dcsoll_".$this->getAnlIntnr();
    }

    // get Weather Database
    public function getNameWeather()
    {
        $weatherDB = ($this->getAnlDbWs()) ? $this->getAnlDbWs() : $this->getAnlIntnr();
        $weatherDB = $this->getWeatherStation()->getDatabaseIdent();

        return $weatherDB;
    }
    public function getDbNameWeather()
    {
        ($this->getAnlDbWs()) ? $anlageDbWeather = $this->getAnlDbWs() : $anlageDbWeather = $this->getAnlIntnr();
        $anlageDbWeather = $this->getNameWeather();

        return $this->dbAnlagenData. ".db__pv_ws_".$anlageDbWeather;
    }
    public function getDbNameWeatherOld()
    {
        ($this->getAnlDbWs()) ? $anlageDbWeather = $this->getAnlDbWs() : $anlageDbWeather = $this->getAnlIntnr();
        $anlageDbWeather = $this->getNameWeather();

        return "db__pv_ws_".$anlageDbWeather;
    }

    /**
     * @return Collection|AnlageAcGroups[]
     */
    public function getAcGroups(): Collection
    {
        return $this->acGroups;
    }

    public function addAcGroup(AnlageAcGroups $acGroup): self
    {
        if (!$this->acGroups->contains($acGroup)) {
            $this->acGroups[] = $acGroup;
            $acGroup->setAnlage($this);
        }

        return $this;
    }

    public function removeAcGroup(AnlageAcGroups $acGroup): self
    {
        if ($this->acGroups->contains($acGroup)) {
            $this->acGroups->removeElement($acGroup);
            // set the owning side to null (unless already changed)
            if ($acGroup->getAnlage() === $this) {
                $acGroup->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getGroupsAc():array
    {
        $gruppe = [];

        /** @var AnlageAcGroups $group */
        foreach ($this->getAcGroups() as $group) {
            $gruppe[$group->getAcGroup()] = [
                "GMIN" => $group->getUnitFirst(),
                "GMAX" => $group->getUnitLast(),
                "INVNR" => $group->getAcGroup(),
                "GroupName" => $group->getAcGroupName()
            ];
        }
        return $gruppe;
    }

    public function getAnzInverterFromGroupsAC():int
    {
        $anzGruppen = 0;
        foreach ($this->getAcGroups() as $group) {
            $anzGruppen += $group->getUnitLast() - $group->getUnitFirst() + 1;
        }

        return $anzGruppen;
    }

    /**
     * @return Collection|AnlageAvailability[]
     */
    public function getAvailability(): Collection
    {
        return $this->availability;
    }

    public function addAvailability(AnlageAvailability $availability): self
    {
        if (!$this->availability->contains($availability)) {
            $this->availability[] = $availability;
            $availability->setAnlage($this);
        }

        return $this;
    }

    public function removeAvailability(AnlageAvailability $availability): self
    {
        if ($this->availability->contains($availability)) {
            $this->availability->removeElement($availability);
            // set the owning side to null (unless already changed)
            if ($availability->getAnlage() === $this) {
                $availability->setAnlage(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|AnlagenStatus[]
     */
    public function getLastStatus(): Collection
    {
        $criteria = AnlagenRepository::lastAnlagenStatusCriteria();

        return $this->status->matching($criteria);
    }

    public function addStatus(AnlagenStatus $status): self
    {
        if (!$this->status->contains($status)) {
            $this->status[] = $status;
            $status->setAnlage($this);
        }

        return $this;
    }

    public function removeStatus(AnlagenStatus $status): self
    {
        if ($this->status->contains($status)) {
            $this->status->removeElement($status);
            // set the owning side to null (unless already changed)
            if ($status->getAnlage() === $this) {
                $status->setAnlage(null);
            }
        }

        return $this;
    }


    /**
     * @return Collection|AnlagenPR[]
     */
    public function getPr(): Collection
    {
        return $this->pr;
    }

    /**
     * @return Collection|AnlagenPR
     */
    public function getLastPR(): Collection
    {
        $criteria = AnlagenRepository::lastAnlagenPRCriteria();
        return $this->pr->matching($criteria);
    }
    /**
     * @return Collection|AnlagenPR
     */
    public function getYesterdayPR(): Collection
    {
        $criteria = AnlagenRepository::yesterdayAnlagenPRCriteria();

        return $this->pr->matching($criteria);
    }

    public function addPr(AnlagenPR $pr): self
    {
        if (!$this->pr->contains($pr)) {
            $this->pr[] = $pr;
            $pr->setAnlage($this);
        }

        return $this;
    }

    public function removePr(AnlagenPR $pr): self
    {
        if ($this->pr->contains($pr)) {
            $this->pr->removeElement($pr);
            // set the owning side to null (unless already changed)
            if ($pr->getAnlage() === $this) {
                $pr->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowOnlyUpperIrr(): ?bool
    {
        return $this->showOnlyUpperIrr;
    }

    public function setShowOnlyUpperIrr(bool $showOnlyUpperIrr): self
    {
        $this->showOnlyUpperIrr = $showOnlyUpperIrr;

        return $this;
    }

    public function getShowStringCharts(): ?bool
    {
        return $this->showStringCharts;
    }

    public function setShowStringCharts(bool $showStringCharts): self
    {
        $this->showStringCharts = $showStringCharts;

        return $this;
    }

    public function getShowAvailability(): ?bool
    {
        return $this->showAvailability;
    }

    public function setShowAvailability(bool $showAvailability): self
    {
        $this->showAvailability = $showAvailability;

        return $this;
    }

    public function getShowAvailabilitySecond(): ?bool
    {
        return $this->showAvailabilitySecond;
    }

    public function setShowAvailabilitySecond(bool $showAvailabilitySecond): self
    {
        $this->showAvailabilitySecond = $showAvailabilitySecond;

        return $this;
    }

    public function getShowInverterPerformance(): ?bool
    {
        return $this->showInverterPerformance;
    }

    public function setShowInverterPerformance(bool $showInverterPerformance): self
    {
        $this->showInverterPerformance = $showInverterPerformance;

        return $this;
    }

    public function getShowMenuReporting(): ?bool
    {
        return $this->showMenuReporting;
    }

    public function setShowMenuReporting(bool $showMenuReporting): self
    {
        $this->showMenuReporting = $showMenuReporting;

        return $this;
    }

    public function getShowMenuDownload(): ?bool
    {
        return $this->showMenuDownload;
    }

    public function setShowMenuDownload(bool $showMenuDownload): self
    {
        $this->showMenuDownload = $showMenuDownload;

        return $this;
    }

    public function getUseNewDcSchema(): ?bool
    {
        return $this->useNewDcSchema;
    }

    public function setUseNewDcSchema(bool $useNewDcSchema): self
    {
        $this->useNewDcSchema = $useNewDcSchema;

        return $this;
    }

    public function getShowEvuDiag(): ?bool
    {
        return $this->showEvuDiag;
    }

    public function setShowEvuDiag(bool $showEvuDiag): self
    {
        $this->showEvuDiag = $showEvuDiag;

        return $this;
    }

    public function getShowCosPhiDiag(): ?bool
    {
        return $this->showCosPhiDiag;
    }

    public function setShowCosPhiDiag(bool $showCosPhiDiag): self
    {
        $this->showCosPhiDiag = $showCosPhiDiag;

        return $this;
    }

    public function getShowCosPhiPowerDiag(): ?bool
    {
        return $this->showCosPhiPowerDiag;
    }

    public function setShowCosPhiPowerDiag(bool $showCosPhiPowerDiag): self
    {
        $this->showCosPhiPowerDiag = $showCosPhiPowerDiag;

        return $this;
    }

    /**
     * @return array
     */
    public function getGroupsDc() {
        $gruppe = [];
        /** @var AnlageGroups $row */
        foreach ($this->getGroups() as $row) {
            $grpnr = $row->getDcGroup();
            $gruppe[$grpnr] = [
                "ANLID" => $row->getAnlage()->getAnlId(),
                "GMIN" => $row->getUnitFirst(),
                "GMAX" => $row->getUnitLast(),
                "GRPNR" => $row->getDcGroup(),
                "GroupName" => $row->getDcGroupName()
            ];
        }

        return $gruppe;
    }


    /**
     * @return array
     */
    function getInvertersFromDcGroups(): array
    {
        $inverters = [];
        $groups = $this->getGroups();
        foreach ($groups as $key => $group) {
            for ($i = $group->getUnitFirst(); $i <= $group->getUnitLast(); $i++) {
                $inverters[] = [
                    'inverterNo'    => $i,
                    'group'         => $group->getDcGroupName(),
                    'name'          => "Inv. #$i",
                ];
            }
        }

        return $inverters;
    }

    public function getUseCosPhi(): ?bool
    {
        return $this->useCosPhi;
    }

    public function setUseCosPhi(bool $useCosPhi): self
    {
        $this->useCosPhi = $useCosPhi;

        return $this;
    }

    public function getUseCustPRAlgorithm(): ?string
    {
        return $this->useCustPRAlgorithm;
    }

    public function setUseCustPRAlgorithm(?string $useCustPRAlgorithm): self
    {
        $this->useCustPRAlgorithm = $useCustPRAlgorithm;

        return $this;
    }

    /**
     * @return Collection|AnlagenReports[]
     */
    public function getAnlagenReports(): Collection
    {
        return $this->anlagenReports;
    }

    public function addAnlagenReport(AnlagenReports $anlagenReport): self
    {
        if (!$this->anlagenReports->contains($anlagenReport)) {
            $this->anlagenReports[] = $anlagenReport;
            $anlagenReport->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlagenReport(AnlagenReports $anlagenReport): self
    {
        if ($this->anlagenReports->contains($anlagenReport)) {
            $this->anlagenReports->removeElement($anlagenReport);
            // set the owning side to null (unless already changed)
            if ($anlagenReport->getAnlage() === $this) {
                $anlagenReport->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowGraphDcCurrInv(): ?bool
    {
        return $this->showGraphDcCurrInv;
    }

    public function setShowGraphDcCurrInv(bool $showGraphDcCurrInv): self
    {
        $this->showGraphDcCurrInv = $showGraphDcCurrInv;

        return $this;
    }

    public function getShowGraphDcCurrGrp(): ?bool
    {
        return $this->showGraphDcCurrGrp;
    }

    public function setShowGraphDcCurrGrp(bool $showGraphDcCurrGrp): self
    {
        $this->showGraphDcCurrGrp = $showGraphDcCurrGrp;

        return $this;
    }

    public function getShowGraphVoltGrp(): ?bool
    {
        return $this->showGraphVoltGrp;
    }

    public function setShowGraphVoltGrp(bool $showGraphVoltGrp): self
    {
        $this->showGraphVoltGrp = $showGraphVoltGrp;

        return $this;
    }

    public function getShowGraphDcInverter(): ?bool
    {
        return $this->showGraphDcInverter;
    }

    public function setShowGraphDcInverter(bool $showGraphDcInverter): self
    {
        $this->showGraphDcInverter = $showGraphDcInverter;

        return $this;
    }

    public function getShowGraphIrrPlant(): ?bool
    {
        return $this->showGraphIrrPlant;
    }

    public function setShowGraphIrrPlant(bool $showGraphIrrPlant): self
    {
        $this->showGraphIrrPlant = $showGraphIrrPlant;

        return $this;
    }

    public function getShowPR(): ?bool
    {
        return $this->showPR;
    }

    public function setShowPR(bool $showPR): self
    {
        $this->showPR = $showPR;

        return $this;
    }


    public function getOpenWeather()
    {
        $weatherArray = [];
        $apiKey = "795982a4e205f23abb3ce3cf9a9a032a";
        $lat = $this->anlGeoLat;
        $lng = $this->anlGeoLon;
        if ($lat and $lng) {
            $urli     = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lng&lang=en&APPID=$apiKey";
            $contents = file_get_contents($urli);
            $clima    = json_decode($contents);
            if ($clima) {
                $weatherArray['tempC']          = round(($clima->main->temp - 273.15), 0);
                $weatherArray['tempF']          = round(((($clima->main->temp * 9) / 5) + 32), 0);
                $weatherArray['iconCountry']    = strtolower($clima->sys->country);
                $weatherArray['iconWeather']    = "https://openweathermap.org/img/w/" . strtolower($clima->weather[0]->icon) . ".png";
                $weatherArray['description']    = @$clima->weather[0]->description;
                $weatherArray['cityName']       = @$clima->name;

                return $weatherArray;
            }
        }

        return false;
    }

    /**
     * @return Collection|AnlageEventMail[]
     */
    public function getEventMails(): Collection
    {
        return $this->eventMails;
    }

    public function addEventMail(AnlageEventMail $eventMail): self
    {
        if (!$this->eventMails->contains($eventMail)) {
            $this->eventMails[] = $eventMail;
            $eventMail->setAnlage($this);
        }

        return $this;
    }

    public function removeEventMail(AnlageEventMail $eventMail): self
    {
        if ($this->eventMails->contains($eventMail)) {
            $this->eventMails->removeElement($eventMail);
            // set the owning side to null (unless already changed)
            if ($eventMail->getAnlage() === $this) {
                $eventMail->setAnlage(null);
            }
        }

        return $this;
    }

    public function getIrrLimitAvailability(): ?string
    {
        return $this->irrLimitAvailability;
    }

    public function setIrrLimitAvailability(string $irrLimitAvailability): self
    {
        $this->irrLimitAvailability = $irrLimitAvailability;

        return $this;
    }

    public function getContractualAvailability(): ?string
    {
        return $this->contractualAvailability;
    }

    public function setContractualAvailability(string $contractualAvailability): self
    {
        $this->contractualAvailability =  str_replace(',', '.', $contractualAvailability);

        return $this;
    }

    public function getContractualPR(): ?string
    {
        return $this->contractualPR;
    }

    public function setContractualPR(string $contractualPR): self
    {
        $this->contractualPR =  str_replace(',', '.', $contractualPR);

        return $this;
    }

    public function getContractualPower(): ?string
    {
        return $this->contractualPower;
    }

    public function setContractualPower(string $contractualPower): self
    {
        $this->contractualPower =  str_replace(',', '.', $contractualPower);

        return $this;
    }

    /**
     * @return Collection|AnlageCase5[]
     */
    public function getAnlageCase5s(): Collection
    {
        return $this->anlageCase5s;
    }

    /**
     * @return Collection|AnlageCase5[]
     */
    public function getAnlageCase5sDate($date): Collection
    {
        $criteria = AnlagenRepository::case5ByDateCriteria($date);

        return $this->anlageCase5s->matching($criteria);

    }

    public function addAnlageCase5(AnlageCase5 $anlageCase5): self
    {
        if (!$this->anlageCase5s->contains($anlageCase5)) {
            $this->anlageCase5s[] = $anlageCase5;
            $anlageCase5->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageCase5(AnlageCase5 $anlageCase5): self
    {
        if ($this->anlageCase5s->contains($anlageCase5)) {
            $this->anlageCase5s->removeElement($anlageCase5);
            // set the owning side to null (unless already changed)
            if ($anlageCase5->getAnlage() === $this) {
                $anlageCase5->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AnlagePVSystDaten[]
     */
    public function getAnlagePVSystDatens(): Collection
    {
        return $this->anlagePVSystDatens;
    }

    public function addAnlagePVSystDaten(AnlagePVSystDaten $anlagePVSystDaten): self
    {
        if (!$this->anlagePVSystDatens->contains($anlagePVSystDaten)) {
            $this->anlagePVSystDatens[] = $anlagePVSystDaten;
            $anlagePVSystDaten->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlagePVSystDaten(AnlagePVSystDaten $anlagePVSystDaten): self
    {
        if ($this->anlagePVSystDatens->removeElement($anlagePVSystDaten)) {
            // set the owning side to null (unless already changed)
            if ($anlagePVSystDaten->getAnlage() === $this) {
                $anlagePVSystDaten->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowPvSyst(): ?bool
    {
        return $this->showPvSyst;
    }

    public function setShowPvSyst(bool $showPvSyst): self
    {
        $this->showPvSyst = $showPvSyst;

        return $this;
    }

    public function getWeatherStation(): ?weatherStation
    {
        return $this->weatherStation;
    }

    public function setWeatherStation(?WeatherStation $weatherStation): self
    {
        $this->weatherStation = $weatherStation;

        return $this;
    }

    public function getPacDate(): ?\DateTime
    {
        return $this->pacDate;
    }

    public function setPacDate(?\DateTime $pacDate): self
    {
        $this->pacDate = $pacDate;

        return $this;
    }

    public function getFacDate(): ?\DateTime
    {
        return $this->facDate;
    }

    public function setFacDate(?\DateTime $facDate): self
    {
        $this->facDate = $facDate;

        return $this;
    }

    public function getUsePac(): ?bool
    {
        return $this->usePac;
    }

    public function setUsePac(bool $usePac): self
    {
        $this->usePac = $usePac;

        return $this;
    }

    /**
     * @return Collection|AnlageForecast[]
     */
    public function getAnlageForecasts(): Collection
    {
        return $this->anlageForecasts;
    }

    public function addAnlageForecast(AnlageForecast $anlageForecast): self
    {
        if (!$this->anlageForecasts->contains($anlageForecast)) {
            $this->anlageForecasts[] = $anlageForecast;
            $anlageForecast->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageForecast(AnlageForecast $anlageForecast): self
    {
        if ($this->anlageForecasts->removeElement($anlageForecast)) {
            // set the owning side to null (unless already changed)
            if ($anlageForecast->getAnlage() === $this) {
                $anlageForecast->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AnlageGroups[]
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }
    

    public function addGroup(AnlageGroups $group): self
    {
        if (!$this->groups->contains($group)) {
            $this->groups[] = $group;
            $group->setAnlage($this);
        }

        return $this;
    }

    public function removeGroup(AnlageGroups $group): self
    {
        if ($this->groups->removeElement($group)) {
            // set the owning side to null (unless already changed)
            if ($group->getAnlage() === $this) {
                $group->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AnlageModules[]
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(AnlageModules $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules[] = $module;
            $module->setAnlage($this);
        }

        return $this;
    }

    public function removeModule(AnlageModules $module): self
    {
        if ($this->modules->removeElement($module)) {
            // set the owning side to null (unless already changed)
            if ($module->getAnlage() === $this) {
                $module->setAnlage(null);
            }
        }

        return $this;
    }

    public function getIsOstWestAnlage(): ?bool
    {
        return $this->isOstWestAnlage;
    }

    public function setIsOstWestAnlage(bool $isOstWestAnlage): self
    {
        $this->isOstWestAnlage = $isOstWestAnlage;

        return $this;
    }

    public function getMinIrradiationAvailability(): ?string
    {
        return $this->minIrradiationAvailability;
    }

    public function setMinIrradiationAvailability(?string $minIrradiationAvailability): self
    {
        $this->minIrradiationAvailability =  str_replace(',', '.', $minIrradiationAvailability);

        return $this;
    }

    /**
     * @return Collection|TimesConfig[]
     */
    public function getTimesConfigs(): Collection
    {
        return $this->timesConfigs;
    }

    public function addTimesConfig(TimesConfig $timesConfig): self
    {
        if (!$this->timesConfigs->contains($timesConfig)) {
            $this->timesConfigs[] = $timesConfig;
            $timesConfig->setAnlage($this);
        }

        return $this;
    }

    public function removeTimesConfig(TimesConfig $timesConfig): self
    {
        if ($this->timesConfigs->removeElement($timesConfig)) {
            // set the owning side to null (unless already changed)
            if ($timesConfig->getAnlage() === $this) {
                $timesConfig->setAnlage(null);
            }
        }

        return $this;
    }

    public function getShowForecast(): ?bool
    {
        return $this->showForecast;
    }

    public function setShowForecast(bool $showForecast): self
    {
        $this->showForecast = $showForecast;

        return $this;
    }

    /**
     * @return Collection|AnlageGridMeterDay[]
     */
    public function getAnlageGridMeterDays(): Collection
    {
        return $this->anlageGridMeterDays;
    }

    public function addAnlageGridMeterDay(AnlageGridMeterDay $anlageGridMeterDay): self
    {
        if (!$this->anlageGridMeterDays->contains($anlageGridMeterDay)) {
            $this->anlageGridMeterDays[] = $anlageGridMeterDay;
            $anlageGridMeterDay->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageGridMeterDay(AnlageGridMeterDay $anlageGridMeterDay): self
    {
        if ($this->anlageGridMeterDays->removeElement($anlageGridMeterDay)) {
            // set the owning side to null (unless already changed)
            if ($anlageGridMeterDay->getAnlage() === $this) {
                $anlageGridMeterDay->setAnlage(null);
            }
        }

        return $this;
    }

    public function getUseGridMeterDayData(): ?bool
    {
        return $this->useGridMeterDayData;
    }

    public function setUseGridMeterDayData(bool $useGridMeterDayData): self
    {
        $this->useGridMeterDayData = $useGridMeterDayData;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection|OpenWeather
     */
    public function getLastOpenWeather(): Collection
    {
        $criteria = AnlagenRepository::lastOpenWeatherCriteria();

        return $this->openWeather->matching($criteria);
    }

    public function addOpenWeather(OpenWeather $openWeather): self
    {
        if (!$this->openWeather->contains($openWeather)) {
            $this->openWeather[] = $openWeather;
            $openWeather->setAnlage($this);
        }

        return $this;
    }

    public function removeOpenWeather(OpenWeather $openWeather): self
    {
        if ($this->openWeather->removeElement($openWeather)) {
            // set the owning side to null (unless already changed)
            if ($openWeather->getAnlage() === $this) {
                $openWeather->setAnlage(null);
            }
        }

        return $this;
    }

    public function getCalcPR(): ?bool
    {
        return $this->calcPR;
    }

    public function setCalcPR(bool $calcPR): self
    {
        $this->calcPR = $calcPR;

        return $this;
    }

    public function getPacDuration(): ?string
    {
        return $this->pacDuration;
    }

    public function setPacDuration(string $pacDuration): self
    {
        $this->pacDuration = $pacDuration;

        return $this;
    }

    public function getKwPeakPvSyst(): ?string
    {
        return $this->kwPeakPvSyst;
    }

    public function setKwPeakPvSyst(?string $kwPeakPvSyst): self
    {
        $this->kwPeakPvSyst = $kwPeakPvSyst;

        return $this;
    }

    public function getDesignPR(): ?string
    {
        return $this->designPR;
    }

    public function setDesignPR(?string $designPR): self
    {
        $this->designPR = $designPR;

        return $this;
    }

    public function getFacDateStart(): ?\DateTime
    {
        return $this->facDateStart;
    }

    public function setFacDateStart(?\DateTime $facDateStart): self
    {
        $this->facDateStart = $facDateStart;

        return $this;
    }

    public function getPacDateEnd(): ?\DateTime
    {
        return $this->pacDateEnd;
    }

    public function setPacDateEnd(?\DateTime $pacDateEnd): self
    {
        $this->pacDateEnd = $pacDateEnd;

        return $this;
    }

    /**
     * @return Collection|AnlagenPvSystMonth[]
     */
    public function getPvSystMonths(): Collection
    {
        return $this->anlagenPvSystMonths;
    }

    public function addPvSystMonth(AnlagenPvSystMonth $anlagenPvSystMonth): self
    {
        if (!$this->anlagenPvSystMonths->contains($anlagenPvSystMonth)) {
            $this->anlagenPvSystMonths[] = $anlagenPvSystMonth;
            $anlagenPvSystMonth->setAnlage($this);
        }

        return $this;
    }

    public function removePvSystMonth(AnlagenPvSystMonth $anlagenPvSystMonth): self
    {
        if ($this->anlagenPvSystMonths->removeElement($anlagenPvSystMonth)) {
            // set the owning side to null (unless already changed)
            if ($anlagenPvSystMonth->getAnlage() === $this) {
                $anlagenPvSystMonth->setAnlage(null);
            }
        }

        return $this;
    }

    public function getOneMonthPvSyst($month):?AnlagenPvSystMonth
    {
        $criteria = AnlagenRepository::oneMonthPvSystCriteria($month);
        $result = $this->anlagenPvSystMonths->matching($criteria);

        return $result[0];
    }

    public function getLid(): string
    {
        return $this->lid;
    }

    public function setLid(string $lid): self
    {
        $this->lid = $lid;

        return $this;
    }

    public function getAnnualDegradation(): ?string
    {
        return $this->annualDegradation;
    }

    public function setAnnualDegradation(?string $annualDegradation): self
    {
        $this->annualDegradation = $annualDegradation;

        return $this;
    }

    public function getPldPR(): ?string
    {
        return $this->pldPR;
    }

    public function setPldPR(?string $pldPR): self
    {
        $this->pldPR = $pldPR;

        return $this;
    }

    public function getEpcReportType(): ?string
    {
        return $this->epcReportType;
    }

    public function setEpcReportType(string $epcReportType): self
    {
        $this->epcReportType = $epcReportType;

        return $this;
    }

    /**
     * @return Collection|AnlagenMonthlyData[]
     */
    public function getMonthlyYields(): Collection
    {
        return $this->anlagenMonthlyData;
    }

    /**
     * @return Collection|AnlagenMonthlyData[]
     */
    public function getAnlagenMonthlyData(): Collection
    {
        return $this->anlagenMonthlyData;
    }

    public function addMonthlyYield(AnlagenMonthlyData $anlagenMonthlyData): self
    {
        if (!$this->anlagenMonthlyData->contains($anlagenMonthlyData)) {
            $this->anlagenMonthlyData[] = $anlagenMonthlyData;
            $anlagenMonthlyData->setAnlage($this);
        }

        return $this;
    }

    public function removeMonthlyYield(AnlagenMonthlyData $anlagenMonthlyData): self
    {
        if ($this->anlagenMonthlyData->removeElement($anlagenMonthlyData)) {
            // set the owning side to null (unless already changed)
            if ($anlagenMonthlyData->getAnlage() === $this) {
                $anlagenMonthlyData->setAnlage(null);
            }
        }

        return $this;
    }

    public function getTransformerTee(): ?string
    {
        return $this->transformerTee;
    }

    public function setTransformerTee(string $transformerTee): self
    {
        $this->transformerTee = $transformerTee;

        return $this;
    }

    public function getGuaranteeTee(): ?string
    {
        return $this->guaranteeTee;
    }

    public function setGuaranteeTee(string $guaranteeTee): self
    {
        $this->guaranteeTee = $guaranteeTee;

        return $this;
    }

    public function getPldYield(): ?string
    {
        return $this->pldYield;
    }

    public function setPldYield(string $pldYield): self
    {
        $this->pldYield = $pldYield;

        return $this;
    }

    public function getProjektNr(): ?string
    {
        return $this->projektNr;
    }

    public function setProjektNr(string $projektNr): self
    {
        $this->projektNr = $projektNr;

        return $this;
    }

    /**
     * @return Collection|AnlageLegendReport[]
     */
    public function getLegendEpcReports(): Collection
    {
        $criteria = AnlagenRepository::selectLegendType('epc');

        return $this->anlageLegendReports->matching($criteria);
    }

    public function addLegendEpcReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->addAnlageLegendReport($anlageLegendReport);
    }

    public function removeLegendEpcReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->removeAnlageLegendReport($anlageLegendReport);
    }

    /**
     * @return Collection|AnlageLegendReport[]
     */
    public function getLegendMonthlyReports(): Collection
    {
        $criteria = AnlagenRepository::selectLegendType('monthly');

        return $this->anlageLegendReports->matching($criteria);
    }

    public function addLegendMonthlyReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->addAnlageLegendReport($anlageLegendReport);
    }

    public function removeLegendMonthlyReport(AnlageLegendReport $anlageLegendReport): self
    {
        return $this->removeAnlageLegendReport($anlageLegendReport);
    }

    /**
     * @return Collection|AnlageLegendReport[]
     */
    public function getAnlageLegendReports(): Collection
    {
        return $this->anlageLegendReports;
    }

    public function addAnlageLegendReport(AnlageLegendReport $anlageLegendReport): self
    {
        if (!$this->anlageLegendReports->contains($anlageLegendReport)) {
            $this->anlageLegendReports[] = $anlageLegendReport;
            $anlageLegendReport->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageLegendReport(AnlageLegendReport $anlageLegendReport): self
    {
        if ($this->anlageLegendReports->removeElement($anlageLegendReport)) {
            // set the owning side to null (unless already changed)
            if ($anlageLegendReport->getAnlage() === $this) {
                $anlageLegendReport->setAnlage(null);
            }
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->Notes;
    }

    public function setNotes(?string $Notes): self
    {
        $this->Notes = $Notes;

        return $this;
    }

    /**
     * @return Collection|AnlageMonth[]
     */
    public function getAnlageMonth(): Collection
    {
        return $this->anlageMonth;
    }

    public function addAnlageMonth(AnlageMonth $anlageMonth): self
    {
        if (!$this->anlageMonth->contains($anlageMonth)) {
            $this->anlageMonth[] = $anlageMonth;
            $anlageMonth->setAnlage($this);
        }

        return $this;
    }

    public function removeAnlageMonth(AnlageMonth $anlageMonth): self
    {
        if ($this->anlageMonth->removeElement($anlageMonth)) {
            // set the owning side to null (unless already changed)
            if ($anlageMonth->getAnlage() === $this) {
                $anlageMonth->setAnlage(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AnlageInverters[]
     */
    public function getInverters(): Collection
    {
        return $this->Inverters;
    }

    public function addInverter(AnlageInverters $inverter): self
    {
        if (!$this->Inverters->contains($inverter)) {
            $this->Inverters[] = $inverter;
            $inverter->setAnlage($this);
        }

        return $this;
    }

    public function removeInverter(AnlageInverters $inverter): self
    {
        if ($this->Inverters->removeElement($inverter)) {
            // set the owning side to null (unless already changed)
            if ($inverter->getAnlage() === $this) {
                $inverter->setAnlage(null);
            }
        }

        return $this;
    }

    public function getTempCorrCellTypeAvg(): ?string
    {
        return $this->tempCorrCellTypeAvg;
    }

    public function setTempCorrCellTypeAvg(string $tempCorrCellTypeAvg): self
    {
        $this->tempCorrCellTypeAvg = $tempCorrCellTypeAvg;

        return $this;
    }

    public function getTempCorrGamma(): ?string
    {
        return $this->tempCorrGamma;
    }

    public function setTempCorrGamma(string $tempCorrGamma): self
    {
        $this->tempCorrGamma = $tempCorrGamma;

        return $this;
    }

    public function getTempCorrA(): ?string
    {
        return $this->tempCorrA;
    }

    public function setTempCorrA(string $tempCorrA): self
    {
        $this->tempCorrA = $tempCorrA;

        return $this;
    }

    public function getTempCorrB(): ?string
    {
        return $this->tempCorrB;
    }

    public function setTempCorrB(string $tempCorrB): self
    {
        $this->tempCorrB = $tempCorrB;

        return $this;
    }

    public function getTempCorrDeltaTCnd(): ?string
    {
        return $this->tempCorrDeltaTCnd;
    }

    public function setTempCorrDeltaTCnd(string $tempCorrDeltaTCnd): self
    {
        $this->tempCorrDeltaTCnd = $tempCorrDeltaTCnd;

        return $this;
    }

    public function getPldNPValue(): ?string
    {
        return $this->pldNPValue;
    }

    public function setPldNPValue(string $pldNPValue): self
    {
        $this->pldNPValue = $pldNPValue;

        return $this;
    }

    public function getPldDivisor(): ?string
    {
        return $this->pldDivisor;
    }

    public function setPldDivisor(string $pldDivisor): self
    {
        $this->pldDivisor = $pldDivisor;

        return $this;
    }

    public function getEpcReportStart(): ?\DateTime
    {
        return $this->epcReportStart;
    }

    public function setEpcReportStart(?\DateTime $epcReportStart): self
    {
        $this->epcReportStart = $epcReportStart;

        return $this;
    }

    public function getEpcReportEnd(): ?\DateTime
    {
        return $this->epcReportEnd;
    }

    public function setEpcReportEnd(?\DateTime $epcReportEnd): self
    {
        $this->epcReportEnd = $epcReportEnd;

        return $this;
    }

    public function getInverterStartVoltage(): ?string
    {
        return $this->inverterStartVoltage;
    }

    public function setInverterStartVoltage(string $inverterStartVoltage): self
    {
        $this->inverterStartVoltage = $inverterStartVoltage;

        return $this;
    }

    public function getUseLowerIrrForExpected(): ?bool
    {
        return $this->useLowerIrrForExpected;
    }

    public function setUseLowerIrrForExpected(bool $useLowerIrrForExpected): self
    {
        $this->useLowerIrrForExpected = $useLowerIrrForExpected;

        return $this;
    }

    public function getEpcReportNote(): ?string
    {
        return $this->epcReportNote;
    }

    public function setEpcReportNote(string $epcReportNote): self
    {
        $this->epcReportNote = $epcReportNote;

        return $this;
    }

    public function getSourceInvName(): ?string
    {
        return $this->sourceInvName;
    }

    public function setSourceInvName(string $sourceInvName): self
    {
        $this->sourceInvName = $sourceInvName;

        return $this;
    }
}
