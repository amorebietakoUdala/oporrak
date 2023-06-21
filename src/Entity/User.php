<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use AMREU\UserBundle\Model\UserInterface as AMREUserInterface;
use AMREU\UserBundle\Model\User as BaseUser;
use App\Repository\AntiquityDaysRepository;
use DateTime;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User extends BaseUser implements AMREUserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list"})
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Groups({"event","list"})
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
     * @ORM\ManyToOne(targetEntity=Department::class, inversedBy="users")
     * @ORM\JoinColumn(nullable=false)
     */
    private $department;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $yearsWorked;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    private $endDate;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $extraDays;

    public function __construct()
    {
        $this->employees = new ArrayCollection();
        $this->events = new ArrayCollection();
        $this->yearsWorked = 0;
        $this->extraDays = 0;
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

    public function getDepartment()
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    /**
     * Get the value of yearsWorked
     */ 
    public function getYearsWorked()
    {
        return $this->yearsWorked === null ? 0 : $this->yearsWorked;
    }

    /**
     * Set the value of yearsWorked
     *
     * @return  self
     */ 
    public function setYearsWorked(int $yearsWorked): self
    {
        $this->yearsWorked = $yearsWorked;

        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getExtraDays(): ?int
    {
        return $this->extraDays;
    }

    public function setExtraDays(?int $extraDays): self
    {
        $this->extraDays = $extraDays;

        return $this;
    }

    public function getWorkDaysCurrentYear(): int {
        $interval = date_diff($this->endDate, $this->startDate);
   
        return $interval->days;        
    }

    public function isFirstYear(): bool {
        $year = (new DateTime())->format('Y');
        if ($this->startDate !== null) {
            $startYear = $this->startDate->format('Y');
            if ($startYear === $year) {
                return true;
            }
        }
        return false;
    }

    public function isLastYear(): bool {
        $year = (new DateTime())->format('Y');
        if ($this->endDate !== null) {
            $endYear = $this->endDate->format('Y');
            if ($endYear === $year) {
                return true;
            }
        }
        return false;
    }

    public function isWorkingAllYear(): bool {
        if ( $this->isFirstYear() || $this->isLastYear() ) {
            return false;
        }
        if ( null === $this->endDate ) {
            return true;
        }
        return true;
    }

    public function calculateCurrentYearBaseDays(WorkCalendar $workCalendar): int  {
        $baseDays = $workCalendar->getBaseDays() + $this->getExtraDays();
        if ( !$this->isWorkingAllYear() ) {
            $baseDays = ceil($this->calculateHasToWorkDaysThisYear() * $baseDays / 365);
        }
        return $baseDays;
    }

    public function calculateHasToWorkDaysThisYear(): int {
        if ( $this->isLastYear() && $this->isFirstYear()) {
            $currentYear = $this->endDate->format('Y');
            $interval = date_diff($this->endDate, $this->startDate);
            $hasToWork =  $interval->days + 1;
            return $hasToWork;
        }
        if ( $this->isLastYear() ) {
            $currentYear = $this->endDate->format('Y');
            $firstDayOfTheYear = date_create_from_format('Y/m/d',$currentYear.'/01/01');
            $interval = date_diff($this->endDate, $firstDayOfTheYear);
            $hasToWork =  $interval->days + 1;
            return $hasToWork;
        }
        if ( $this->isFirstYear() ) {
            $nextYear = $this->startDate->format('Y') + 1;
            $firstDayNextYear = date_create_from_format('Y/m/d',$nextYear.'/01/01');
            $interval = date_diff($firstDayNextYear, $this->startDate);
            $hasToWork =  $interval->days;
            return $hasToWork;
        }
        return 365;
    }

    public function isLimitedTimeWorker(): bool {
        if ( $this->endDate !== null ) {
            return true;
        }
        return false;
    }

    public function getTotals( WorkCalendar $workCalendar, AntiquityDaysRepository $adRepo ): array {
        if ( $this->isWorkingAllYear() ) {
            $totals = [
                EventType::VACATION => $workCalendar->getVacationDays(),
                EventType::PARTICULAR_BUSSINESS_LEAVE => $workCalendar->getParticularBusinessLeave(),
                EventType::OVERTIME => $workCalendar->getOvertimeDays() + $this->getExtraDays(),
                EventType::ANTIQUITY_DAYS => $adRepo->findAntiquityDaysForYearsWorked($this->yearsWorked) !== null ? $adRepo->findAntiquityDaysForYearsWorked($this->yearsWorked)->getVacationDays() : 0,
             ];
        } else {
            $totals = [
                /* If is not working all year, we leave 2 days for particular bussiness days, to allow taking half days */
                EventType::VACATION => $this->calculateCurrentYearBaseDays($workCalendar) - 2,
                EventType::PARTICULAR_BUSSINESS_LEAVE => 2,
                EventType::OVERTIME => 0,
                EventType::ANTIQUITY_DAYS => $adRepo->findAntiquityDaysForYearsWorked($this->yearsWorked) !== null ? $adRepo->findAntiquityDaysForYearsWorked($this->yearsWorked)->getVacationDays() : 0,
             ];
        }
        return $totals;
    }
}
