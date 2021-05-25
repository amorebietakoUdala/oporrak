<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\Groups;
use App\Repository\StatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StatusRepository::class)
 */
class Status
{
    const RESERVED = 1;
    const APPROVED = 2;
    const NOT_APPROVED = 3;
    /**
     * @Groups({"event"})
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups({"event"})
     * @ORM\Column(type="string", length=255)
     */
    private $description;

    /**
     * @Groups({"event"})
     * @ORM\Column(type="string", length=8)
     */
    private $color;

    /**
     * @ORM\OneToMany(targetEntity=Event::class, mappedBy="status")
     */
    private $event;

    public function __construct()
    {
        $this->event = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * @return Collection|Event[]
     */
    public function getEvent(): Collection
    {
        return $this->event;
    }

    // public function addEvent(Event $event): self
    // {
    //     if (!$this->event->contains($event)) {
    //         $this->event[] = $event;
    //         $event->setStatus($this);
    //     }

    //     return $this;
    // }

    // public function removeEvent(Event $event): self
    // {
    //     if ($this->event->removeElement($event)) {
    //         // set the owning side to null (unless already changed)
    //         if ($event->getStatus() === $this) {
    //             $event->setStatus(null);
    //         }
    //     }

    //     return $this;
    // }

    public function __toString()
    {
        return $this->description;
    }
}
