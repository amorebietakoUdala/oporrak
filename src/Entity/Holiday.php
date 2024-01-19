<?php

namespace App\Entity;

use App\Repository\HolidayRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HolidayRepository::class)]
class Holiday
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer')]
    private $year;

    #[ORM\Column(type: 'date')]
    private $date;

    #[ORM\Column(type: 'string', length: 255)]
    private $descriptionEs;

    #[ORM\Column(type: 'string', length: 255)]
    private $descriptionEu;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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

    public function fillFromArray(array $data): self
    {
        $this->date = new \DateTime($data['date']);
        $this->year = (new \DateTime($data['date']))->format('Y');
        if ( array_key_exists('descriptionEs', $data) )
        {
            $this->descriptionEs = $data['descriptionEs'];
        } elseif (array_key_exists('descripcionEs', $data)) {
            $this->descriptionEs = $data['descripcionEs'];
        }
        if ( array_key_exists('descriptionEu', $data) )
        {
            $this->descriptionEu = $data['descriptionEu'];
        } elseif (array_key_exists('descripcionEu', $data)) {
            $this->descriptionEu = $data['descripcionEu'];
        }
        return $this;
    }

    public function fill(Holiday $data): self
    {
        $this->date = $data->getDate();
        $this->year = $data->getYear();
        $this->descriptionEs = $data->getDescriptionEs();
        $this->descriptionEu = $data->getDescriptionEu();
        return $this;
    }
}
