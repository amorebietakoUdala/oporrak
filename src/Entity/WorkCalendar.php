<?php

namespace App\Entity;

use App\Repository\WorkCalendarRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkCalendarRepository::class)]
class WorkCalendar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $year;

    #[ORM\Column(type: 'integer')]
    private $vacationDays;

    #[ORM\Column(type: 'integer')]
    private $particularBusinessLeave;

    #[ORM\Column(type: 'integer')]
    private $overtimeDays;

    #[ORM\Column(type: 'integer')]
    private $workingHours;

    #[ORM\Column(type: 'integer')]
    private $workingMinutes;

    #[ORM\Column(type: 'integer')]
    private $partitionableDays;

    #[ORM\Column(type: 'date', nullable: true)]
    private $deadlineNextYear;

    private $workingHoursDecimal;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

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

    public function getPartitionableHoursDecimal(): float
    {
        return $this->getWorkingHoursDecimal() * $this->partitionableDays;
    }

    public function getPartitionableHoursAsHoursAndMinutes(): string
    {
        $partitionableHours = $this->getPartitionableHoursDecimal();
        $hours = floor($partitionableHours);
        $minutes = ($partitionableHours-floor($partitionableHours))*60;
        return "$hours:$minutes";
    }

    public function getWorkingHours(): ?string
    {
        return $this->workingHours;
    }

    public function setWorkingHours(string $workingHours): self
    {
        $this->workingHours = $workingHours;

        return $this;
    }

    public function getWorkingMinutes(): ?int
    {
        return $this->workingMinutes;
    }

    public function setWorkingMinutes($workingMinutes): self
    {
        $this->workingMinutes = $workingMinutes;

        return $this;
    }

    public function getWorkingHoursDecimal(): ?float
    {
        $this->workingHoursDecimal = ( $this->workingHours ?? 0 ) + ( $this->workingMinutes ?? 0)/60;
        return $this->workingHoursDecimal;

    }

    public function setWorkingHoursDecimal($workingHoursDecimal): self
    {
        $this->workingHoursDecimal = $workingHoursDecimal;

        return $this;
    }

    public function getTotalWorkingMinutes(): ?int
    {
        return ( $this->workingHours * 60 ) + $this->workingMinutes;
    }

    public function getPartitionableDays(): ?int
    {
        return $this->partitionableDays;
    }

    public function setPartitionableDays(int $partitionableDays): self
    {
        $this->partitionableDays = $partitionableDays;

        return $this;
    }

    public function getDeadlineNextYear(): ?\DateTimeInterface
    {
        return $this->deadlineNextYear;
    }

    public function setDeadlineNextYear(?\DateTimeInterface $deadlineNextYear): self
    {
        $this->deadlineNextYear = $deadlineNextYear;

        return $this;
    }

    public function getBaseDays(): int {
        return $this->getVacationDays() + $this->getParticularBusinessLeave() + $this->getOvertimeDays();
    }

    public function fill(WorkCalendar $data): self
    {
        $this->id = $data->getId();
        $this->year = $data->getYear();
        $this->overtimeDays = $data->getOvertimeDays();
        $this->vacationDays = $data->getVacationDays();
        $this->particularBusinessLeave = $data->getParticularBusinessLeave();
        $this->deadlineNextYear = $data->getDeadlineNextYear();
        $this->partitionableDays = $data->getPartitionableDays();
        $this->workingHours = $data->getWorkingHours();
        $this->workingMinutes = $data->getWorkingMinutes();
        return $this;
    }
}
