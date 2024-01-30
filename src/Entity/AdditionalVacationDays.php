<?php

namespace App\Entity;

use App\Repository\AdditionalVacationDaysRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdditionalVacationDaysRepository::class)]
class AdditionalVacationDays
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $yearsWorked;

    #[ORM\Column(type: 'integer', nullable: false)]
    private $vacationDays;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getYearsWorked(): ?int
    {
        return $this->yearsWorked;
    }

    public function setYearsWorked(int $yearsWorked): self
    {
        $this->yearsWorked = $yearsWorked;

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

    public function fill(AdditionalVacationDays $additionalVacationDays)
    {
        $this->id = $additionalVacationDays->getId();
        $this->yearsWorked = $additionalVacationDays->getYearsWorked();
        $this->vacationDays = $additionalVacationDays->getVacationDays();
    }
}
