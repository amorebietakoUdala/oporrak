<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\EventTypeRepository;

/**
 * @ORM\Entity(repositoryClass=EventTypeRepository::class)
 */
class EventType
{
    const VACATION = 1;
    const PARTICULAR_BUSSINESS_LEAVE = 2;
    const OVERTIME = 3;
    const ANTIQUITY_DAYS = 4;
    const ADDITONAL_VACATION_DAYS = 5;
    const OTHERS = 6;
    
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"event"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"event"})
     */
    private $descriptionEs;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"event"})
     */
    private $descriptionEu;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId($id): self
    {
        $this->id = $id;

        return $this;
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

    public function fill(EventType $data): self
    {
        $this->descriptionEs = $data->getDescriptionEs();
        $this->descriptionEu = $data->getDescriptionEu();
        return $this;
    }

    public function __toString()
    {
        return $this->descriptionEs . '/' . $this->descriptionEu;
    }
}
