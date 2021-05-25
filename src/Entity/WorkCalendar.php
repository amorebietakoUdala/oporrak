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
    private $annualMaximumWorkingHours;

    /**
     * @ORM\Column(type="integer")
     */
    private $annualMaximumWorkingDays;

    /**
     * @ORM\Column(type="integer")
     */
    private $dailyWorkingHours;

    /**
     * @ORM\Column(type="integer")
     */
    private $dailyWorkingMinutes;

    /**
     * @ORM\Column(type="integer")
     */
    private $break;

    /**
     * @ORM\Column(type="integer")
     */
    private $annualTotalWorkHours;

    /**
     * @ORM\Column(type="integer")
     */
    private $overtimeHours;

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

    public function getAnnualMaximumWorkingHours(): ?int
    {
        return $this->annualMaximumWorkingHours;
    }

    public function setAnnualMaximumWorkingHours(int $annualMaximumWorkingHours): self
    {
        $this->annualMaximumWorkingHours = $annualMaximumWorkingHours;

        return $this;
    }

    public function getAnnualMaximumWorkingDays(): ?int
    {
        return $this->annualMaximumWorkingDays;
    }

    public function setAnnualMaximumWorkingDays(int $annualMaximumWorkingDays): self
    {
        $this->annualMaximumWorkingDays = $annualMaximumWorkingDays;

        return $this;
    }

    public function getDailyWorkingHours(): ?int
    {
        return $this->dailyWorkingHours;
    }

    public function setDailyWorkingHours(int $dailyWorkingHours): self
    {
        $this->dailyWorkingHours = $dailyWorkingHours;

        return $this;
    }

    public function getDailyWorkingMinutes(): ?int
    {
        return $this->dailyWorkingMinutes;
    }

    public function setDailyWorkingMinutes(int $dailyWorkingMinutes): self
    {
        $this->dailyWorkingMinutes = $dailyWorkingMinutes;

        return $this;
    }

    public function getBreak(): ?int
    {
        return $this->break;
    }

    public function setBreak(int $break): self
    {
        $this->break = $break;

        return $this;
    }

    public function getAnnualTotalWorkHours(): ?int
    {
        return $this->annualTotalWorkHours;
    }

    public function setAnnualTotalWorkHours(int $annualTotalWorkHours): self
    {
        $this->annualTotalWorkHours = $annualTotalWorkHours;

        return $this;
    }

    public function getOvertimeHours(): ?int
    {
        return $this->overtimeHours;
    }

    public function setOvertimeHours(int $overtimeHours): self
    {
        $this->overtimeHours = $overtimeHours;

        return $this;
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
