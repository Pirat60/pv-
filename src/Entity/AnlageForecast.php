<?php

namespace App\Entity;

use App\Repository\ForecastRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ForecastRepository::class)
 */
class AnlageForecast
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Anlage::class, inversedBy="anlageForecasts")
     */
    private $anlage;

    /**
     * @ORM\Column(type="integer")
     */
    private $week;

    /**
     * @ORM\Column(type="integer")
     */
    private $day;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $expectedWeek;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $divergensMinus;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $divergensPlus;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $minNorm;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $maxNorm;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getWeek(): ?int
    {
        return $this->week;
    }

    public function setWeek(int $week): self
    {
        $this->week = $week;

        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;

        return $this;
    }

    public function getExpectedWeek(): ?string
    {
        return $this->expectedWeek;
    }

    public function setExpectedWeek(string $expectedWeek): self
    {
        $this->expectedWeek = $expectedWeek;

        return $this;
    }

    public function getDivergensMinus(): ?string
    {
        return $this->divergensMinus;
    }

    public function setDivergensMinus(string $divergensMinus): self
    {
        $this->divergensMinus = $divergensMinus;

        return $this;
    }

    public function getDivergensPlus(): ?string
    {
        return $this->divergensPlus;
    }

    public function setDivergensPlus(string $divergensPlus): self
    {
        $this->divergensPlus = $divergensPlus;

        return $this;
    }

    public function getMinNorm(): ?string
    {
        return $this->minNorm;
    }

    public function setMinNorm(string $minNorm): self
    {
        $this->minNorm = $minNorm;

        return $this;
    }

    public function getMaxNorm(): ?string
    {
        return $this->maxNorm;
    }

    public function setMaxNorm(string $maxNorm): self
    {
        $this->maxNorm = $maxNorm;

        return $this;
    }
}
