<?php

namespace App\DTO;

use App\Entity\Department;
use App\Entity\User;

class ReportsFilterFormDTO
{
   private int $year;

   // private $startDate;

   // private $endDate;

   private ?User $user = null;

   private ?Department $department = null;

   // /**
   //  * Get the value of startDate
   //  */ 
   // public function getStartDate(): ?\DateTime
   // {
   //    return $this->startDate;
   // }

   // /**
   //  * Set the value of startDate
   //  *
   //  * @return  self
   //  */ 
   // public function setStartDate(\DateTime $startDate = null)
   // {
   //    $this->startDate = $startDate;

   //    return $this;
   // }

   // /**
   //  * Get the value of endDate
   //  */ 
   // public function getEndDate(): ?\DateTime
   // {
   //    return $this->endDate;
   // }

   // /**
   //  * Set the value of endDate
   //  *
   //  * @return  self
   //  */ 
   // public function setEndDate(\DateTime $endDate = null)
   // {
   //    $this->endDate = $endDate;

   //    return $this;
   // }

   public function __construct()
   {
      $this->year = intval((new \DateTime())->format('Y'));
   }

   /**
    * Get the value of user
    */ 
   public function getUser(): ?User
   {
      return $this->user;
   }

   /**
    * Set the value of user
    *
    * @return  self
    */ 
   public function setUser(User $user = null)
   {
      $this->user = $user;

      return $this;
   }

   /**
    * Get the value of department
    */ 
   public function getDepartment(): ?Department
   {
      return $this->department;
   }

   /**
    * Set the value of department
    *
    * @return  self
    */ 
   public function setDepartment(Department $department = null)
   {
      $this->department = $department;

      return $this;
   }

   /**
    * Get the value of year
    */ 
   public function getYear():int 
   {
      return $this->year;
   }

   /**
    * Set the value of year
    *
    * @return  self
    */ 
   public function setYear(int $year)
   {
      $this->year = $year;

      return $this;
   }
}