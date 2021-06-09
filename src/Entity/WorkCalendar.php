<?php

namespace App\Entity;

use App\Repository\WorkCalendarRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WorkCalendarRepository::class)
 */
class WorkCalendar
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $year;

    /**
     * @ORM\Column(type="integer")
     */
    private $vacationDays;

    /**
     * @ORM\Column(type="integer")
     */
    private $particularBusinessLeave;

    /**
     * @ORM\Column(type="integer")
     */
    private $overtimeDays;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVacationDays(): ?int
    {
        return $this->vacationDays;
    }

    public function setVacationDays(int $vacationDays): self
    {
        $this->vacationDays = $vacationDays;

        return $this;
    }

    public function getParticularBusinessLeave(): ?int
    {
        return $this->particularBusinessLeave;
    }

    public function setParticularBusinessLeave(int $particularBusinessLeave): self
    {
        $this->particularBusinessLeave = $particularBusinessLeave;

        return $this;
    }

    public function getOvertimeDays(): ?int
    {
        return $this->overtimeDays;
    }

    public function setOvertimeDays(int $overtimeDays): self
    {
        $this->overtimeDays = $overtimeDays;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }
}
