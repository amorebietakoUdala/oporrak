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
    private $descriptionEs;

    /**
     * @Groups({"event"})
     * @ORM\Column(type="string", length=255)
     */
    private $descriptionEu;

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

    public function getDescriptionEs(): ?string
    {
        return $this->descriptionEs;
    }

    public function setDescriptionEs(string $descriptionEs): self
    {
        $this->descriptionEs = $descriptionEs;

        return $this;
    }

    public function getDescriptionEu(): ?string
    {
        return $this->descriptionEu;
    }

    public function setDescriptionEu(string $descriptionEu): self
    {
        $this->descriptionEu = $descriptionEu;

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

    public function __toString()
    {
        return $this->descriptionEs;
    }

    public function copy(Status $status): self
    {
        $this->id = $status->getId();
        $this->descriptionEs = $status->getDescriptionEs();
        $this->descriptionEu = $status->getDescriptionEu();
        $this->color = $status->getColor();
        return $this;
    }

    public function getDescription($locale) {
        if ( $locale === 'es' ) {
            return $this->getDescriptionEs();
        } else {
            return $this->getDescriptionEu();
        }
    }
}
