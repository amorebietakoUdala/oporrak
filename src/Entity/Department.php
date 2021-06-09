<?php

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=DepartmentRepository::class)
 */
class Department
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nameEs;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $nameEu;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }


    public function getNameEs(): ?string
    {
        return $this->nameEs;
    }

    public function setNameEs(string $nameEs): self
    {
        $this->nameEs = $nameEs;

        return $this;
    }

    public function getNameEu(): ?string
    {
        return $this->nameEu;
    }

    public function setNameEu(string $nameEu): self
    {
        $this->nameEu = $nameEu;

        return $this;
    }

    public function __toString(): ?string
    {
        return $this->getNameEs() . ' / ' . $this->getNameEs();
    }

    public function fill(Department $department)
    {
        $this->id = $department->getId();
        $this->nameEs = $department->getNameEs();
        $this->nameEu = $department->getNameEu();
    }
}
