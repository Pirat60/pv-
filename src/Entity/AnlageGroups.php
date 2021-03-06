<?php

namespace App\Entity;

use App\Repository\GroupsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=GroupsRepository::class)
 */
class AnlageGroups
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $dcGroup;

    /**
     * @ORM\Column(type="string", length=30)
     */
    private string $dcGroupName;

    /**
     * @ORM\Column(type="integer")
     */
    private int $acGroup;

    /**
     * @ORM\Column(type="integer")
     */
    private int $unitFirst;

    /**
     * @ORM\Column(type="integer")
     */
    private int $unitLast;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrUpper;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $irrLower;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $shadowLoss;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $cabelLoss = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $secureLoss = '0';

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $factorAC;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private string $limitAc;

    /**
     * @ORM\OneToMany(targetEntity=AnlageGroupMonths::class, mappedBy="anlageGroup", cascade={"persist", "remove"})
     * @ORM\OrderBy({"month" = "ASC"})
     */
    private $months;

    /**
     * @ORM\OneToMany(targetEntity=AnlageGroupModules::class, mappedBy="anlageGroup", cascade={"persist", "remove"})
     */
    private $modules;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="groups")
     */
    private  $anlage;

    /**
     * @ORM\ManyToOne(targetEntity=WeatherStation::class)
     */
    private $weatherStation;



    public function __construct()
    {
        $this->months = new ArrayCollection();
        $this->modules = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDcGroup(): ?int
    {
        return $this->dcGroup;
    }

    public function setDcGroup(int $dcGroup): self
    {
        $this->dcGroup = $dcGroup;

        return $this;
    }

    public function getDcGroupName(): ?string
    {
        return $this->dcGroupName;
    }

    public function setDcGroupName(string $dcGroupName): self
    {
        $this->dcGroupName = $dcGroupName;

        return $this;
    }

    public function getAcGroup(): ?int
    {
        return $this->acGroup;
    }

    public function setAcGroup(int $acGroup): self
    {
        $this->acGroup = $acGroup;

        return $this;
    }

    public function getUnitFirst(): ?int
    {
        return $this->unitFirst;
    }

    public function setUnitFirst(int $unitFirst): self
    {
        $this->unitFirst = $unitFirst;

        return $this;
    }

    public function getUnitLast(): ?int
    {
        return $this->unitLast;
    }

    public function setUnitLast(int $unitLast): self
    {
        $this->unitLast = $unitLast;

        return $this;
    }

    public function getIrrUpper(): ?string
    {
        return $this->irrUpper;
    }

    public function setIrrUpper(string $irrUpper): self
    {
        $this->irrUpper =  str_replace(',', '.', $irrUpper);

        return $this;
    }

    public function getIrrLower(): ?string
    {
        return $this->irrLower;
    }

    public function setIrrLower(string $irrLower): self
    {
        $this->irrLower =  str_replace(',', '.', $irrLower);

        return $this;
    }

    public function getShadowLoss(): ?string
    {
        return $this->shadowLoss;
    }

    public function setShadowLoss(string $shadowLoss): self
    {
        $this->shadowLoss =  str_replace(',', '.', $shadowLoss);

        return $this;
    }

    public function getCabelLoss(): ?string
    {
        return $this->cabelLoss;
    }

    public function setCabelLoss(string $cabelLoss): self
    {
        $this->cabelLoss =  str_replace(',', '.', $cabelLoss);

        return $this;
    }

    public function getSecureLoss(): ?string
    {
        return $this->secureLoss;
    }

    public function setSecureLoss(string $secureLoss): self
    {
        $this->secureLoss =  str_replace(',', '.', $secureLoss);

        return $this;
    }

    /**
     * @return Collection|AnlageGroupMonths[]
     */
    public function getMonths(): Collection
    {
        return $this->months;
    }

    public function addMonth(AnlageGroupMonths $month): self
    {
        if (!$this->months->contains($month)) {
            $this->months[] = $month;
            $month->setAnlageGroup($this);
        }
        return $this;
    }

    public function removeMonth(AnlageGroupMonths $month): self
    {
        if ($this->months->removeElement($month)) {
            // set the owning side to null (unless already changed)
            if ($month->getAnlageGroup() === $this) {
                $month->setAnlageGroup(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|AnlageGroupModules[]
     */
    public function getModules(): Collection
    {
        return $this->modules;
    }

    public function addModule(AnlageGroupModules $module): self
    {
        if (!$this->modules->contains($module)) {
            $this->modules[] = $module;
            $module->setAnlageGroup($this);
        }

        return $this;
    }

    public function removeModule(AnlageGroupModules $module): self
    {
        if ($this->modules->removeElement($module)) {
            // set the owning side to null (unless already changed)
            if ($module->getAnlageGroup() === $this) {
                $module->setAnlageGroup(null);
            }
        }

        return $this;
    }

    public function getAnlage(): ?Anlage
    {
        return $this->anlage;
    }

    public function setAnlage(?Anlage $anlage): self
    {
        $this->anlage = $anlage;

        return $this;
    }

    public function getFactorAC(): ?string
    {
        return $this->factorAC;
    }

    public function setFactorAC(string $factorAC): self
    {
        $this->factorAC =  str_replace(',', '.', $factorAC);

        return $this;
    }

    public function getWeatherStation(): ?WeatherStation
    {
        return $this->weatherStation;
    }

    public function setWeatherStation(?WeatherStation $weatherStation): self
    {
        $this->weatherStation = $weatherStation;

        return $this;
    }

    public function getLimitAc(): ?string
    {
        return $this->limitAc;
    }

    public function setLimitAc(string $limitAc): self
    {
        $this->limitAc = $limitAc;

        return $this;
    }
}
