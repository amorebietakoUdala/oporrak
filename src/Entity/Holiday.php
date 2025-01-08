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
        // Capitalize descriptions
        if ( array_key_exists('descriptiones', $data) )
        {
            $this->descriptionEs = mb_convert_case($data['descriptiones'], MB_CASE_TITLE, "UTF-8");
        } elseif (array_key_exists('descripciones', $data)) {
            $this->descriptionEs = mb_convert_case($data['descripciones'], MB_CASE_TITLE, "UTF-8");
        }
        if ( array_key_exists('descriptioneu', $data) )
        {
            $this->descriptionEu = mb_convert_case($data['descriptioneu'], MB_CASE_TITLE, "UTF-8");
        } elseif (array_key_exists('descripcioneu', $data)) {
            $this->descriptionEu = mb_convert_case($data['descripcioneu'], MB_CASE_TITLE, "UTF-8");
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
