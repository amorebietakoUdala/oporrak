<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=EventRepository::class)
 */
class Event
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"event"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"event"})
     */
    private $name;

    /**
     * @ORM\Column(type="date", nullable=false)
     * @Groups({"event"})
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", nullable=false)
     * @Groups({"event"})
     */
    private $endDate;

    /**
     * @ORM\ManyToOne(targetEntity=Status::class, inversedBy="event")
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"event"})
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"event"})
     */
    private $user;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default" : false} )
     * @Groups({"event"})
     */
    private $halfDay;

    /**
     * @ORM\ManyToOne(targetEntity=EventType::class)
     * @Groups({"event"})
     */
    private $type;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"event"})
     */
    private $askedAt;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Groups({"event"})
     */
    private $hours;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     * @Groups({"event"})
     */
    private $usePreviousYearDays;

    public function __construct()
    {
        $this->halfDay = false;
        $this->usePreviousYearDays = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate = null): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate = null): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function fill(Event $event)
    {
        $this->id = $event->getId();
        $this->name = $event->getName();
        $this->startDate = $event->getStartDate();
        $this->endDate = $event->getEndDate();
        $this->status = $event->getStatus();
        $this->user = $event->getUser();
        $this->halfDay = $event->getHalfDay();
        $this->askedAt = $event->getAskedAt();
        $this->type = $event->getType();
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDays(): int
    {
        if (!$this->halfDay) {
            $interval = date_diff($this->startDate, $this->endDate);
            return intVal($interval->days) + 1;
        } else {
            return 0;
        }
    }

    public function getHalfDay(): ?bool
    {
        return $this->halfDay;
    }

    public function setHalfDay($halfDay): self
    {
        $this->halfDay = $halfDay;

        return $this;
    }

    public function checkOverlap($event): bool
    {
        if (
            $this->startDate >= $event->getStartDate() && $this->startDate <= $event->getEndDate()
            || $this->endDate >= $event->getStartDate() && $this->endDate <= $event->getEndDate()
            || $event->getStartDate() >= $this->startDate && $event->getStartDate() <= $this->startDate
            || $event->getEndDate()  >= $this->startDate && $event->getEndDate() <= $this->endDate
        ) {
            return true;
        }
        return false;
    }

    public function getType(): ?EventType
    {
        return $this->type;
    }

    public function setType(?EventType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAskedAt(): ?\DateTimeInterface
    {
        return $this->askedAt;
    }

    public function setAskedAt(\DateTimeInterface $askedAt): self
    {
        $this->askedAt = $askedAt;

        return $this;
    }

    public function getHours(): ?float
    {
        return $this->hours;
    }

    public function setHours(?float $hours): self
    {
        $this->hours = $hours;

        return $this;
    }

    public function getUsePreviousYearDays(): ?bool
    {
        return $this->usePreviousYearDays;
    }

    public function setUsePreviousYearDays(?bool $usePreviousYearDays): self
    {
        $this->usePreviousYearDays = $usePreviousYearDays;

        return $this;
    }

    /**
     * @Groups({"event"})
     */
    public function isBetweenYears(): bool {
        if ( $this->startDate->format('Y') !== $this->endDate->format('Y') ) {
            return true;
        }

        return false;
    }

    public function getDaysFirstYear(): int {
        $startDate = $this->getStartDate();
        $year = $this->getStartDate()->format('Y');
        $nextYear = intval($year) + 1;
        $nextYearStartDate = new \DateTime("${nextYear}-01-01");
        return $nextYearStartDate->diff($startDate)->format("%a");
    }

    public function getDaysSecondYear(): int {
        $year = $this->getEndDate()->format('Y');
        $endYearStartDate = new \DateTime("${year}-01-01");
        return $this->endDate->diff($endYearStartDate)->format("%a") + 1;
    }
}
