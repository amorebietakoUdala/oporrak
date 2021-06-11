<?php

namespace App\Entity;

use App\Repository\AntiquityDaysRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=AntiquityDaysRepository::class)
 */
class AntiquityDays
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $yearsWorking;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
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

    public function getYearsWorking(): ?int
    {
        return $this->yearsWorking;
    }

    public function setYearsWorking(int $yearsWorking): self
    {
        $this->yearsWorking = $yearsWorking;

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

    public function fill(AntiquityDays $antiquityDay)
    {
        $this->id = $antiquityDay->getId();
        $this->yearsWorking = $antiquityDay->getYearsWorking();
        $this->vacationDays = $antiquityDay->getVacationDays();
    }
}
