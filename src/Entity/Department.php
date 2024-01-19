<?php

namespace App\Entity;

use App\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
class Department implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $nameEs;

    #[ORM\Column(type: 'string', length: 255)]
    private $nameEu;

    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'department')]
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

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

    public function __toString(): string
    {
        return $this->getNameEs() . ' / ' . $this->getNameEs();
    }

    public function fill(Department $department)
    {
        $this->id = $department->getId();
        $this->nameEs = $department->getNameEs();
        $this->nameEu = $department->getNameEu();
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->setDepartment($this);
        }

        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getDepartment() === $this) {
                $user->setDepartment(null);
            }
        }

        return $this;
    }
}
