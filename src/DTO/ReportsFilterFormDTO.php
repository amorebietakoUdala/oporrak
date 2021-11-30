<?php

namespace App\DTO;

use App\Entity\Department;
use App\Entity\User;

class ReportsFilterFormDTO
{
   private $startDate;

   private $endDate;

   private $user;

   private $department;

   /**
    * Get the value of startDate
    */ 
   public function getStartDate(): ?\DateTime
   {
      return $this->startDate;
   }

   /**
    * Set the value of startDate
    *
    * @return  self
    */ 
   public function setStartDate(\DateTime $startDate = null)
   {
      $this->startDate = $startDate;

      return $this;
   }

   /**
    * Get the value of endDate
    */ 
   public function getEndDate(): ?\DateTime
   {
      return $this->endDate;
   }

   /**
    * Set the value of endDate
    *
    * @return  self
    */ 
   public function setEndDate(\DateTime $endDate = null)
   {
      $this->endDate = $endDate;

      return $this;
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
}