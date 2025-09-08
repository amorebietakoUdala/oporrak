<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

use App\Repository\EventTypeRepository;

#[ORM\Entity(repositoryClass: EventTypeRepository::class)]
class EventType implements \Stringable
{
    final public const VACATION = 1;
    final public const PARTICULAR_BUSSINESS_LEAVE = 2;
    final public const OVERTIME = 3;
    final public const ANTIQUITY_DAYS = 4;
    final public const ADDITONAL_VACATION_DAYS = 5;
    final public const UNION_HOURS = 6;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['event'])]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['event'])]
    private $descriptionEs;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['event'])]
    private $descriptionEu;

    #[ORM\Column(nullable: false, options: ["default" => 0])]
    private ?bool $onlyForUnionDelegates = false;

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
        $this->onlyForUnionDelegates = $data->isOnlyForUnionDelegates();
        return $this;
    }

    public function __toString(): string
    {
        return $this->descriptionEs . '/' . $this->descriptionEu;
    }

    public function isOnlyForUnionDelegates(): ?bool
    {
        return $this->onlyForUnionDelegates;
    }

    public function setOnlyForUnionDelegates(?bool $onlyForUnionDelegates): static
    {
        $this->onlyForUnionDelegates = $onlyForUnionDelegates;

        return $this;
    }
}
