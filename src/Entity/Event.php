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
     * @ORM\Column(type="string", length=255, nullable=false)
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
        $interval = date_diff($this->startDate, $this->endDate);
        return intVal($interval->days);
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
}
