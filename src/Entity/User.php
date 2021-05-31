<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use AMREU\UserBundle\Model\UserInterface as AMREUserInterface;
use AMREU\UserBundle\Model\User as BaseUser;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends BaseUser implements AMREUserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"event"})
     */
    protected $username;

    /**
     * @ORM\Column(type="json")
     */
    protected $roles = [];

    /**
     * @var string The hashed password
     * @ORM\Column(type="string")
     */
    protected $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $firstName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $email;

    /**
     * @ORM\Column(type="boolean", options={"default":"1"}, nullable=true)
     */
    protected $activated;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastLogin;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="employees")
     */
    private $boss;

    /**
     * @ORM\OneToMany(targetEntity=User::class, mappedBy="boss")
     */
    private $employees;

    private $events;

    /**
     * @ORM\ManyToOne(targetEntity=Department::class)
     */
    private $department;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    public function getBoss(): ?self
    {
        return $this->boss;
    }

    public function setBoss(?self $boss): self
    {
        $this->boss = $boss;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getEmployees(): Collection
    {
        return $this->employees;
    }

    public function addEmployee(self $employee): self
    {
        if (!$this->employees->contains($employee)) {
            $this->employees[] = $employee;
            $employee->setBoss($this);
        }

        return $this;
    }

    public function removeEmployee(self $employee): self
    {
        if ($this->users->removeElement($employee)) {
            // set the owning side to null (unless already changed)
            if ($employee->getBoss() === $this) {
                $employee->setBoss(null);
            }
        }

        return $this;
    }

    public function addEvent(Event $event): self
    {
        if (!$this->events->contains($event)) {
            $this->events[] = $event;
            $event->setUser($this);
        }

        return $this;
    }

    public function removeEvent(Event $event): self
    {
        if ($this->events->removeElement($event)) {
            // set the owning side to null (unless already changed)
            if ($event->getUser() === $this) {
                $event->setUser(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->username;
    }

    /**
     * Get the value of department
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * Set the value of department
     *
     * @return  self
     */
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }
}
